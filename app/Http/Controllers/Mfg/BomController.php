<?php
namespace App\Http\Controllers\Mfg;
use App\Http\Controllers\CrudController;
class BomController extends CrudController {
    protected string $model = \App\Models\Mfg\Bom::class;
    protected string $routePrefix = 'mfg.boms';
    protected string $viewPrefix = 'modules.mfg.boms';
    protected string $title = 'BOM';
    protected string $perm = 'mfg';
    protected array $columns = [
        'version' => 'Versi',
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
