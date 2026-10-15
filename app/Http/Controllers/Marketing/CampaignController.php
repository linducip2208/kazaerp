<?php
namespace App\Http\Controllers\Marketing;
use App\Http\Controllers\CrudController;
class CampaignController extends CrudController {
    protected string $model = \App\Models\Marketing\Campaign::class;
    protected string $routePrefix = 'marketing.campaigns';
    protected string $viewPrefix = 'modules.marketing.campaigns';
    protected string $title = 'Kampanye';
    protected string $perm = 'marketing';
    protected array $columns = [
        'name' => 'Nama',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'name','label'=>'Nama','type'=>'text']
    ];
    protected array $rules = [
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['name'];
    protected ?string $entityField = null;
}
