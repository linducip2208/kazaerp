<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transport_positions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $t->decimal('lat', 10, 7);
            $t->decimal('lng', 10, 7);
            $t->decimal('speed_kmh', 6, 2)->nullable();
            $t->dateTime('recorded_at');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['trip_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_positions');
    }
};
