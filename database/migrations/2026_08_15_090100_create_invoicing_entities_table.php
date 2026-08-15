<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoicing_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('type', ['individual', 'establishment', 'llc', 'other']);
            $table->string('cr_number')->nullable();
            $table->string('tax_card_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->date('vat_registered_from')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoicing_entities');
    }
};
