<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->json('response_data')->nullable(); // Full API response
            $table->json('extracted_fields')->nullable(); // Fields parsed from the response
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status'); // Success, Failed
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_logs');
    }
};
