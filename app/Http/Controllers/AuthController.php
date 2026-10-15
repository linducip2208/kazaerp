<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class AuthController extends Controller {
    public function showLogin() {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }
    public function login(Request $r) {
        $r->validate(['email'=>'required|email','password'=>'required'],[],['email'=>'Email','password'=>'Kata sandi']);
        $email = strtolower(trim($r->email));
        $user = \App\Models\User::where('email',$email)->first();
        if ($user && !$user->is_active) {
            DB::table('login_activities')->insert(['user_id'=>$user->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>false,'created_at'=>now()]);
            return back()->withErrors(['email'=>'Akun nonaktif. Hubungi administrator.'])->onlyInput('email');
        }
        if (!Auth::attempt(['email'=>$email,'password'=>$r->password], $r->boolean('remember'))) {
            DB::table('login_activities')->insert(['user_id'=>$user?->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>false,'created_at'=>now()]);
            return back()->withErrors(['email'=>'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        $u = Auth::user();
        $u->forceFill(['last_login_at'=>now()])->save();
        $r->session()->put('erp_context',['holding_id'=>$u->holding_id,'legal_entity_id'=>$u->legal_entity_id,'branch_id'=>$u->branch_id]);
        DB::table('login_activities')->insert(['user_id'=>$u->id,'email'=>$email,'ip'=>$r->ip(),'agent'=>substr((string)$r->userAgent(),0,250),'success'=>true,'created_at'=>now()]);
        return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $r) {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function profile() { return view('auth.profile'); }
    public function updateProfile(Request $r) {
        $r->validate(['name'=>'required|string|max:100']);
        $r->user()->update(['name'=>$r->name]);
        return back()->with('ok','Profil diperbarui.');
    }
    public function switchContext(Request $r) {
        $r->validate(['legal_entity_id'=>'nullable|exists:legal_entities,id','branch_id'=>'nullable|exists:branches,id']);
        $u = $r->user();
        if ($r->legal_entity_id && !$u->inScope('legal_entity',(int)$r->legal_entity_id) && !$u->hasRole('super_admin','holding_owner','executive')) abort(403);
        if ($r->branch_id && !$u->inScope('branch',(int)$r->branch_id) && (int)$u->branch_id!==(int)$r->branch_id && !$u->hasRole('super_admin','holding_owner','executive')) abort(403);
        $ctx = $r->session()->get('erp_context',[]);
        if ($r->legal_entity_id) $ctx['legal_entity_id'] = (int)$r->legal_entity_id;
        if ($r->branch_id) $ctx['branch_id'] = (int)$r->branch_id;
        $r->session()->put('erp_context',$ctx);
        return back()->with('ok','Konteks aktif diperbarui.');
    }
}
