<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocNumberService;
use App\Services\PostingService;

class ApPaymentController extends Controller
{
    public function index()
    {
        $rows = DB::table('ap_payments')->join('suppliers', 'suppliers.id', '=', 'ap_payments.supplier_id')
            ->select('ap_payments.*', 'suppliers.name as supplier')
            ->orderByDesc('ap_payments.id')->paginate(15);

        return view('modules.purchasing.appayments.index', compact('rows'));
    }

    public function create(Request $r)
    {
        $vinv = $r->get('from_vinvoice') ? DB::table('vendor_invoices')->find($r->get('from_vinvoice')) : null;

        return view('modules.purchasing.appayments.form', compact('vinv'));
    }

    public function store(Request $r)
    {
        $r->validate([
            'vendor_invoice_id' => 'required|exists:vendor_invoices,id',
            'amount' => 'required|numeric|min:1', 'method' => 'required|in:cash,transfer,giro',
            'date' => 'required|date',
        ]);
        $u = auth()->user();
        $ctx = session('erp_context', []);
        try {
            DB::transaction(function () use ($r, $u, $ctx) {
                $v = DB::table('vendor_invoices')->where('id', $r->vendor_invoice_id)->lockForUpdate()->first();
                $sisa = (float) $v->grand_total - (float) $v->paid_total;
                if ((float) $r->amount > $sisa + 0.005) {
                    throw new \RuntimeException('Pembayaran melebihi sisa utang ('.number_format($sisa, 0).').');
                }
                $ent = \App\Models\Org\LegalEntity::find($v->legal_entity_id);
                $number = DocNumberService::next('AP', $v->legal_entity_id, $v->branch_id ?? $ctx['branch_id'] ?? $u->branch_id, $ent?->code);
                DB::table('ap_payments')->insert([
                    'legal_entity_id' => $v->legal_entity_id, 'branch_id' => $v->branch_id ?? $ctx['branch_id'] ?? $u->branch_id,
                    'number' => $number, 'date' => $r->date, 'vendor_invoice_id' => $v->id,
                    'supplier_id' => $v->supplier_id, 'method' => $r->method, 'amount' => $r->amount,
                    'reference' => $r->reference, 'created_by' => $u->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $paid = (float) $v->paid_total + (float) $r->amount;
                DB::table('vendor_invoices')->where('id', $v->id)->update([
                    'paid_total' => $paid,
                    'status' => $paid >= (float) $v->grand_total - 0.005 ? 'paid' : 'partial',
                ]);
                $coa = fn ($code) => DB::table('coas')->where(['legal_entity_id' => $v->legal_entity_id, 'code' => $code])->value('id');
                if ($coa('2000') && $coa('1000')) {
                    PostingService::postJournal(
                        ['legal_entity_id' => $v->legal_entity_id, 'branch_id' => $v->branch_id, 'date' => $r->date,
                            'number' => 'J-'.$number, 'description' => 'Bayar utang '.$v->number,
                            'source_type' => 'ap_payment', 'source_id' => $v->id],
                        [['coa_id' => $coa('2000'), 'debit' => (float) $r->amount, 'credit' => 0],
                            ['coa_id' => $coa('1000'), 'debit' => 0, 'credit' => (float) $r->amount]],
                        'appay-'.$number
                    );
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchasing.appayments.index')->with('ok', 'Pembayaran utang tercatat + jurnal.');
    }
}
