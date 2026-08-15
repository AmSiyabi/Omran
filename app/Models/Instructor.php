<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name_ar', 'name_en', 'email', 'phone', 'bio_ar', 'bio_en',
    'photo_path', 'specialization_ar', 'is_public', 'notes',
])]
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'is_public' => 'boolean',
    ];
}
