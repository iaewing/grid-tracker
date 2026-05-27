<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('energy_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_variable_id')->constrained()->cascadeOnDelete();
            $table->dateTimeTz('observed_at')->index();
            $table->decimal('value', 12, 3);
            $table->string('unit')->default('MW');
            $table->string('source');
            $table->string('source_code');
            $table->string('freshness_status')->default('current')->index();
            $table->dateTimeTz('received_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'source_variable_id', 'observed_at', 'source']);
            $table->index(['region_id', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_observations');
    }
};
