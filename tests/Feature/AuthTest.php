<?php
namespace Tests\Feature;
use Tests\TestCase;
class AuthTest extends TestCase {
    public function test_login_berhasil_dan_gagal(): void {
        $u = $this->makeUser('viewer');
        $u->forceFill(['email'=>'auth1@test.local'])->save();
        $this->post('/login',['email'=>'auth1@test.local','password'=>'secret123'])->assertRedirect('/');
        $this->post('/login',['email'=>'auth1@test.local','password'=>'salah'])->assertSessionHasErrors();
        // akun nonaktif ditolak
        $u->update(['is_active'=>false]);
        $this->post('/login',['email'=>'auth1@test.local','password'=>'secret123'])->assertSessionHasErrors('email');
    }
    public function test_guest_dialihkan(): void {
        $this->get('/')->assertRedirect('/login');
    }
}
