<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class LeadController extends CrudController {
    protected string $model = \App\Models\Master\Lead::class;
    protected string $routePrefix = 'crm.leads';
    protected string $viewPrefix = 'modules.crm.leads';
    protected string $title = 'Lead';
    protected string $perm = 'crm';
    protected array $columns = [
        'name' => 'Nama',
        'phone' => 'Telepon',
        'stage' => 'Tahap'
    ];
    protected array $fields = [
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'email','label'=>'Email','type'=>'email'],
        ['name'=>'stage','label'=>'Tahap','type'=>'select', 'options'=>['new'=>'Baru','contacted'=>'Dihubungi','qualified'=>'Qualified','lost'=>'Lost','converted'=>'Converted']],
        ['name'=>'lost_reason','label'=>'Alasan Lost','type'=>'text']
    ];
    protected array $rules = [
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['name', 'phone'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = null;
}
