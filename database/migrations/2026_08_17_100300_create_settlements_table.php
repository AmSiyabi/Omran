<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 20)->unique();
            $table->enum('type', ['cohort', 'monthly']);
            $table->foreignId('cohort_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->bigInteger('gross_revenue_baisa')->default(0);
            $table->bigInteger('direct_costs_baisa')->default(0);
            $table->bigInteger('net_distributable_baisa')->default(0);
            $table->bigInteger('deliverer_total_baisa')->default(0);
            $table->bigInteger('center_share_baisa')->default(0);
            $table->bigInteger('center_opex_allocated_baisa')->default(0);
            $table->bigInteger('distributable_profit_baisa')->default(0);
            $table->enum('status', ['draft', 'confirmed', 'posted', 'reversed'])->default('draft');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('computed_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes_ar')->nullable();
            // الحساب كاملاً مجمداً لحظة التأكيد — ما يجعل النظام قابلاً للتدقيق
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
            $table->index('cohort_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
