<?php

namespace App\Http\Controllers\Identity;

use App\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends CrudController
{
    protected string $model = \App\Models\Identity\Role::class;
    protected string $routePrefix = 'roles';
    protected string $viewPrefix = 'modules.roles';
    protected string $title = 'Peran';
    protected string $perm = 'roles';
    protected array $columns = [
        'name' => 'Kode', 'label' => 'Label',
    ];
    protected array $fields = [
        ['name' => 'name', 'label' => 'Kode', 'type' => 'text'],
        ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
    ];
    protected array $rules = [
        'name' => 'required|max:60', 'label' => 'required|max:100',
    ];
    protected array $searchable = ['name', 'label'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;

    public function perms($id)
    {
        $row = ($this->model)::findOrFail($id);
        $groups = \App\Models\Identity\Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $owned = DB::table('permission_role')->where('role_id', $id)->pluck('permission_id')->all();

        return view('modules.roles.perms', compact('row', 'groups', 'owned'));
    }

    public function updatePerms(Request $r, $id)
    {
        $r->validate(['permissions' => 'nullable|array', 'permissions.*' => 'exists:permissions,id']);
        DB::transaction(function () use ($r, $id) {
            DB::table('permission_role')->where('role_id', $id)->delete();
            foreach ($r->get('permissions', []) as $pid) {
                DB::table('permission_role')->insert(['role_id' => $id, 'permission_id' => $pid]);
            }
        });
        \App\Services\AuditService::log('perms', 'roles', (int) $id, ['permissions' => $r->get('permissions', [])]);

        return back()->with('ok', 'Permission peran diperbarui.');
    }
}
