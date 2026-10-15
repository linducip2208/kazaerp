<?php
namespace App\Http\Controllers\Org;
use App\Http\Controllers\CrudController;
class LegalEntityController extends CrudController {
    protected string $model = \App\Models\Org\LegalEntity::class;
    protected string $routePrefix = 'org.entities';
    protected string $viewPrefix = 'modules.org.entities';
    protected string $title = 'Badan Usaha';
    protected string $perm = 'org';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'npwp' => 'NPWP'
    ];
    protected array $fields = [
        ['name'=>'holding_id','label'=>'Holding','type'=>'select_model', 'model'=>'App\\Models\\Org\\Holding', 'option_label'=>'name'],
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'npwp','label'=>'NPWP','type'=>'text'],
        ['name'=>'address','label'=>'Alamat','type'=>'textarea']
    ];
    protected array $rules = [
        'holding_id' => 'required|exists:holdings,id',
        'code' => 'required|max:20',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;
}
