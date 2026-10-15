<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class CustomerController extends CrudController {
    protected string $model = \App\Models\Master\Customer::class;
    protected string $routePrefix = 'crm.customers';
    protected string $viewPrefix = 'modules.crm.customers';
    protected string $title = 'Pelanggan';
    protected string $perm = 'crm';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'phone' => 'Telepon',
        'tier' => 'Tier'
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'kind','label'=>'Jenis','type'=>'select', 'options'=>['retail'=>'Retail','reseller'=>'Reseller','corporate'=>'Corporate']],
        ['name'=>'tier','label'=>'Tier Harga','type'=>'select', 'options'=>['retail'=>'Retail','wholesale'=>'Wholesale','reseller'=>'Reseller']],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'email','label'=>'Email','type'=>'email']
    ];
    protected array $rules = [
        'code' => 'required|max:30',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name', 'phone'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = 'branch_id';
}
