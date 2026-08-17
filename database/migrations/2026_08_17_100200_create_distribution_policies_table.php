<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name_ar');
            $table->text('description_ar')->nullable();
            $table->decimal('deliverer_share_percent', 5, 2)->nullable();
            $table->enum('external_fee_mode', ['none', 'fixed'])->default('none');
            $table->boolean('deduct_direct_costs_first')->default(true);
            $table->enum('center_split_mode', ['by_ownership', 'custom'])->default('by_ownership');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // D-020 fulfilled: the FK deferred since Phase 2, plus the fixed fee
        // the §8.5 algorithm reads for EXTERNAL_TRAINER cohorts
        Schema::table('cohorts', function (Blueprint $table) {
            $table->bigInteger('external_fee_baisa')->nullable()->after('distribution_policy_id');
            $table->foreign('distribution_policy_id')
                ->references('id')
                ->on('distribution_policies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropForeign(['distribution_policy_id']);
            $table->dropColumn('external_fee_baisa');
        });

        Schema::dropIfExists('distribution_policies');
    }
};
