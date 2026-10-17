<?php
namespace App\Http\Controllers\Finance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FinanceReportController extends Controller {
    protected function eid(): ?int {
        $ctx = session('erp_context',[]); return $ctx['legal_entity_id'] ?? auth()->user()->legal_entity_id;
    }
    protected function esc(mixed $v): string {
        $s = (string) $v;
        return ($s !== '' && str_contains('=+-@', $s[0])) ? "'".$s : $s;
    }
    protected function csv(string $filename, string $header, iterable $rows, callable $map): \Symfony\Component\HttpFoundation\Response {
        $out = fopen('php://temp', 'r+');
        fwrite($out, $header."\n");
        $n = 0;
        foreach ($rows as $x) {
            if (++$n > 5000) break;
            $cols = array_map(fn ($v) => '"'.str_replace('"', '""', $this->esc($v)).'"', $map($x));
            fwrite($out, implode(',', $cols)."\n");
        }
        rewind($out); $csv = stream_get_contents($out); fclose($out);

        return response($csv, 200, ['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }
    public function ledger(Request $r) {
        $rows = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')
            ->where('journals.legal_entity_id',$this->eid())
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))
            ->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->when($r->coa_id,fn($q)=>$q->where('journal_lines.coa_id',$r->coa_id))
            ->select('journals.date','journals.number','coas.code','coas.name','journal_lines.debit','journal_lines.credit')
            ->orderBy('journals.date')->paginate(30)->withQueryString();
        $coas = DB::table('coas')->where('legal_entity_id',$this->eid())->orderBy('code')->get();
        if ($r->get('export')==='csv') {
            $all = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
                ->join('coas','coas.id','=','journal_lines.coa_id')
                ->where('journals.legal_entity_id',$this->eid())
                ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))
                ->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
                ->when($r->coa_id,fn($q)=>$q->where('journal_lines.coa_id',$r->coa_id))
                ->select('journals.date','journals.number','coas.code','coas.name','journal_lines.debit','journal_lines.credit')
                ->orderBy('journals.date')->orderBy('journals.id')->cursor();

            return $this->csv('buku-besar.csv', 'tanggal,nomor,kode_akun,nama_akun,debit,kredit', $all,
                fn ($x) => [$x->date, $x->number, $x->code, $x->name, $x->debit, $x->credit]);
        }

