<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->string('document_type'); // birth_certificate, previous_result, passport, etc.
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->json('extracted_data')->nullable(); // Saved JSON from OpenRouter
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_documents');
    }
};
