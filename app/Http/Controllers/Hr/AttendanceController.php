<?php
namespace App\Http\Controllers\Hr;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AttendanceController extends Controller {
    public function index(Request $r) {
        $rows = DB::table('attendances')->join('employees','employees.id','=','attendances.employee_id')
            ->select('attendances.*','employees.name')->orderByDesc('attendances.date')->paginate(20)->withQueryString();
        return view('modules.hr.attendance.index', compact('rows'));
    }
    public function store(Request $r) {
        $r->validate(['employee_id'=>'required|exists:employees,id','date'=>'required|date','check_in'=>'nullable','check_out'=>'nullable']);
        DB::table('attendances')->updateOrInsert(['employee_id'=>$r->employee_id,'date'=>$r->date],
            ['check_in'=>$r->check_in,'check_out'=>$r->check_out,'status'=>'present','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('ok','Absensi dicatat.');
    }
}