        return view('modules.finance.reports.ledger', compact('rows','coas'));
    }
    public function trialBalance(Request $r) {
        $rows = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')
            ->where('journals.legal_entity_id',$this->eid())
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))
            ->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('coas.code, coas.name, SUM(journal_lines.debit) debit, SUM(journal_lines.credit) credit')
            ->groupBy('coas.code','coas.name')->orderBy('coas.code')->get();
        if ($r->get('export')==='csv') {
            return $this->csv('neraca-saldo.csv', 'kode,nama,debit,kredit', $rows,
                fn ($x) => [$x->code, $x->name, $x->debit, $x->credit]);
        }

        return view('modules.finance.reports.trial', compact('rows'));
    }
    public function pl(Request $r) {
        $sum = fn($types)=>DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')->where('journals.legal_entity_id',$this->eid())
            ->whereIn('coas.type',$types)
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('SUM(journal_lines.credit - journal_lines.debit) s')->value('s') ?? 0;
        $rev = $sum(['revenue']); $exp = -1*$sum(['expense']);
        $detail = DB::table('journal_lines')->join('journals','journals.id','=','journal_lines.journal_id')
            ->join('coas','coas.id','=','journal_lines.coa_id')->where('journals.legal_entity_id',$this->eid())
            ->whereIn('coas.type',['revenue','expense'])
            ->when($r->from,fn($q)=>$q->where('journals.date','>=',$r->from))->when($r->to,fn($q)=>$q->where('journals.date','<=',$r->to))
            ->selectRaw('coas.code, coas.name, coas.type, SUM(journal_lines.debit) d, SUM(journal_lines.credit) c')
            ->groupBy('coas.code','coas.name','coas.type')->orderBy('coas.code')->get();
        if ($r->get('export')==='csv') {
            return $this->csv('laba-rugi.csv', 'kode,nama,tipe,mutasi', $detail,
                fn ($x) => [$x->code, $x->name, $x->type, ((float) $x->c - (float) $x->d)]);
        }

        return view('modules.finance.reports.pl', ['revenue'=>$rev,'expense'=>$exp,'net'=>$rev-$exp,'detail'=>$detail]);
    }
    public function aging(Request $r) {        $kind = $r->get('kind','ar');
        if ($kind==='ap') {
            $base = fn () => DB::table('vendor_invoices')->join('suppliers','suppliers.id','=','vendor_invoices.supplier_id')
                ->where('vendor_invoices.legal_entity_id',$this->eid())
                ->selectRaw('suppliers.name, vendor_invoices.number, vendor_invoices.due_date, vendor_invoices.grand_total - vendor_invoices.paid_total as sisa, DATEDIFF(CURDATE(), vendor_invoices.due_date) as overdue')
                ->having('sisa','>',0);
            $rows = (clone $base())->orderByDesc('overdue')->paginate(20)->withQueryString();
        } else {
            $base = fn () => DB::table('invoices')->join('customers','customers.id','=','invoices.customer_id')
                ->where('invoices.legal_entity_id',$this->eid())
                ->selectRaw('customers.name, invoices.number, invoices.due_date, invoices.grand_total - invoices.paid_total as sisa, DATEDIFF(CURDATE(), invoices.due_date) as overdue')
                ->having('sisa','>',0);
            $rows = (clone $base())->orderByDesc('overdue')->paginate(20)->withQueryString();
        }
        if ($r->get('export')==='csv') {
            $all = (clone $base())->orderByDesc('overdue')->cursor();

            return $this->csv('aging-'.$kind.'.csv', 'nama,nomor,jatuh_tempo,sisa,overdue_hari', $all,
                fn ($x) => [$x->name, $x->number, $x->due_date, $x->sisa, $x->overdue]);
        }

        return view('modules.finance.reports.aging', compact('rows','kind'));
    }
    public function closing(Request $r) {
        $periods = DB::table('fiscal_periods')->where('legal_entity_id',$this->eid())->orderByDesc('start_date')->limit(12)->get();
        return view('modules.finance.reports.closing', compact('periods'));
    }
    public function doClose(Request $r) {
        $r->validate(['period_id'=>'required|exists:fiscal_periods,id']);
        $p = DB::table('fiscal_periods')->where('id',$r->period_id)->first();
        if ((int)$p->legal_entity_id !== (int)$this->eid() && !auth()->user()->hasRole('super_admin','holding_owner')) abort(403);
        DB::table('fiscal_periods')->where('id',$p->id)->update(['is_closed'=>true]);
        DB::table('period_closings')->insert(['fiscal_period_id'=>$p->id,'status'=>'closed','note'=>$r->note,'closed_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Periode ditutup. Posting ke periode ini akan ditolak.');
    }
    public function reopen(Request $r) {
        $r->validate(['period_id'=>'required|exists:fiscal_periods,id','reason'=>'required|max:255']);
        if (!auth()->user()->hasPermission('finance.close')) abort(403);
        $p = DB::table('fiscal_periods')->where('id',$r->period_id)->first();
        DB::table('fiscal_periods')->where('id',$p->id)->update(['is_closed'=>false]);
        DB::table('period_closings')->insert(['fiscal_period_id'=>$p->id,'status'=>'reopened','note'=>$r->reason,'closed_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Periode dibuka kembali (tercatat + butuh permission finance.close).');
    }
    /** Worksheet eliminasi intercompany: dasar manual untuk Finance (otomatisasi butuh kebijakan). */
    public function intercompany(Request $r)
    {
        $txns = DB::table('intercompany_txns')
            ->join('legal_entities as f', 'f.id', '=', 'intercompany_txns.from_entity_id')
            ->join('legal_entities as t', 't.id', '=', 'intercompany_txns.to_entity_id')
            ->when($r->from, fn ($q) => $q->where('intercompany_txns.date', '>=', $r->from))
            ->when($r->to, fn ($q) => $q->where('intercompany_txns.date', '<=', $r->to))
            ->select('intercompany_txns.*', 'f.code as from_code', 't.code as to_code')
            ->orderByDesc('intercompany_txns.id')->paginate(20)->withQueryString();
        $pairs = DB::table('intercompany_txns')
            ->join('legal_entities as f', 'f.id', '=', 'intercompany_txns.from_entity_id')
            ->join('legal_entities as t', 't.id', '=', 'intercompany_txns.to_entity_id')
            ->when($r->from, fn ($q) => $q->where('intercompany_txns.date', '>=', $r->from))
            ->when($r->to, fn ($q) => $q->where('intercompany_txns.date', '<=', $r->to))
            ->selectRaw('f.code as dari, t.code as ke, SUM(intercompany_txns.amount) as total, COUNT(*) as n')
            ->groupBy('f.code', 't.code')->get();

        return view('modules.finance.reports.intercompany', compact('txns', 'pairs'));
    }
    /** Budget vs aktual per akun (aktual = mutasi jurnal periode budget). */
    public function budget(Request $r)
    {
        $budgets = DB::table('budgets')->where('legal_entity_id', $this->eid())->orderByDesc('id')->get();
        $bid = $r->get('budget_id', $budgets->first()->id ?? null);
        $lines = [];
        if ($bid) {
            $b = DB::table('budgets')->where('id', $bid)->first();
            $fy = DB::table('fiscal_years')->where('id', $b->fiscal_year_id)->first();
            $lines = DB::table('budget_lines')->join('coas', 'coas.id', '=', 'budget_lines.coa_id')
                ->leftJoin('journal_lines', 'journal_lines.coa_id', '=', 'budget_lines.coa_id')
                ->leftJoin('journals', function ($j) use ($fy) {
                    $j->on('journals.id', '=', 'journal_lines.journal_id');
                    if ($fy) {
                        $j->where('journals.date', '>=', $fy->start_date)->where('journals.date', '<=', $fy->end_date);
                    }
                })
                ->where('budget_lines.budget_id', $bid)
                ->selectRaw('coas.code, coas.name, budget_lines.amount as budget,
                    COALESCE(SUM(journal_lines.debit - journal_lines.credit),0) as actual_net')
                ->groupBy('coas.code', 'coas.name', 'budget_lines.amount')
                ->orderBy('coas.code')->get();
        }

        return view('modules.finance.reports.budget', compact('budgets', 'lines', 'bid'));
    }
}
