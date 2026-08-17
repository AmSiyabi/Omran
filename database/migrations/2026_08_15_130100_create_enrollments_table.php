<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name_ar');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('organization_ar')->nullable();
            $table->string('job_title_ar')->nullable();
            $table->enum('status', ['pending', 'approved', 'confirmed', 'waitlisted', 'cancelled', 'attended', 'no_show'])->default('pending');
            $table->bigInteger('amount_due_baisa')->default(0);
            $table->bigInteger('amount_paid_baisa')->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'waived'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamp('enrolled_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cohort_id', 'email']);
            $table->index(['cohort_id', 'status']);
            $table->index(['cohort_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
