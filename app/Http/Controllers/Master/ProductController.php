<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class ProductController extends CrudController {
    protected string $model = \App\Models\Master\Product::class;
    protected string $routePrefix = 'master.products';
    protected string $viewPrefix = 'modules.master.products';
    protected string $title = 'Produk';
    protected string $perm = 'crm';
    protected array $columns = [
        'sku' => 'SKU',
        'name' => 'Nama',
        'sell_price' => 'Harga'
    ];
    protected array $fields = [
        ['name'=>'sku','label'=>'SKU','type'=>'text'],
        ['name'=>'barcode','label'=>'Barcode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'kind','label'=>'Jenis','type'=>'select', 'options'=>['goods'=>'Barang','service'=>'Jasa','package'=>'Paket']],
        ['name'=>'category_id','label'=>'Kategori','type'=>'select_model', 'model'=>'App\\Models\\Master\\Category', 'option_label'=>'name'],
        ['name'=>'cost_price','label'=>'HPP/Modal','type'=>'number'],
        ['name'=>'sell_price','label'=>'Harga Jual','type'=>'number'],
        ['name'=>'wholesale_price','label'=>'Harga Grosir','type'=>'number'],
        ['name'=>'reseller_price','label'=>'Harga Reseller','type'=>'number']
    ];
    protected array $rules = [
        'sku' => 'required|max:40',
        'name' => 'required|max:150',
        'sell_price' => 'required|numeric|min:0'
    ];
    protected array $searchable = ['sku', 'name', 'barcode'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = null;
}
