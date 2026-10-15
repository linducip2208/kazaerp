<?php
namespace App\Http\Controllers\Pet;
use App\Http\Controllers\CrudController;
class PetController extends CrudController {
    protected string $model = \App\Models\Pet\Pet::class;
    protected string $routePrefix = 'pet.pets';
    protected string $viewPrefix = 'modules.pet.pets';
    protected string $title = 'Hewan';
    protected string $perm = 'pet';
    protected array $columns = [
        'name' => 'Nama',
        'species' => 'Spesies'
    ];
    protected array $fields = [
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'species','label'=>'Spesies','type'=>'text']
    ];
    protected array $rules = [
        'name' => 'required|max:100'
    ];
    protected array $searchable = ['name'];
    protected ?string $entityField = null;
}
