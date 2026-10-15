<?php
namespace App\Http\Controllers\Org;
use App\Http\Controllers\CrudController;
class WarehouseController extends CrudController {
    protected string $model = \App\Models\Org\Warehouse::class;
    protected string $routePrefix = 'org.warehouses';
    protected string $viewPrefix = 'modules.org.warehouses';
    protected string $title = 'Gudang';
    protected string $perm = 'org';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama'
    ];
    protected array $fields = [
        ['name'=>'branch_id','label'=>'Cabang','type'=>'select_model', 'model'=>'App\\Models\\Org\\Branch', 'option_label'=>'name'],
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text']
    ];
    protected array $rules = [
        'branch_id' => 'required|exists:branches,id',
        'code' => 'required|max:20',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;
}
