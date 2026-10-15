<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class TransportOrderController extends CrudController {
    protected string $model = \App\Models\Pet\TransportOrder::class;
    protected string $routePrefix = 'pet.transport';
    protected string $viewPrefix = 'modules.pet.transport';
    protected string $title = 'Transport';
    protected string $perm = 'pet';
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
