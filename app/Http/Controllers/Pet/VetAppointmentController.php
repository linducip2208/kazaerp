<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class VetAppointmentController extends CrudController {
    protected string $model = \App\Models\Pet\VetAppointment::class;
    protected string $routePrefix = 'pet.vet';
    protected string $viewPrefix = 'modules.pet.vet';
    protected string $title = 'Janji Dokter Hewan';
    protected string $perm = 'pet';
    protected array $columns = [
        'scheduled_at' => 'Jadwal',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'status','label'=>'Status','type'=>'text']
    ];
    protected array $rules = [
        
    ];
    protected array $searchable = ['status'];
    protected ?string $entityField = null;
}
