<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('display_name_ar');
            $table->string('display_name_en')->nullable();
            $table->text('bio_ar');
            $table->text('bio_en')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('ownership_percent', 5, 2)->default(50.00);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('public_profile_visible')->default(true);
            // Encrypted casts produce long ciphertext — text, not string
            $table->text('bank_name')->nullable();
            $table->text('bank_account')->nullable();
            $table->text('civil_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
