<?php
namespace App\Models\Master;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'customers';
    protected $fillable = ['legal_entity_id', 'branch_id', 'code', 'name', 'kind', 'email', 'phone', 'tier', 'sales_owner_id', 'is_active', 'marketing_opt_out', 'merged_into_customer_id'];
    protected $casts = ['marketing_opt_out' => 'boolean'];
    // CRM-06: label ramah untuk daftar. CRM-05: pelanggan yang digabung tetap ada (histori), nonaktif + tertaut ke induk.
    public function getPromoAttribute(): string { return $this->marketing_opt_out ? 'Opt-out' : 'Aktif'; }
    public function mergedInto() { return $this->belongsTo(self::class, 'merged_into_customer_id'); }
}
