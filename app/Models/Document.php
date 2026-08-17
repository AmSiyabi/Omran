<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'documentable_type', 'documentable_id', 'type', 'file_path',
    'original_filename', 'mime_type', 'size_bytes', 'uploaded_by', 'notes',
])]
class Document extends Model
{
    use SoftDeletes;

    /** @var array<string, string> */
    protected $casts = [
        'type' => DocumentType::class,
        'size_bytes' => 'integer',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
