<?php
namespace App\Http\Controllers\Org;
use App\Http\Controllers\CrudController;
class BranchController extends CrudController {
    protected string $model = \App\Models\Org\Branch::class;
    protected string $routePrefix = 'org.branches';
    protected string $viewPrefix = 'modules.org.branches';
    protected string $title = 'Cabang';
    protected string $perm = 'org';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama'
    ];
    protected array $fields = [
        ['name'=>'legal_entity_id','label'=>'Badan Usaha','type'=>'select_model', 'model'=>'App\\Models\\Org\\LegalEntity', 'option_label'=>'name'],
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'address','label'=>'Alamat','type'=>'textarea']
    ];
    protected array $rules = [
        'legal_entity_id' => 'required|exists:legal_entities,id',
        'code' => 'required|max:20',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = null;
}
