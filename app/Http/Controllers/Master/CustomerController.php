<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\CrudController;
class CustomerController extends CrudController {
    protected string $model = \App\Models\Master\Customer::class;
    protected string $routePrefix = 'crm.customers';
    protected string $viewPrefix = 'modules.crm.customers';
    protected string $title = 'Pelanggan';
    protected string $perm = 'crm';
    protected array $columns = [
        'code' => 'Kode',
        'name' => 'Nama',
        'phone' => 'Telepon',
        'promo' => 'Promo',
    ];
    protected array $fields = [
        ['name'=>'code','label'=>'Kode','type'=>'text'],
        ['name'=>'name','label'=>'Nama','type'=>'text'],
        ['name'=>'kind','label'=>'Jenis','type'=>'select', 'options'=>['retail'=>'Retail','reseller'=>'Reseller','corporate'=>'Corporate']],
        ['name'=>'tier','label'=>'Tier Harga','type'=>'select', 'options'=>['retail'=>'Retail','wholesale'=>'Wholesale','reseller'=>'Reseller']],
        ['name'=>'phone','label'=>'Telepon','type'=>'text'],
        ['name'=>'email','label'=>'Email','type'=>'email'],
        ['name'=>'marketing_opt_out','label'=>'Status promo','type'=>'select', 'options'=>['0'=>'Menerima promo','1'=>'Opt-out (tidak dikirimi promo)']],
    ];
    protected array $rules = [
        'code' => 'required|max:30',
        'name' => 'required|max:150',
        'marketing_opt_out' => 'nullable|in:0,1',
    ];
    protected array $searchable = ['code', 'name', 'phone'];
    protected ?string $entityField = 'legal_entity_id';
    protected ?string $branchField = 'branch_id';

    // CRM-05: gabungkan pelanggan duplikat. Histori TIDAK dipindah/dihapus: sumber
    // dinonaktifkan + tertaut ke induk; dokumen lama tetap menunjuk sumber aslinya.
    // Hanya petugas berwenang (perm crm.convert). Satu entitas, bukan diri sendiri.
    public function merge(\Illuminate\Http\Request $r, $id) {
        $r->validate(['target_id' => 'required|integer|min:1']);
        $me = auth()->user();
        if (! $me->hasPermission('crm.convert') && ! $me->hasRole('super_admin', 'holding_owner', 'executive', 'branch_manager')) {
            abort(403, 'Penggabungan pelanggan butuh wewenang khusus.');
        }
        try {
            $done = \Illuminate\Support\Facades\DB::transaction(function () use ($r, $id, $me) {
                $src = \App\Models\Master\Customer::lockForUpdate()->findOrFail((int) $id);
                $dst = \App\Models\Master\Customer::lockForUpdate()->findOrFail((int) $r->target_id);
                if (! $this->inScopeSr($src, $me) || ! $this->inScopeSr($dst, $me)) abort(404);
                if ($src->id === $dst->id) throw new \RuntimeException('Pelanggan tidak bisa digabung ke dirinya sendiri.');
                if ((int) $src->legal_entity_id !== (int) $dst->legal_entity_id) throw new \RuntimeException('Penggabungan hanya dalam satu entitas.');
                if ($src->merged_into_customer_id) throw new \RuntimeException('Pelanggan ini sudah digabung ke #' . $src->merged_into_customer_id . '.');
                if ($dst->merged_into_customer_id || ! $dst->is_active) throw new \RuntimeException('Pelanggan tujuan tidak aktif/sudah digabung.');
                $src->update(['is_active' => false, 'merged_into_customer_id' => $dst->id]);
                \App\Services\AuditService::log('merge', 'customers', $src->id, ['into' => $dst->id, 'by' => $me->id]);
                return $dst->id;
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
        return redirect()->route($this->routePrefix . '.show', $done)->with('ok', 'Pelanggan digabung; histori dipertahankan pada data asal.');
    }

    private function inScopeSr($row, $me): bool {
        if ($me->hasRole('super_admin', 'holding_owner', 'executive', 'auditor')) return true;
        $ctx = session('erp_context', []);
        $eid = $ctx['legal_entity_id'] ?? $me->legal_entity_id;
        return (int) $row->legal_entity_id === (int) $eid;
    }
}
