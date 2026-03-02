<?php

namespace App\Modules\CaseManagement\Services;

use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaseDocumentService
{
    protected string $disk = 'local';
    protected string $path = 'case-documents';

    public function upload(CaseModel $case, UploadedFile $file): CaseDocument
    {
        $name = Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs($this->path . '/' . $case->id, $name, $this->disk);

        $originalName = $file->getClientOriginalName();
        $version = CaseDocument::where('case_id', $case->id)
            ->where('original_name', $originalName)
            ->max('version') + 1;

        return CaseDocument::create([
            'case_id' => $case->id,
            'file_path' => $path,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'version' => $version,
        ]);
    }

    public function getStoragePath(CaseDocument $document): string
    {
        return Storage::disk($this->disk)->path($document->file_path);
    }

    public function exists(CaseDocument $document): bool
    {
        return Storage::disk($this->disk)->exists($document->file_path);
    }
}
