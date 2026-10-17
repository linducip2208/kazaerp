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

    // CRM-06: daftar penerima = pelanggan aktif, BUKAN opt-out, BUKAN hasil gabungan.
    // Menegakkan "opt-out diterapkan ke daftar kampanye".
    public function show($id) {
        $row = $this->baseQuery()->findOrFail($id);
        $u = auth()->user();
        $q = \App\Models\Master\Customer::where('is_active', true)
            ->where('marketing_opt_out', false)
            ->whereNull('merged_into_customer_id');
        if (! $u->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) {
            $ctx = session('erp_context', []);
            $eid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
            $q->where('legal_entity_id', $eid);
        }
        $recipients = $q->orderBy('name')->paginate(15, ['*'], 'recipients')->withQueryString();
        $excluded = \App\Models\Master\Customer::where('is_active', true)
            ->where('marketing_opt_out', true)->count();
        return view($this->viewPrefix . '.show', ['row' => $row, 'meta' => $this->meta(),
            'recipients' => $recipients, 'excludedOptOut' => $excluded]);
    }
}
