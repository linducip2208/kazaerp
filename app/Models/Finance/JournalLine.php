<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class JournalLine extends Model {
    protected $table = 'journal_lines';
    protected $fillable = ['journal_id', 'coa_id', 'debit', 'credit', 'cost_center_id', 'note'];
    protected $casts = [];
}
