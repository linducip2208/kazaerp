<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $rows = DB::table('notifications')->where('user_id', auth()->id())
            ->orderByDesc('id')->paginate(20);

        return view('modules.notifications.index', compact('rows'));
    }

    public function read($id)
    {
        DB::table('notifications')->where('id', $id)->where('user_id', auth()->id())
            ->update(['read_at' => now()]);

        $n = DB::table('notifications')->where('id', $id)->first();

        return redirect($n->link ?? route('dashboard'));
    }

    public function readAll()
    {
        DB::table('notifications')->where('user_id', auth()->id())->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('ok', 'Semua notifikasi ditandai dibaca.');
    }
}
