<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    protected function engine(): Google2FA
    {
        return new Google2FA;
    }

    /** Halaman setup: hasilkan secret + QR (SVG inline, tanpa CDN). */
    public function setup(Request $r)
    {
        $u = $r->user();
        if (! $u->two_factor_secret || $r->get('regen')) {
            $u->forceFill(['two_factor_secret' => $this->engine()->generateSecretKey(), 'two_factor_confirmed_at' => null])->save();
        }
        $qrUrl = $this->engine()->getQRCodeUrl(config('app.name'), $u->email, $u->two_factor_secret);
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($qrUrl);

        return view('auth.2fa-setup', ['svg' => $svg, 'confirmed' => (bool) $u->two_factor_confirmed_at]);
    }

    public function confirm(Request $r)
    {
        $r->validate(['code' => 'required|digits:6']);
        $u = $r->user();
        if (! $u->two_factor_secret || ! $this->engine()->verifyKey($u->two_factor_secret, $r->code)) {
            return back()->withErrors(['code' => 'Kode salah atau kedaluwarsa.']);
        }
        $u->forceFill(['two_factor_confirmed_at' => now()])->save();
        \App\Services\AuditService::log('2fa_enable', 'users', $u->id, []);

        return back()->with('ok', 'Autentikasi dua faktor aktif.');
    }

    public function disable(Request $r)
    {
        $r->validate(['password' => 'required']);
        $u = $r->user();
        if (! \Illuminate\Support\Facades\Hash::check($r->password, $u->password)) {
            return back()->withErrors(['password' => 'Kata sandi salah.']);
        }
        $u->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        \App\Services\AuditService::log('2fa_disable', 'users', $u->id, []);

        return back()->with('ok', '2FA dinonaktifkan.');
    }

    public function challenge()
    {
        if (! session()->has('2fa_pending')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-challenge');
    }

    public function verify(Request $r)
    {
        $r->validate(['code' => 'required|digits:6']);
        $uid = session('2fa_pending');
        if (! $uid) {
            return redirect()->route('login');
        }
        $user = \App\Models\User::find($uid);
        if (! $user || ! $user->two_factor_confirmed_at
            || ! $this->engine()->verifyKey($user->two_factor_secret, $r->code)) {
            return back()->withErrors(['code' => 'Kode salah atau kedaluwarsa.']);
        }
        session()->forget('2fa_pending');
        Auth::login($user, (bool) session('2fa_remember'));
        session()->forget('2fa_remember');
        $r->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $r->session()->put('erp_context', ['holding_id' => $user->holding_id, 'legal_entity_id' => $user->legal_entity_id, 'branch_id' => $user->branch_id]);

        return redirect()->intended(route('dashboard'));
    }
}
