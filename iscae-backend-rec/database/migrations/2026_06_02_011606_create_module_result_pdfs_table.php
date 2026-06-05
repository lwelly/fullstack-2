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
        // database/migrations/xxxx_create_module_result_pdfs_table.php
Schema::create('module_result_pdfs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('module_id')->constrained()->cascadeOnDelete();
    $table->foreignId('semestre_id')->constrained()->cascadeOnDelete();
    $table->string('academic_year', 9);
    $table->enum('type', ['controle', 'examen', 'rattrapage']);
    $table->string('original_name');
    $table->string('file_path');
    $table->string('disk')->default('public');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->unique(['module_id', 'semestre_id', 'academic_year', 'type'], 'uq_module_result_pdf');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_result_pdfs');
    }
};
