<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Signed, authorized, streamed from the private disk (spec §7.4).
     */
    public function show(int $document): StreamedResponse
    {
        // التوقيع تتحقق منه الوسيطة signed على المسار؛ هنا نتحقق من الصلاحية
        abort_unless(auth()->user()?->can('finance.view'), 403);

        $model = Document::query()->findOrFail($document);

        abort_unless(Storage::disk('local')->exists($model->file_path), 404);

        return Storage::disk('local')->response(
            $model->file_path,
            $model->original_filename,
            ['Content-Type' => $model->mime_type],
        );
    }
}
