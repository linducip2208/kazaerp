<?php
namespace App\Http\Controllers\Marketing;
use App\Http\Controllers\CrudController;
class VoucherController extends CrudController {
    protected string $model = \App\Models\Marketing\Voucher::class;
    protected string $routePrefix = 'marketing.vouchers';
    protected string $viewPrefix = 'modules.marketing.vouchers';
    protected string $title = 'Voucher';
    protected string $perm = 'marketing';
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
