<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit integritas data lintas ledger:
 * 1) saldo stok (stock_balances) vs mutasi (stock_ledgers)
 * 2) mutasi stok tanpa baris saldo (ledger yatim)
 * 3) jurnal: debit = credit dan total header = jumlah baris
 * 4) invoice: paid_total vs jumlah pembayaran (sales_payments)
 * Exit code 1 bila ada temuan (untuk monitoring/cron alerting).
 */
class AuditIntegrity extends Command
{
    protected $signature = 'kaza:audit-integrity {--limit=10 : Jumlah baris sampel per temuan}';

    protected $description = 'Audit integritas: saldo stok vs ledger, balance jurnal, paid_total invoice vs pembayaran.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $findings = 0;

        // 1) Saldo stok != sum ledger
        $stock = DB::select("
            SELECT t.balance_id, t.warehouse_id, t.product_id, t.variant_id, t.batch_id, t.qty_on_hand, t.ledger_qty
            FROM (
                SELECT w.id AS balance_id, w.warehouse_id, w.product_id, w.variant_id, w.batch_id,
                       w.qty_on_hand, COALESCE(l.qty, 0) AS ledger_qty
                FROM stock_balances w
                LEFT JOIN (
                    SELECT warehouse_id, product_id, variant_id, batch_id, SUM(qty) AS qty
                    FROM stock_ledgers
                    GROUP BY warehouse_id, product_id, variant_id, batch_id
                ) l ON l.warehouse_id = w.warehouse_id AND l.product_id = w.product_id
                   AND (l.variant_id <=> w.variant_id) AND (l.batch_id <=> w.batch_id)
            ) t
            WHERE ABS(t.qty_on_hand - t.ledger_qty) > 0.0001
        ");
        $findings += $this->report('Saldo stok != sum ledger', $stock, $limit, function ($r) {
            return sprintf('balance#%d wh=%d product=%d variant=%s batch=%s saldo=%s ledger=%s',
                $r->balance_id, $r->warehouse_id, $r->product_id, $r->variant_id ?? '-', $r->batch_id ?? '-',
                (float) $r->qty_on_hand, (float) $r->ledger_qty);
        });

        // 2) Ledger yatim: mutasi tanpa baris saldo dan sum != 0
        $orphan = DB::select("
            SELECT l.warehouse_id, l.product_id, l.variant_id, l.batch_id, l.qty
            FROM (
                SELECT warehouse_id, product_id, variant_id, batch_id, SUM(qty) AS qty
                FROM stock_ledgers
                GROUP BY warehouse_id, product_id, variant_id, batch_id
            ) l
            LEFT JOIN stock_balances w ON w.warehouse_id = l.warehouse_id AND w.product_id = l.product_id
                AND (w.variant_id <=> l.variant_id) AND (w.batch_id <=> l.batch_id)
            WHERE w.id IS NULL AND ABS(l.qty) > 0.0001
        ");
        $findings += $this->report('Ledger tanpa baris saldo', $orphan, $limit, function ($r) {
            return sprintf('wh=%d product=%d variant=%s batch=%s sum_qty=%s',
                $r->warehouse_id, $r->product_id, $r->variant_id ?? '-', $r->batch_id ?? '-', (float) $r->qty);
        });

        // 3) Jurnal tidak balance / total header != jumlah baris
        $journals = DB::select("
            SELECT j.id, j.number, j.total_debit, j.total_credit,
                   COALESCE(SUM(jl.debit), 0) AS sum_debit, COALESCE(SUM(jl.credit), 0) AS sum_credit
            FROM journals j
            LEFT JOIN journal_lines jl ON jl.journal_id = j.id
            GROUP BY j.id, j.number, j.total_debit, j.total_credit
            HAVING ABS(j.total_debit - j.total_credit) > 0.005
                OR ABS(j.total_debit - COALESCE(SUM(jl.debit), 0)) > 0.005
                OR ABS(j.total_credit - COALESCE(SUM(jl.credit), 0)) > 0.005
        ");
        $findings += $this->report('Jurnal tidak balance / header != baris', $journals, $limit, function ($r) {
            return sprintf('%s (journal#%d) debit=%s credit=%s baris_debit=%s baris_credit=%s',
                $r->number, $r->id, (float) $r->total_debit, (float) $r->total_credit,
                (float) $r->sum_debit, (float) $r->sum_credit);
        });

        // 4) Invoice: paid_total != sum pembayaran / kelebihan bayar
        $invoices = DB::select("
            SELECT i.id, i.number, i.grand_total, i.paid_total, COALESCE(p.sum_paid, 0) AS sum_paid
            FROM invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS sum_paid FROM sales_payments GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE ABS(i.paid_total - COALESCE(p.sum_paid, 0)) > 0.005
               OR COALESCE(p.sum_paid, 0) > i.grand_total + 0.005
        ");
        $findings += $this->report('paid_total invoice != sum pembayaran', $invoices, $limit, function ($r) {
            return sprintf('%s (invoice#%d) grand=%s paid_total=%s pembayaran=%s',
                $r->number, $r->id, (float) $r->grand_total, (float) $r->paid_total, (float) $r->sum_paid);
        });

        if ($findings === 0) {
            $this->info('AUDIT OK: saldo stok, jurnal, dan pembayaran invoice konsisten.');

            return self::SUCCESS;
        }
        $this->error("AUDIT GAGAL: {$findings} temuan integritas. Tindak lanjuti sebelum tutup buku.");

        return self::FAILURE;
    }

    /** Cetak satu kategori temuan; kembalikan jumlah temuan. */
    protected function report(string $label, array $rows, int $limit, callable $fmt): int
    {
        $n = count($rows);
        if ($n === 0) {
            $this->line("  [OK] {$label}: 0 temuan");

            return 0;
        }
        $this->warn("  [TEMUAN] {$label}: {$n}");
        foreach (array_slice($rows, 0, $limit) as $r) {
            $this->line('    - '.$fmt($r));
        }
        if ($n > $limit) {
            $this->line('    ... dan '.($n - $limit).' lainnya (naikkan --limit untuk melihat semua)');
        }

        return $n;
    }
}
