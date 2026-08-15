<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohort_deliverers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->enum('deliverer_type', ['partner', 'external']);
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('share_weight', 5, 2);
            $table->timestamps();

            $table->index('cohort_id');
        });

        // Spec §7.2: exactly one of partner_id / instructor_id, matching type.
        // Enforced in the model observer too; this is the DB-level backstop.
        // (MySQL only — the SQLite test database relies on the observer.)
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE cohort_deliverers
                ADD CONSTRAINT chk_deliverer_identity CHECK (
                    (deliverer_type = 'partner' AND partner_id IS NOT NULL AND instructor_id IS NULL)
                    OR
                    (deliverer_type = 'external' AND instructor_id IS NOT NULL AND partner_id IS NULL)
                )
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cohort_deliverers');
    }
};
