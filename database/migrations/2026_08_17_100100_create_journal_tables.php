<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec §8.3: append-only journal. No updated_at, no soft deletes —
     * corrections happen through reversing entries only.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 20)->unique();
            $table->date('entry_date');
            $table->string('description_ar', 500);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('cohort_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoicing_entity_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('status', ['posted', 'reversed'])->default('posted');
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at');

            $table->index(['entry_date', 'status']);
            $table->index(['cohort_id', 'entry_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->bigInteger('debit_baisa')->default(0);
            $table->bigInteger('credit_baisa')->default(0);
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('memo_ar', 500)->nullable();
            $table->unsignedInteger('line_order')->default(0);
            $table->timestamp('created_at');

            $table->index(['account_id', 'cohort_id']);
            $table->index(['partner_id', 'account_id']);
            $table->index('cohort_id');
        });

        // العدّاد المتسلسل بلا فجوات — صف لكل سنة يُقفل داخل معاملة الترحيل
        Schema::create('journal_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journal_sequences');
    }
};
