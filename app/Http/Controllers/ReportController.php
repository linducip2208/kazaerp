<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Laporan enterprise: filter tanggal + entitas/cabang (entity scope wajib),
 * agregat konsisten dengan filter, ekspor CSV + PDF, siap cetak.
 */
class ReportController extends Controller
{
    protected function eid(): int
    {
        $ctx = session('erp_context', []);

        return (int) ($ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id);
    }

    /** Netralkan formula injection CSV (sel diawali = + - @). */
    protected function esc(mixed $v): string
    {
        $s = (string) $v;
        if ($s !== '' && str_contains('=+-@', $s[0])) {
            return "'".$s;
        }

        return $s;
    }

    protected function csvResponse(string $filename, string $header, iterable $rows, callable $map): \Symfony\Component\HttpFoundation\Response
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, $header."\n");
        $n = 0;
        foreach ($rows as $x) {
            if (++$n > 5000) {
                fwrite($out, '"Catatan: dibatasi 5000 baris pertama. Persempit filter tanggal."'.str_repeat(',', max(0, substr_count($header, ',') - 0))."\n");
                break;
            }
            $cols = array_map(fn ($v) => '"'.str_replace('"', '""', $this->esc($v)).'"', $map($x));
            fwrite($out, implode(',', $cols)."\n");
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function pdfResponse(string $title, string $subtitle, array $headers, iterable $rows, callable $map, ?string $totalLine, string $filename)
    {
        $mapped = [];
        foreach ($rows as $x) {
            $mapped[] = $map($x);
            if (count($mapped) >= 2000) {
                break;
            }
        }
        $pdf = Pdf::loadView('modules.reports.pdf_table', [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $mapped,
            'totalLine' => $totalLine,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    // ---------- PENJUALAN ----------
    public function sales(Request $r)
    {
        $eid = $this->eid();
        $q = trim((string) $r->get('q', ''));
        $status = trim((string) $r->get('status', ''));
        $branch = (int) $r->get('branch', 0);

        $base = fn () => DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.legal_entity_id', $eid)
            ->when($q !== '', fn ($qry) => $qry->where(fn ($w) => $w
                ->where('invoices.number', 'like', '%'.$q.'%')
                ->orWhere('customers.name', 'like', '%'.$q.'%')))
            ->when($r->from, fn ($qry) => $qry->where('invoices.date', '>=', $r->from))
            ->when($r->to, fn ($qry) => $qry->where('invoices.date', '<=', $r->to))
            ->when($status !== '', fn ($qry) => $qry->where('invoices.pay_status', $status))
            ->when($branch > 0, fn ($qry) => $qry->where('invoices.branch_id', $branch));

        $rows = (clone $base())
            ->select('invoices.number', 'invoices.date', 'invoices.due_date', 'customers.name as customer',
                'invoices.grand_total', 'invoices.paid_total', 'invoices.pay_status', 'invoices.status')
            ->orderByDesc('invoices.id')->paginate(25)->withQueryString();

        $sum = (clone $base())
            ->selectRaw('COUNT(*) c, COALESCE(SUM(invoices.grand_total),0) t, COALESCE(SUM(invoices.paid_total),0) p')
            ->first();
        $sum->sisa = (float) $sum->t - (float) $sum->p;

        $branches = DB::table('branches')->where('legal_entity_id', $eid)->orderBy('code')->get(['id', 'code', 'name']);

        $subtitle = 'Periode '.($r->from ?: '…').' s/d '.($r->to ?: '…').($status !== '' ? ' · Status: '.$status : '');

        if ($r->get('export') === 'csv') {
            $all = (clone $base())
                ->select('invoices.number', 'invoices.date', 'customers.name as customer',
                    'invoices.grand_total', 'invoices.paid_total', 'invoices.pay_status')
                ->orderBy('invoices.date')->orderBy('invoices.id')->cursor();

            return $this->csvResponse('penjualan.csv',
                'nomor,tanggal,pelanggan,total,bayar,status',
                $all,
                fn ($x) => [$x->number, $x->date, $x->customer, $x->grand_total, $x->paid_total, $x->pay_status]);
        }
        if ($r->get('export') === 'pdf') {
            $all = (clone $base())
                ->select('invoices.number', 'invoices.date', 'customers.name as customer',
                    'invoices.grand_total', 'invoices.paid_total', 'invoices.pay_status')
                ->orderBy('invoices.date')->orderBy('invoices.id')->cursor();

            return $this->pdfResponse('Laporan Penjualan', $subtitle,
                ['Nomor', 'Tanggal', 'Pelanggan', 'Total', 'Bayar', 'Status'],
                $all,
                fn ($x) => [$x->number, tgl_id($x->date), $x->customer, idr($x->grand_total), idr($x->paid_total), $x->pay_status],
                'Transaksi: '.$sum->c.' · Total: '.idr($sum->t).' · Terbayar: '.idr($sum->p).' · Sisa: '.idr($sum->sisa),
                'laporan-penjualan.pdf');
        }

        return view('modules.reports.sales', compact('rows', 'sum', 'branches'));
    }

    // ---------- INVENTORI (mutasi) ----------
    public function inventory(Request $r)
    {
        $eid = $this->eid();
        $q = trim((string) $r->get('q', ''));
        $wh = (int) $r->get('warehouse', 0);

        $base = fn () => DB::table('stock_ledgers')
            ->join('products', 'products.id', '=', 'stock_ledgers.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_ledgers.warehouse_id')
            ->where('products.legal_entity_id', $eid)
            ->when($q !== '', fn ($qry) => $qry->where(fn ($w) => $w
                ->where('products.sku', 'like', '%'.$q.'%')
                ->orWhere('products.name', 'like', '%'.$q.'%')))
            ->when($r->from, fn ($qry) => $qry->where('stock_ledgers.created_at', '>=', $r->from))
            ->when($r->to, fn ($qry) => $qry->where('stock_ledgers.created_at', '<=', $r->to.' 23:59:59'))
            ->when($wh > 0, fn ($qry) => $qry->where('stock_ledgers.warehouse_id', $wh));

        $rows = (clone $base())
            ->select('stock_ledgers.created_at', 'stock_ledgers.qty', 'stock_ledgers.ref_type', 'stock_ledgers.ref_id',
                'products.sku', 'products.name as product', 'warehouses.code as wh')
            ->orderByDesc('stock_ledgers.id')->paginate(25)->withQueryString();

        $sum = (clone $base())
            ->selectRaw('COUNT(*) c, COALESCE(SUM(stock_ledgers.qty),0) qty_masuk_keluar_bersih')
            ->first();

        $warehouses = DB::table('warehouses')
            ->join('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->where('branches.legal_entity_id', $eid)
            ->orderBy('warehouses.code')->get(['warehouses.id', 'warehouses.code']);

        $subtitle = 'Periode '.($r->from ?: '…').' s/d '.($r->to ?: '…');

        if ($r->get('export') === 'csv') {
            $all = (clone $base())
                ->select('stock_ledgers.created_at', 'products.sku', 'products.name as product',
                    'warehouses.code as wh', 'stock_ledgers.qty', 'stock_ledgers.ref_type', 'stock_ledgers.ref_id')
                ->orderBy('stock_ledgers.id')->cursor();

            return $this->csvResponse('inventori.csv',
                'waktu,sku,produk,gudang,qty,ref_type,ref_id',
                $all,
                fn ($x) => [$x->created_at, $x->sku, $x->product, $x->wh, $x->qty, $x->ref_type, $x->ref_id]);
        }
        if ($r->get('export') === 'pdf') {
            $all = (clone $base())
                ->select('stock_ledgers.created_at', 'products.sku', 'products.name as product',
                    'warehouses.code as wh', 'stock_ledgers.qty', 'stock_ledgers.ref_type', 'stock_ledgers.ref_id')
                ->orderBy('stock_ledgers.id')->cursor();

            return $this->pdfResponse('Laporan Mutasi Inventori', $subtitle,
                ['Waktu', 'SKU', 'Produk', 'Gudang', 'Qty', 'Referensi'],
                $all,
                fn ($x) => [tgl_id($x->created_at, true), $x->sku, $x->product, $x->wh, $x->qty, $x->ref_type.' #'.$x->ref_id],
                'Baris mutasi: '.$sum->c,
                'laporan-inventori.pdf');
        }

        return view('modules.reports.inventory', compact('rows', 'sum', 'warehouses'));
    }

    // ---------- PEMBELIAN ----------
    public function purchase(Request $r)
    {
        $eid = $this->eid();
        $q = trim((string) $r->get('q', ''));
        $status = trim((string) $r->get('status', ''));

        $base = fn () => DB::table('purchase_orders')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->where('purchase_orders.legal_entity_id', $eid)
            ->when($q !== '', fn ($qry) => $qry->where(fn ($w) => $w
                ->where('purchase_orders.number', 'like', '%'.$q.'%')
                ->orWhere('suppliers.name', 'like', '%'.$q.'%')))
            ->when($r->from, fn ($qry) => $qry->where('purchase_orders.date', '>=', $r->from))
            ->when($r->to, fn ($qry) => $qry->where('purchase_orders.date', '<=', $r->to))
            ->when($status !== '', fn ($qry) => $qry->where('purchase_orders.status', $status));

        $rows = (clone $base())
            ->select('purchase_orders.number', 'purchase_orders.date', 'suppliers.name as supplier',
                'purchase_orders.subtotal', 'purchase_orders.tax', 'purchase_orders.grand_total', 'purchase_orders.status')
            ->orderByDesc('purchase_orders.id')->paginate(25)->withQueryString();

        $sum = (clone $base())
            ->selectRaw('COUNT(*) c, COALESCE(SUM(purchase_orders.grand_total),0) t')
            ->first();

        $subtitle = 'Periode '.($r->from ?: '…').' s/d '.($r->to ?: '…').($status !== '' ? ' · Status: '.$status : '');

        if ($r->get('export') === 'csv') {
            $all = (clone $base())
                ->select('purchase_orders.number', 'purchase_orders.date', 'suppliers.name as supplier',
                    'purchase_orders.subtotal', 'purchase_orders.tax', 'purchase_orders.grand_total', 'purchase_orders.status')
                ->orderBy('purchase_orders.date')->orderBy('purchase_orders.id')->cursor();

            return $this->csvResponse('pembelian.csv',
                'nomor,tanggal,supplier,subtotal,pajak,total,status',
                $all,
                fn ($x) => [$x->number, $x->date, $x->supplier, $x->subtotal, $x->tax, $x->grand_total, $x->status]);
        }
        if ($r->get('export') === 'pdf') {
            $all = (clone $base())
                ->select('purchase_orders.number', 'purchase_orders.date', 'suppliers.name as supplier',
                    'purchase_orders.grand_total', 'purchase_orders.status')
                ->orderBy('purchase_orders.date')->orderBy('purchase_orders.id')->cursor();

            return $this->pdfResponse('Laporan Pembelian', $subtitle,
                ['Nomor', 'Tanggal', 'Supplier', 'Total', 'Status'],
                $all,
                fn ($x) => [$x->number, tgl_id($x->date), $x->supplier, idr($x->grand_total), $x->status],
                'PO: '.$sum->c.' · Total: '.idr($sum->t),
                'laporan-pembelian.pdf');
        }

        return view('modules.reports.purchase', compact('rows', 'sum'));
    }

    // ---------- STOK MENUA ----------
    public function aging(Request $r)
    {
        $eid = $this->eid();
        $days = max(1, (int) $r->get('days', 30));
        $q = trim((string) $r->get('q', ''));
        $wh = (int) $r->get('warehouse', 0);

        $base = fn () => DB::table('stock_balances')
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->leftJoin('stock_ledgers as last_move', function ($j) {
                $j->on('last_move.warehouse_id', '=', 'stock_balances.warehouse_id')
                    ->on('last_move.product_id', '=', 'stock_balances.product_id');
            })
            ->where('products.legal_entity_id', $eid)
            ->whereRaw('stock_balances.qty_on_hand - stock_balances.qty_reserved > 0')
            ->when($q !== '', fn ($qry) => $qry->where(fn ($w) => $w
                ->where('products.sku', 'like', '%'.$q.'%')
                ->orWhere('products.name', 'like', '%'.$q.'%')))
            ->when($wh > 0, fn ($qry) => $qry->where('stock_balances.warehouse_id', $wh));

        $rows = (clone $base())
            ->selectRaw('warehouses.code as wh, products.sku, products.name as product,
                stock_balances.qty_on_hand, stock_balances.qty_reserved,
                MAX(last_move.created_at) as last_move_at,
                DATEDIFF(CURDATE(), MAX(last_move.created_at)) as age_days')
            ->groupBy('warehouses.code', 'products.sku', 'products.name', 'stock_balances.qty_on_hand', 'stock_balances.qty_reserved')
            ->having('age_days', '>=', $days)
            ->orderByDesc('age_days')->paginate(25)->withQueryString();

        $warehouses = DB::table('warehouses')
            ->join('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->where('branches.legal_entity_id', $eid)
            ->orderBy('warehouses.code')->get(['warehouses.id', 'warehouses.code']);

        $subtitle = 'Tanpa mutasi ≥ '.$days.' hari';

        if ($r->get('export') === 'csv') {
            $all = (clone $base())
                ->selectRaw('warehouses.code as wh, products.sku, products.name as product,
                    stock_balances.qty_on_hand, stock_balances.qty_reserved,
                    MAX(last_move.created_at) as last_move_at,
                    DATEDIFF(CURDATE(), MAX(last_move.created_at)) as age_days')
                ->groupBy('warehouses.code', 'products.sku', 'products.name', 'stock_balances.qty_on_hand', 'stock_balances.qty_reserved')
                ->having('age_days', '>=', $days)
                ->orderByDesc('age_days')->cursor();

            return $this->csvResponse('stok-menua.csv',
                'gudang,sku,produk,tersedia,mutasi_terakhir,umur_hari',
                $all,
                fn ($x) => [$x->wh, $x->sku, $x->product, ((float) $x->qty_on_hand - (float) $x->qty_reserved), $x->last_move_at, $x->age_days]);
        }
        if ($r->get('export') === 'pdf') {
            $all = (clone $base())
                ->selectRaw('warehouses.code as wh, products.sku, products.name as product,
                    stock_balances.qty_on_hand, stock_balances.qty_reserved,
                    MAX(last_move.created_at) as last_move_at,
                    DATEDIFF(CURDATE(), MAX(last_move.created_at)) as age_days')
                ->groupBy('warehouses.code', 'products.sku', 'products.name', 'stock_balances.qty_on_hand', 'stock_balances.qty_reserved')
                ->having('age_days', '>=', $days)
                ->orderByDesc('age_days')->cursor();

            return $this->pdfResponse('Stok Lambat / Menua', $subtitle,
                ['Gudang', 'SKU', 'Produk', 'Tersedia', 'Mutasi Terakhir', 'Umur (hari)'],
                $all,
                fn ($x) => [$x->wh, $x->sku, $x->product, ((float) $x->qty_on_hand - (float) $x->qty_reserved),
                    $x->last_move_at ? tgl_id($x->last_move_at) : '-', $x->age_days],
                null,
                'laporan-stok-menua.pdf');
        }

        return view('modules.reports.aging', compact('rows', 'days', 'warehouses'));
    }
}
