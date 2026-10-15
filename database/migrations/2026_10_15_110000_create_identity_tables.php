<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('roles', function (Blueprint $t) {
            $t->id(); $t->string('name', 60)->unique(); $t->string('label', 100);
            $t->boolean('is_system')->default(false); $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id(); $t->string('name', 100)->unique(); $t->string('group', 60);
            $t->string('label', 150); $t->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->primary(['role_id', 'user_id']);
        });
        Schema::create('user_scopes', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('scope_type', 30); $t->unsignedBigInteger('scope_id');
            $t->timestamps(); $t->unique(['user_id', 'scope_type', 'scope_id']);
            $t->index(['scope_type', 'scope_id']);
        });
        Schema::create('login_activities', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email', 150)->nullable(); $t->string('ip', 45)->nullable();
            $t->string('agent', 255)->nullable(); $t->boolean('success')->default(false);
            $t->timestamp('created_at')->nullable(); $t->index(['user_id', 'created_at']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 40); $t->string('entity', 80); $t->unsignedBigInteger('record_id');
            $t->text('changes')->nullable(); $t->string('ip', 45)->nullable();
            $t->timestamps(); $t->index(['entity', 'record_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_active')->default(true)->after('password');
            $t->foreignId('holding_id')->nullable()->after('is_active')->constrained('holdings')->nullOnDelete();
            $t->foreignId('legal_entity_id')->nullable()->after('holding_id')->constrained('legal_entities')->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->after('legal_entity_id')->constrained('branches')->nullOnDelete();
            $t->timestamp('last_login_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('branch_id');
            $t->dropConstrainedForeignId('legal_entity_id');
            $t->dropConstrainedForeignId('holding_id');
            $t->dropColumn(['is_active', 'last_login_at']);
        });
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_activities');
        Schema::dropIfExists('user_scopes');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
