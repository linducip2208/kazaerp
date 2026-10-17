<?php

namespace App\Http\Controllers\Identity;

use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends CrudController
{
    protected string $model = \App\Models\User::class;
    protected string $routePrefix = 'users';
    protected string $viewPrefix = 'modules.users';
    protected string $title = 'Pengguna';
    protected string $perm = 'users';
    protected array $columns = [
        'name' => 'Nama', 'email' => 'Email', 'is_active' => 'Aktif',
    ];
    protected array $fields = [
        ['name' => 'name', 'label' => 'Nama', 'type' => 'text'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
        ['name' => 'password', 'label' => 'Kata sandi (kosongkan jika tidak diubah)', 'type' => 'password'],
        ['name' => 'legal_entity_id', 'label' => 'Badan Usaha', 'type' => 'select_model', 'model' => 'App\\Models\\Org\\LegalEntity'],
        ['name' => 'branch_id', 'label' => 'Cabang', 'type' => 'select_model', 'model' => 'App\\Models\\Org\\Branch'],
    ];
    protected array $rules = [
        'name' => 'required|max:100', 'email' => 'required|email|max:150',
    ];
    protected array $searchable = ['name', 'email'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;

    public function store(Request $r)
    {
        $r->merge(['email' => str_replace(["\r", "\n"], '', trim((string) $r->email))]);
        $v = $r->validate(array_merge($this->rules, ['password' => 'required|min:8']));
        $this->injectContext($v);
        $row = ($this->model)::create($v);
        \App\Services\AuditService::log('create', 'users', $row->id, []);

        return redirect()->route('users.roles', $row->id)->with('ok', 'Pengguna dibuat. Tetapkan peran.');
    }

    public function update(Request $r, $id)
    {
        $row = $this->baseQuery()->findOrFail($id);
        $r->merge(['email' => str_replace(["\r", "\n"], '', trim((string) $r->email))]);
        $v = $r->validate(array_merge($this->rules, ['password' => 'nullable|min:8']));
        if (empty($v['password'])) {
            unset($v['password']);
        }
        $row->update($v);
        \App\Services\AuditService::log('update', 'users', $row->id, []);

        return redirect()->route($this->routePrefix.'.show', $row->id)->with('ok', 'Pengguna diperbarui.');
    }

    public function toggle($id)
    {
        $row = ($this->model)::findOrFail($id);
        if ((int) $row->id === (int) auth()->id()) {
            return back()->withErrors(['msg' => 'Tidak dapat menonaktifkan akun sendiri.']);
        }
        $row->update(['is_active' => ! $row->is_active]);
        \App\Services\AuditService::log($row->is_active ? 'activate' : 'deactivate', 'users', $row->id, []);

        return back()->with('ok', 'Status pengguna diperbarui (sesi lama otomatis kehilangan hak via pengecekan is_active).');
    }

    public function editRoles($id)
    {
        $row = ($this->model)::with('roles')->findOrFail($id);
        $roles = \App\Models\Identity\Role::orderBy('label')->get();
        $scopes = DB::table('user_scopes')->where('user_id', $id)->get();
        $entities = \App\Models\Org\LegalEntity::orderBy('code')->get();
        $branches = \App\Models\Org\Branch::orderBy('code')->get();

        return view('modules.users.roles', compact('row', 'roles', 'scopes', 'entities', 'branches'));
    }

    public function updateRoles(Request $r, $id)
    {
        $r->validate(['roles' => 'nullable|array', 'roles.*' => 'exists:roles,id']);
        DB::transaction(function () use ($r, $id) {
            DB::table('role_user')->where('user_id', $id)->delete();
            foreach ($r->get('roles', []) as $rid) {
                DB::table('role_user')->insert(['role_id' => $rid, 'user_id' => $id]);
            }
        });
        \App\Services\AuditService::log('roles', 'users', (int) $id, ['roles' => $r->get('roles', [])]);

        return back()->with('ok', 'Peran pengguna diperbarui.');
    }

    public function updateScopes(Request $r, $id)
    {
        $r->validate([
            'entities' => 'nullable|array', 'entities.*' => 'exists:legal_entities,id',
            'branches' => 'nullable|array', 'branches.*' => 'exists:branches,id',
        ]);
        DB::transaction(function () use ($r, $id) {
            DB::table('user_scopes')->where('user_id', $id)->delete();
            foreach ($r->get('entities', []) as $e) {
                DB::table('user_scopes')->updateOrInsert(
                    ['user_id' => $id, 'scope_type' => 'legal_entity', 'scope_id' => $e],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
            foreach ($r->get('branches', []) as $b) {
                DB::table('user_scopes')->updateOrInsert(
                    ['user_id' => $id, 'scope_type' => 'branch', 'scope_id' => $b],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        });

        return back()->with('ok', 'Cakupan entitas/cabang diperbarui.');
    }
}
