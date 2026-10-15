<?php
namespace App\Http\Controllers\Thrift;
use App\Http\Controllers\CrudController;
class LotController extends CrudController {
    protected string $model = \App\Models\Thrift\ThriftLot::class;
    protected string $routePrefix = 'thrift.lots';
    protected string $viewPrefix = 'modules.thrift.lots';
    protected string $title = 'Lot Thrift';
    protected string $perm = 'thrift';
    protected array $columns = [
        'number' => 'Nomor',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'status','label'=>'Status','type'=>'text']
    ];
    protected array $rules = [
        
    ];
    protected array $searchable = ['number'];
    protected ?string $entityField = null;
}
