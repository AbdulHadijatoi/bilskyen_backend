<?php

namespace App\Services\Cms;

use App\Models\CmsMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsMediaService
{
    public function upload(UploadedFile $file, ?int $userId = null, ?string $altText = null): CmsMedia
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('cms-media', $filename, 'public');

        return CmsMedia::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'alt_text' => $altText,
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(CmsMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
