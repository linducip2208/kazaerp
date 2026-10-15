<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankRecController extends Controller
{
    public function index()
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        $rows = DB::table('bank_reconciliations')->join('bank_accounts', 'bank_accounts.id', '=', 'bank_reconciliations.bank_account_id')
            ->where('bank_accounts.legal_entity_id', $eid)
            ->select('bank_reconciliations.*', 'bank_accounts.bank_name', 'bank_accounts.account_no')
            ->orderByDesc('bank_reconciliations.id')->paginate(15);

        return view('modules.finance.bankrec.index', compact('rows'));
    }

    public function create()
    {
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;

        return view('modules.finance.bankrec.form', [
            'banks' => DB::table('bank_accounts')->where('legal_entity_id', $eid)->where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $r->validate(['bank_account_id' => 'required|exists:bank_accounts,id', 'period' => 'required|date',
            'book_balance' => 'required|numeric', 'bank_balance' => 'required|numeric']);
        $bank = DB::table('bank_accounts')->where('id', $r->bank_account_id)->first();
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
        if ((int) $bank->legal_entity_id !== (int) $eid && ! auth()->user()->hasRole('super_admin', 'holding_owner')) {
            abort(403);
        }
        $diff = (float) $r->book_balance - (float) $r->bank_balance;
        DB::table('bank_reconciliations')->insert(['bank_account_id' => $bank->id, 'period' => $r->period,
            'book_balance' => $r->book_balance, 'bank_balance' => $r->bank_balance, 'difference' => $diff,
            'status' => abs($diff) < 0.005 ? 'matched' : 'draft', 'note' => $r->note,
            'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('finance.bankrec.index')->with('ok',
            abs($diff) < 0.005 ? 'Rekonsiliasi cocok (matched).' : 'Selisih '.number_format($diff, 0).' dicatat — telusuri sebelum tutup periode.');
    }

    public function confirm($id)
    {
        $row = DB::table('bank_reconciliations')->where('id', $id)->firstOrFail();
        DB::table('bank_reconciliations')->where('id', $id)->update(['status' => 'confirmed', 'note' => request('note', $row->note)]);

        return back()->with('ok', 'Rekonsiliasi dikonfirmasi.');
    }
}
