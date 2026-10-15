<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class SupplierController extends CrudController {
    protected string $model = \App\Models\Master\Supplier::class;
    protected string $routePrefix = 'crm.suppliers';
    protected string $viewPrefix = 'modules.crm.suppliers';
    protected string $title = 'Supplier';
    protected string $perm = 'crm';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'phone' => 'Telepon'
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'email','label'=>'Email','type'=>'email'],
        ['name'=>'bank_account','label'=>'Rekening','type'=>'text']
    ];
    protected array $rules = [
        'code' => 'required|max:30',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = null;
}
