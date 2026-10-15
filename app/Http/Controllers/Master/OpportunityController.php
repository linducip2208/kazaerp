<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class OpportunityController extends CrudController {
    protected string $model = \App\Models\Master\Opportunity::class;
    protected string $routePrefix = 'crm.opportunities';
    protected string $viewPrefix = 'modules.crm.opportunities';
    protected string $title = 'Opportunity';
    protected string $perm = 'crm';
    protected array $columns = [
        'title' => 'Judul',
        'amount' => 'Nilai',
        'stage' => 'Tahap'
    ];
    protected array $fields = [
        ['name'=>'title','label'=>'Judul','type'=>'text'],
        ['name'=>'amount','label'=>'Nilai','type'=>'number'],
        ['name'=>'stage','label'=>'Tahap','type'=>'select', 'options'=>['prospect'=>'Prospek','negotiation'=>'Negosiasi','won'=>'Menang','lost'=>'Kalah']],
        ['name'=>'probability','label'=>'Probabilitas %','type'=>'number']
    ];
    protected array $rules = [
        'title' => 'required|max:150',
        'amount' => 'required|numeric|min:0'
    ];
    protected array $searchable = ['title'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = null;
}
