<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('title_override_ar')->nullable();
            $table->enum('delivery_mode', ['onsite', 'online', 'hybrid'])->default('onsite');
            $table->string('venue_ar')->nullable();
            $table->string('venue_url')->nullable();
            $table->string('city_ar')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone')->default('Asia/Muscat');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('seats_taken')->default(0);
            $table->bigInteger('price_baisa')->default(0);
            $table->boolean('is_free')->default(false);
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoicing_entity_id')->constrained()->restrictOnDelete();
            // FK deferred to Phase 5 when distribution_policies exists (D-020)
            $table->unsignedBigInteger('distribution_policy_id')->nullable();
            $table->enum('status', ['draft', 'announced', 'open', 'closed', 'delivered', 'settled', 'cancelled'])->default('draft');
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
            $table->index(['course_id', 'starts_at']);
            $table->index('client_id');
            $table->index('distribution_policy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
