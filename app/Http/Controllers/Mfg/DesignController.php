<?php
namespace App\Http\Controllers\Mfg;
use App\Http\Controllers\CrudController;
class DesignController extends CrudController {
    protected string $model = \App\Models\Mfg\Design::class;
    protected string $routePrefix = 'mfg.designs';
    protected string $viewPrefix = 'modules.mfg.designs';
    protected string $title = 'Desain';
    protected string $perm = 'mfg';
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
        'code' => 'required|max:30',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
}
