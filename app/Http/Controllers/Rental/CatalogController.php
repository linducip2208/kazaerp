<?php
namespace App\Http\Controllers\Rental;
use App\Http\Controllers\CrudController;
class CatalogController extends CrudController {
    protected string $model = \App\Models\Rental\RentalCatalog::class;
    protected string $routePrefix = 'rental.catalogs';
    protected string $viewPrefix = 'modules.rental.catalogs';
    protected string $title = 'Katalog Sewa';
    protected string $perm = 'rental';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama'
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text']
    ];
    protected array $rules = [
        'code' => 'required',
        'name' => 'required'
    ];
    protected array $searchable = ['code', 'name'];
    protected ?string $entityField = null;
}
