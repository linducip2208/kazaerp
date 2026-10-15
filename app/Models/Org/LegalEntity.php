<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class LegalEntity extends Model {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = 'legal_entities';
    protected $fillable = ['holding_id', 'code', 'name', 'npwp', 'address', 'phone', 'email', 'is_active'];
    protected $casts = [];
}
