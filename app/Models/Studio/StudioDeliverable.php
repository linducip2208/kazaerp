<?php
namespace App\Models\Studio;
use Illuminate\Database\Eloquent\Model;
class StudioDeliverable extends Model {
    protected $table = 'studio_deliverables';
    protected $fillable = ['studio_project_id', 'title', 'version', 'file_path', 'status'];
    protected $casts = [];
}
