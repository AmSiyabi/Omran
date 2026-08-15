<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohort_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('session_number');
            $table->string('title_ar')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('venue_override_ar')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cohort_id', 'session_number']);
            $table->index(['cohort_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohort_sessions');
    }
};
