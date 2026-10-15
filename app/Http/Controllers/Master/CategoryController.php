<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class CategoryController extends CrudController {
    protected string $model = \App\Models\Master\Category::class;
    protected string $routePrefix = 'master.categories';
    protected string $viewPrefix = 'modules.master.categories';
    protected string $title = 'Kategori';
    protected string $perm = 'crm';
    protected array $columns = [
        'kind' => 'Jenis',
        'code' => 'Kode',
        'name' => 'Nama'
    ];
    protected array $fields = [
        ['name'=>'kind','label'=>'Jenis','type'=>'select', 'options'=>['product'=>'Produk','service'=>'Jasa']],
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text']
    ];
    protected array $rules = [
        'code' => 'required|max:30',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;
}
