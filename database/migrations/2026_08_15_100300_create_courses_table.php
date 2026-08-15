<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('summary_ar', 500);
            $table->string('summary_en', 500)->nullable();
            $table->longText('description_ar');
            $table->longText('description_en')->nullable();
            $table->json('outcomes_ar');
            $table->json('outcomes_en')->nullable();
            $table->text('target_audience_ar')->nullable();
            $table->text('prerequisites_ar')->nullable();
            $table->decimal('duration_hours', 5, 1)->default(0);
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_description_ar', 500)->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
