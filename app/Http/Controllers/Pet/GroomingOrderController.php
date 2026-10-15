<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class GroomingOrderController extends CrudController {
    protected string $model = \App\Models\Pet\GroomingOrder::class;
    protected string $routePrefix = 'pet.grooming';
    protected string $viewPrefix = 'modules.pet.grooming';
    protected string $title = 'Grooming';
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
