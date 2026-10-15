<?php
namespace App\Http\Controllers\Gov;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ApprovalService;
class ApprovalController extends Controller {
    public function index(Request $r) {
        $rows = DB::table('approvals')->orderByDesc('id')->paginate(20)->withQueryString();
        return view('modules.gov.approvals.index', compact('rows'));
    }
    public function decide(Request $r, $id) {
        $r->validate(['decision'=>'required|in:approved,rejected','note'=>'nullable|max:500']);
        try {
            ApprovalService::decide((int)$id, $r->decision, $r->note);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return back()->with('ok','Keputusan approval dicatat.');
    }
}
