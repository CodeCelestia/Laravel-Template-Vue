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
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('code', 50)->nullable()->unique();
            $table->string('type', 30)->default('municipality'); // 'city' or 'municipality'
            $table->string('district', 50)->default('District 2')->index(); // 'District 1' or 'District 2'
            $table->string('zip_code', 10)->nullable();
            $table->string('location_description')->nullable(); // e.g. "Capitol Compound, Surigao City" or "Municipal Hall, Poblacion"
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
