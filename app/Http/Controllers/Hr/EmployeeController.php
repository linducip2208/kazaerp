<?php
namespace App\Http\Controllers\Hr;
use App\Http\Controllers\CrudController;
class EmployeeController extends CrudController {
    protected string $model = \App\Models\Hr\Employee::class;
    protected string $routePrefix = 'hr.employees';
    protected string $viewPrefix = 'modules.hr.employees';
    protected string $title = 'Karyawan';
    protected string $perm = 'hr';
    protected array $columns = ['nip'=>'NIP','name'=>'Nama','status'=>'Status'];
    protected array $fields = [
        ['name'=>'nip','label'=>'NIP','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'join_date','label'=>'Tgl Masuk','type'=>'date'],
        ['name'=>'position_id','label'=>'Jabatan','type'=>'select_model','model'=>'App\\Models\\Hr\\Position'],
        ['name'=>'department_id','label'=>'Departemen','type'=>'select_model','model'=>'App\\Models\\Org\\Department'],
    ];
    protected array $rules = ['nip'=>'required|max:30','name'=>'required|max:150'];
    protected array $searchable = ['nip','name'];
}
