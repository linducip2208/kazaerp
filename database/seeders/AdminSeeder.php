<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Admin awal PRODUKSI — hanya berjalan bila env KAZA_ADMIN_* diisi.
 * Tidak ada kredensial default yang mudah ditebak.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('KAZA_ADMIN_EMAIL');
        $pass = env('KAZA_ADMIN_PASSWORD');
        if (! $email || ! $pass || strlen($pass) < 12) {
            $this->command->warn('AdminSeeder dilewati: isi KAZA_ADMIN_EMAIL + KAZA_ADMIN_PASSWORD (min 12 karak) di .env.');

            return;
        }
        $holding = DB::table('holdings')->orderBy('id')->value('id');
        $id = DB::table('users')->where('email', $email)->value('id');
        if (! $id) {
            $id = DB::table('users')->insertGetId([
                'name' => 'Administrator', 'email' => $email, 'password' => Hash::make($pass),
                'is_active' => true, 'holding_id' => $holding, 'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $rid = DB::table('roles')->where('name', 'super_admin')->value('id');
        DB::table('role_user')->updateOrInsert(['role_id' => $rid, 'user_id' => $id], []);
        $this->command->info('Admin siap: '.$email.' (segera hapus kredensial dari .env/history setelah login).');
    }
}
