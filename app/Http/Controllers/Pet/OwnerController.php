<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class OwnerController extends CrudController {
    protected string $model = \App\Models\Pet\PetOwner::class;
    protected string $routePrefix = 'pet.owners';
    protected string $viewPrefix = 'modules.pet.owners';
    protected string $title = 'Pemilik Hewan';
    protected string $perm = 'pet';
    protected array $columns = [
        'name' => 'Nama',
        'phone' => 'Telepon'
    ];
    protected array $fields = [
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text']
    ];
    protected array $rules = [
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['name', 'phone'];
    protected ?string $entityField = null;
}
