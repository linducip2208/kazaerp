<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class BoardingReservationController extends CrudController {
    protected string $model = \App\Models\Pet\BoardingReservation::class;
    protected string $routePrefix = 'pet.boarding';
    protected string $viewPrefix = 'modules.pet.boarding';
    protected string $title = 'Boarding';
    protected string $perm = 'pet';
    protected array $columns = [
        'number' => 'Nomor',
        'check_in' => 'Masuk',
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
