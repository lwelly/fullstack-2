<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamation_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reclamation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attachment_id')->nullable()->constrained('reclamation_attachments')->nullOnDelete();

            // Extraction PDF
            $table->longText('pdf_extracted_text')->nullable();
            $table->boolean('pdf_parsed_successfully')->default(false);
            $table->string('pdf_parse_error')->nullable();

            // Prompt OpenAI
            $table->longText('prompt_sent')->nullable();

            // Réponse brute OpenAI
            $table->longText('raw_response')->nullable();

            // Décision structurée
            $table->enum('decision', ['founded', 'unfounded', 'uncertain'])->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00 à 1.00
            $table->text('explanation')->nullable();
            $table->text('recommendation')->nullable();

            // Comparaison des notes
            $table->decimal('note_from_pdf', 5, 2)->nullable();
            $table->decimal('note_from_db', 5, 2)->nullable();
            $table->decimal('note_claimed', 5, 2)->nullable();
            $table->boolean('note_discrepancy_found')->default(false);
            $table->text('discrepancy_details')->nullable();

            // Métadonnées d'exécution
            $table->string('openai_model')->default('gpt-4o');
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->integer('processing_time_ms')->nullable();

            // Qui a déclenché l'analyse
            $table->foreignId('analyzed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Une seule analyse active par réclamation
            $table->unique('reclamation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamation_ai_analyses');
    }
};