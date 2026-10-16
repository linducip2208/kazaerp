<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Buat/promosikan akun super_admin untuk produksi (pengganti "seeder admin interaktif").
 * Interaktif bila opsi tidak diisi; aman dijalankan berulang (idempoten per email).
 */
class AdminCreate extends Command
{
    protected $signature = 'kaza:admin-create
        {--name= : Nama admin}
        {--email= : Email login}
        {--password= : Password (min. 12 karakter; interaktif bila kosong)}';

    protected $description = 'Buat/promosikan akun super_admin (produksi) — interaktif bila opsi tidak diisi.';

    public function handle(): int
    {
        if (! $this->input->isInteractive()
            && (! $this->option('name') || ! $this->option('email') || ! $this->option('password'))) {
            $this->error('Mode non-interaktif: isi lengkap --name, --email, dan --password.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nama admin')));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email login'))));
        $password = (string) ($this->option('password') ?: $this->secret('Password (min. 12 karakter)'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }
        if (strlen($password) < 12) {
            $this->error('Password minimal 12 karakter.');

            return self::FAILURE;
        }
        if (empty($name)) {
            $this->error('Nama wajib diisi.');

            return self::FAILURE;
        }

        $holding = DB::table('holdings')->orderBy('id')->value('id');
        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if (! $holding || ! $roleId) {
            $this->error('Seeder inti belum dijalankan (RolesPermissionsSeeder + OrgSeeder).');

            return self::FAILURE;
        }

        $userId = DB::table('users')->where('email', $email)->value('id');
        $created = false;
        if ($userId) {
            DB::table('users')->where('id', $userId)->update([
                'name' => $name, 'password' => Hash::make($password), 'is_active' => true, 'updated_at' => now(),
            ]);
        } else {
            $userId = DB::table('users')->insertGetId([
                'name' => $name, 'email' => $email, 'password' => Hash::make($password),
                'is_active' => true, 'holding_id' => $holding, 'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $created = true;
        }
        DB::table('role_user')->updateOrInsert(['role_id' => $roleId, 'user_id' => $userId], []);

        $this->info(($created ? 'Admin dibuat' : 'Admin diperbarui') . ': ' . $email);
        $this->line('Langkah lanjut: login → aktifkan 2FA → ganti password bila dibuat oleh orang lain.');

        return self::SUCCESS;
    }
}
