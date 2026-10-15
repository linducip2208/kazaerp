<?php
namespace App\Models\Mfg;
use Illuminate\Database\Eloquent\Model;
class DesignVersion extends Model {
    protected $table = 'design_versions';
    protected $fillable = ['design_id', 'version', 'file_path', 'print_placement', 'print_size', 'status', 'approved_by'];
    protected $casts = [];
}
