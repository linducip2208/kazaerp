<?php
namespace App\Models\Finance;
use Illuminate\Database\Eloquent\Model;
class Journal extends Model {
    protected $table = 'journals';
    protected $fillable = ['legal_entity_id', 'branch_id', 'date', 'number', 'description', 'source_type', 'source_id', 'total_debit', 'total_credit', 'status', 'idempotency_key', 'created_by'];
    protected $casts = [];

    public function lines() { return $this->hasMany(\App\Models\Finance\JournalLine::class, 'journal_id'); }
}
