<?php
namespace App\Http\Controllers\Studio;
use App\Http\Controllers\CrudController;
class ProjectController extends CrudController {
    protected string $model = \App\Models\Studio\StudioProject::class;
    protected string $routePrefix = 'studio.projects';
    protected string $viewPrefix = 'modules.studio.projects';
    protected string $title = 'Proyek Studio';
    protected string $perm = 'studio';
    protected array $columns = [
        'title' => 'Judul',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'title','label'=>'Judul','type'=>'text']
    ];
    protected array $rules = [
        'title' => 'required|max:150'
    ];
    protected array $searchable = ['title'];
    protected ?string $entityField = null;
}
