<?php
namespace App\Http\Controllers\Mfg;
use App\Http\Controllers\CrudController;
class WorkOrderController extends CrudController {
    protected string $model = \App\Models\Mfg\WorkOrder::class;
    protected string $routePrefix = 'mfg.workorders';
    protected string $viewPrefix = 'modules.mfg.workorders';
    protected string $title = 'Work Order';
    protected string $perm = 'mfg';
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
