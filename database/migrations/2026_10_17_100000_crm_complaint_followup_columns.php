<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P1 CRM-03 (follow-up) + RET-01/02/05 (tiket komplain): kolom aditif saja.
return new class extends Migration {
    public function up(): void {
        Schema::table('complaints', function (Blueprint $t) {
            $t->string('urgency', 20)->default('normal')->after('status');
            $t->string('decision', 30)->nullable()->after('urgency');
            $t->string('related_type', 80)->nullable()->after('decision');
            $t->unsignedBigInteger('related_id')->nullable()->after('related_type');
            $t->foreignId('assignee_id')->nullable()->after('related_id')->constrained('users')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->after('assignee_id')->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->after('resolution')->constrained('users')->nullOnDelete();
            $t->dateTime('resolved_at')->nullable()->after('resolved_by');
            $t->index(['legal_entity_id', 'status']);
            $t->index(['related_type', 'related_id']);
        });
        Schema::table('followups', function (Blueprint $t) {
            $t->foreignId('legal_entity_id')->nullable()->after('id')->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $t->index(['legal_entity_id', 'status']);
            $t->index(['assigned_to', 'status']);
        });
    }
    public function down(): void {
        Schema::table('followups', function (Blueprint $t) {
            $t->dropForeign(['legal_entity_id']); $t->dropForeign(['created_by']);
            $t->dropColumn(['legal_entity_id', 'created_by']);
        });
        Schema::table('complaints', function (Blueprint $t) {
            $t->dropForeign(['assignee_id']); $t->dropForeign(['created_by']); $t->dropForeign(['resolved_by']);
            $t->dropColumn(['urgency', 'decision', 'related_type', 'related_id', 'assignee_id', 'created_by', 'resolved_by', 'resolved_at']);
        });
    }
};
