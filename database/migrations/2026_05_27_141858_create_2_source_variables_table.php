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
        Schema::create('source_variables', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('source_code');
            $table->string('label');
            $table->string('category')->index();
            $table->string('fuel_type')->nullable()->index();
            $table->boolean('is_clean')->default(false)->index();
            $table->string('unit')->default('MW');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source', 'source_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_variables');
    }
};
