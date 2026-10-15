<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {

        Schema::create('pet_owners', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->string('name'); $t->string('phone', 30)->nullable(); $t->string('email', 120)->nullable();
            $t->text('address')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('pets', function (Blueprint $t) {
            $t->id(); $t->foreignId('pet_owner_id')->constrained('pet_owners')->cascadeOnDelete();
            $t->string('name'); $t->string('species', 30)->default('cat'); $t->string('breed', 60)->nullable();
            $t->string('gender', 10)->nullable(); $t->date('birth_date')->nullable();
            $t->decimal('weight', 8, 2)->nullable(); $t->string('color', 40)->nullable();
            $t->text('notes')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('vet_appointments', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->foreignId('pet_id')->constrained('pets');
            $t->dateTime('scheduled_at'); $t->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('scheduled'); $t->text('complaint')->nullable(); $t->timestamps();
        });
        Schema::create('consultations', function (Blueprint $t) {
            $t->id(); $t->foreignId('vet_appointment_id')->constrained('vet_appointments')->cascadeOnDelete();
            $t->foreignId('pet_id')->constrained('pets');
            $t->text('clinical_notes')->nullable(); $t->string('diagnosis', 255)->nullable();
            $t->text('treatment_plan')->nullable();
            $t->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('treatments', function (Blueprint $t) {
            $t->id(); $t->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $t->string('action', 150); $t->decimal('qty', 10, 2)->default(1);
            $t->decimal('price', 18, 2)->default(0); $t->timestamps();
        });
        Schema::create('grooming_services', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('code', 30); $t->string('name'); $t->decimal('price', 18, 2);
            $t->integer('duration_min')->default(60); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['legal_entity_id', 'code']);
        });
        Schema::create('grooming_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('pet_id')->constrained('pets');
            $t->foreignId('grooming_service_id')->constrained('grooming_services');
            $t->foreignId('groomer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 20)->default('queued'); $t->text('checklist')->nullable();
            $t->text('before_condition')->nullable(); $t->decimal('grand_total', 18, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('boarding_rooms', function (Blueprint $t) {
            $t->id(); $t->foreignId('branch_id')->constrained('branches');
            $t->string('code', 20); $t->string('name'); $t->integer('capacity')->default(1);
            $t->decimal('rate', 18, 2)->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
            $t->unique(['branch_id', 'code']);
        });
        Schema::create('boarding_reservations', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $t->string('number', 60)->unique();
            $t->foreignId('pet_id')->constrained('pets');
            $t->foreignId('boarding_room_id')->constrained('boarding_rooms');
            $t->date('check_in'); $t->date('check_out');
            $t->text('feeding')->nullable(); $t->text('medication_note')->nullable();
            $t->decimal('deposit', 18, 2)->default(0); $t->decimal('grand_total', 18, 2)->default(0);
            $t->string('status', 20)->default('reserved'); $t->timestamps();
        });
        Schema::create('boarding_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('boarding_reservation_id')->constrained('boarding_reservations')->cascadeOnDelete();
            $t->date('date'); $t->text('activity'); $t->string('incident', 255)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('plate', 20)->unique(); $t->string('name'); $t->integer('capacity')->default(4);
            $t->string('status', 20)->default('active'); $t->timestamps();
        });
        Schema::create('transport_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('legal_entity_id')->constrained('legal_entities');
            $t->string('number', 60)->unique(); $t->date('date');
            $t->foreignId('pet_id')->constrained('pets');
            $t->string('pickup_address', 255); $t->string('delivery_address', 255);
            $t->dateTime('scheduled_at'); $t->decimal('price', 18, 2)->default(0);
            $t->string('status', 20)->default('ordered'); $t->timestamps();
        });
        Schema::create('trips', function (Blueprint $t) {
            $t->id(); $t->foreignId('transport_order_id')->constrained('transport_orders')->cascadeOnDelete();
            $t->foreignId('vehicle_id')->constrained('vehicles');
            $t->foreignId('driver_id')->constrained('users');
            $t->dateTime('depart_at')->nullable(); $t->dateTime('arrive_at')->nullable();
            $t->string('status', 20)->default('assigned');
            $t->decimal('fuel_cost', 18, 2)->default(0); $t->decimal('toll_cost', 18, 2)->default(0);
            $t->text('handover_proof')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pet_owners');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('vet_appointments');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('grooming_services');
        Schema::dropIfExists('grooming_orders');
        Schema::dropIfExists('boarding_rooms');
        Schema::dropIfExists('boarding_reservations');
        Schema::dropIfExists('boarding_logs');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('transport_orders');
        Schema::dropIfExists('trips');
    }
};
