<?php

namespace App\Modules\CaseManagement\Services;

use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaseDocumentService
{
    protected string $disk = 'public';
    protected string $path = 'case-documents';

    public function upload(CaseModel $case, UploadedFile $file, ?string $title = null, ?string $details = null): CaseDocument
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
            'title' => $title,
            'details' => $details,
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'version' => $version,
        ]);
    }

    public function softDelete(CaseDocument $document): void
    {
        $document->deleted_by = auth()->id();
        $document->save();
        $document->delete();
    }

    public function restore(CaseDocument $document): void
    {
        $document->restore();
        $document->deleted_by = null;
        $document->save();
    }

    public function getStoragePath(CaseDocument $document): string
    {
        return Storage::disk($this->disk)->path($document->file_path);
    }

    public function exists(CaseDocument $document): bool
    {
        return Storage::disk($this->disk)->exists($document->file_path);
    }

    public function purge(CaseDocument $document): void
    {
        if (Storage::disk($this->disk)->exists($document->file_path)) {
            Storage::disk($this->disk)->delete($document->file_path);
        }

        $document->forceDelete();
    }
}
