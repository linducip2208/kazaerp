<?php
namespace App\Http\Controllers\Thrift;
use App\Http\Controllers\CrudController;
class ItemController extends CrudController {
    protected string $model = \App\Models\Thrift\ThriftItem::class;
    protected string $routePrefix = 'thrift.items';
    protected string $viewPrefix = 'modules.thrift.items';
    protected string $title = 'Item Thrift';
    protected string $perm = 'thrift';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text']
    ];
    protected array $rules = [
        'code' => 'required',
        'name' => 'required'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
}
