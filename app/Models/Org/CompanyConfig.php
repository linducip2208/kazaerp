<?php
namespace App\Models\Org;
use Illuminate\Database\Eloquent\Model;
class CompanyConfig extends Model {
    protected $table = 'company_configs';
    protected $fillable = ['legal_entity_id', 'key', 'value'];
    protected $casts = [];
}
