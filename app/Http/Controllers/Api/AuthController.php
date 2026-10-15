<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function token(Request $r)
    {
        $r->validate(['email' => 'required|email', 'password' => 'required']);
        $user = \App\Models\User::where('email', strtolower(trim($r->email)))->first();
        if (! $user || ! Hash::check($r->password, $user->password) || ! $user->is_active) {
            return response()->json(['message' => 'Kredensial salah atau akun nonaktif.'], 401);
        }
        $token = $user->createToken('api', ['api.access'])->plainTextToken;

        return response()->json(['token' => $token, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
    }

    public function me(Request $r)
    {
        $u = $r->user();

        return response()->json([
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
            'roles' => $u->roles()->pluck('name'),
            'context' => ['legal_entity_id' => $u->legal_entity_id, 'branch_id' => $u->branch_id],
        ]);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token dicabut.']);
    }
}
