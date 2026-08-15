<?php

namespace App\Models;

use App\Enums\CourseLevel;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'slug', 'category_id', 'title_ar', 'title_en', 'summary_ar', 'summary_en',
    'description_ar', 'description_en', 'outcomes_ar', 'outcomes_en',
    'target_audience_ar', 'prerequisites_ar', 'duration_hours', 'level',
    'is_published', 'published_at', 'meta_title_ar', 'meta_description_ar',
])]
class Course extends Model implements HasMedia
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'outcomes_ar' => 'array',
        'outcomes_en' => 'array',
        'duration_hours' => 'decimal:1',
        'level' => CourseLevel::class,
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Cohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }

    /**
     * Spec Phase 2: automatic AVIF/WebP conversion with responsive variants.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ([480, 960, 1440] as $width) {
            $this->addMediaConversion("avif-{$width}")
                ->nonQueued()
                ->format('avif')
                ->width($width);

            $this->addMediaConversion("webp-{$width}")
                ->nonQueued()
                ->format('webp')
                ->width($width);
        }

        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->format('webp')
            ->fit(Fit::Crop, 200, 200);
    }
}
