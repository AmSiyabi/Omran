<?php

use App\Models\Category;
use App\Models\Course;
use App\Support\ArabicSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transliterates Arabic titles to clean ASCII slugs', function () {
    $slug = ArabicSlug::generate('مقدمة في الذكاء الاصطناعي', 'courses');

    expect($slug)->toMatch('/^[a-z0-9\-]+$/')
        ->and($slug)->not->toBe('')
        ->and($slug)->not->toBe('item');
});

it('guarantees uniqueness with numeric suffixes', function () {
    $category = Category::factory()->create();

    $first = ArabicSlug::generate('دورة الفقه', 'courses');
    Course::factory()->create(['slug' => $first, 'category_id' => $category->id]);

    $second = ArabicSlug::generate('دورة الفقه', 'courses');
    Course::factory()->create(['slug' => $second, 'category_id' => $category->id]);

    $third = ArabicSlug::generate('دورة الفقه', 'courses');

    expect($second)->toBe($first.'-2')
        ->and($third)->toBe($first.'-3');
});

it('counts soft-deleted rows when checking uniqueness', function () {
    $category = Category::factory()->create();

    $first = ArabicSlug::generate('دورة محذوفة', 'courses');
    $course = Course::factory()->create(['slug' => $first, 'category_id' => $category->id]);
    $course->delete();

    expect(ArabicSlug::generate('دورة محذوفة', 'courses'))->toBe($first.'-2');
});

it('falls back safely for untransliterable input', function () {
    expect(ArabicSlug::generate('!!!', 'courses'))->toBe('item');
});
