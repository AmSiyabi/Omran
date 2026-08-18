<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec §11.2: dashboards read pre-computed summaries, never aggregate
     * the journal live. Refreshed by a queued job on journal write.
     */
    public function up(): void
    {
        Schema::create('cohort_financial_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('gross_revenue_baisa')->default(0);
            $table->bigInteger('collected_baisa')->default(0);
            $table->bigInteger('receivable_baisa')->default(0);
            $table->bigInteger('direct_costs_baisa')->default(0);
            $table->bigInteger('deliverer_share_baisa')->default(0);
            $table->bigInteger('center_share_baisa')->default(0);
            $table->bigInteger('net_result_baisa')->default(0);
            // النسبة بالبسنت المئوي الصحيح (basis points) — لا كسور عائمة
            $table->integer('margin_basis_points')->default(0);
            $table->boolean('is_settled')->default(false);
            $table->timestamp('refreshed_at');
            $table->timestamps();
        });

        // spec Phase 6: VAT treatment on every revenue line — default standard
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->enum('vat_treatment', ['standard', 'zero_rated', 'exempt', 'out_of_scope'])
                ->nullable()
                ->after('memo_ar');
        });

        // backfill existing revenue lines via query builder (bypasses the
        // append-only Eloquent observer deliberately — schema backfill, not
        // a business mutation)
        DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('accounts.code', 'like', '4%')
            ->update(['journal_lines.vat_treatment' => 'standard']);
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropColumn('vat_treatment');
        });

        Schema::dropIfExists('cohort_financial_summaries');
    }
};
