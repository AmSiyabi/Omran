<?php

namespace App\Finance;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Spec §7.4/§10: documents live on a private disk, never under the web
 * root's public reach, and are served exclusively through the authorizing
 * controller with temporary signed URLs.
 */
class DocumentStorer
{
    public function attach(Model $documentable, UploadedFile $file, string $type, int $uploadedBy, ?string $notes = null): Document
    {
        $path = $file->storeAs(
            'documents/'.now()->format('Y/m'),
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local',
        );

        return Document::query()->create([
            'documentable_type' => $documentable->getMorphClass(),
            'documentable_id' => $documentable->getKey(),
            'type' => $type,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $uploadedBy,
            'notes' => $notes,
        ]);
    }

    public function temporaryUrl(Document $document): string
    {
        return URL::temporarySignedRoute(
            'admin.documents.show',
            now()->addMinutes(10),
            ['document' => $document->id],
        );
    }

    public function delete(Document $document): void
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
    }
}
