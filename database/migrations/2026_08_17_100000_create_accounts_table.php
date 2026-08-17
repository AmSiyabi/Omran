<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'direct_cost', 'operating_expense']);
            // حسابات الشركاء (301x/302x/501x) تُنسب لشريك بعينه
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
