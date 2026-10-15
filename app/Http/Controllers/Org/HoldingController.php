<?php
namespace App\Http\Controllers\Org;
use App\Http\Controllers\CrudController;
class HoldingController extends CrudController {
    protected string $model = \App\Models\Org\Holding::class;
    protected string $routePrefix = 'org.holdings';
    protected string $viewPrefix = 'modules.org.holdings';
    protected string $title = 'Holding';
    protected string $perm = 'org';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'is_active' => 'Aktif'
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'address','label'=>'Alamat','type'=>'textarea']
    ];
    protected array $rules = [
        'code' => 'required|max:20',
        'name' => 'required|max:150'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
    protected ?string $branchField = null;
}
