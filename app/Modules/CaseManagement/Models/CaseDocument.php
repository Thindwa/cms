<?php

namespace App\Modules\CaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CaseDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'case_documents';

    protected $fillable = [
        'case_id',
        'file_path',
        'original_name',
        'title',
        'details',
        'mime_type',
        'uploaded_by',
        'deleted_by',
        'version',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getDisplayTypeAttribute(): string
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = strtolower((string) pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        $byMime = [
            'application/pdf' => 'PDF',
            'application/msword' => 'Word (.doc)',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word (.docx)',
            'application/vnd.ms-excel' => 'Excel (.xls)',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel (.xlsx)',
            'text/csv' => 'CSV',
            'image/jpeg' => 'Image (JPEG)',
            'image/jpg' => 'Image (JPG)',
            'image/png' => 'Image (PNG)',
            'image/gif' => 'Image (GIF)',
        ];

        if (isset($byMime[$mime])) {
            return $byMime[$mime];
        }

        $byExt = [
            'pdf' => 'PDF',
            'doc' => 'Word (.doc)',
            'docx' => 'Word (.docx)',
            'xls' => 'Excel (.xls)',
            'xlsx' => 'Excel (.xlsx)',
            'csv' => 'CSV',
            'jpeg' => 'Image (JPEG)',
            'jpg' => 'Image (JPG)',
            'png' => 'Image (PNG)',
            'gif' => 'Image (GIF)',
        ];

        if (isset($byExt[$ext])) {
            return $byExt[$ext];
        }

        if ($mime !== '') {
            return Str::upper(Str::afterLast($mime, '/'));
        }

        return 'File';
    }
}
