<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($r->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? back()->with('ok', 'Tautan reset dikirim (cek log email pada environment dev).')
            : back()->withErrors(['email' => 'Email tidak ditemukan.']);
    }

    public function showReset(Request $r, $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $r->email]);
    }

    public function doReset(Request $r)
    {
        $r->validate([
            'token' => 'required', 'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            });
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', 'Kata sandi direset. Silakan masuk.')
            : back()->withErrors(['email' => 'Token tidak valid/kedaluwarsa.']);
    }
}
