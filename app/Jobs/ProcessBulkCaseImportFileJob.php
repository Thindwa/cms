<?php

namespace App\Jobs;

use App\Modules\CaseManagement\Imports\ExcelCaseImportService;
use App\Modules\CaseManagement\Models\CaseImportBulkBatch;
use App\Modules\CaseManagement\Models\CaseImportBulkFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessBulkCaseImportFileJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public string $bulkBatchId,
        public string $bulkFileId
    ) {}

    public function handle(ExcelCaseImportService $service): void
    {
        $file = CaseImportBulkFile::query()->with('batch')->find($this->bulkFileId);
        if (! $file || ! $file->batch || $file->status === 'completed') {
            return;
        }

        $batch = $file->batch;
        $file->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $report = $service->importConfigured(
                Storage::disk('local')->path($file->stored_file_path),
                $batch->sheet_name,
                $batch->mapping ?? [],
                $batch->options ?? [],
                $batch->created_by,
                false
            );

            $file->update([
                'status' => 'completed',
                'report' => $report,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $file->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        $this->refreshBatchStatus($batch->id);
    }

    protected function refreshBatchStatus(string $batchId): void
    {
        $batch = CaseImportBulkBatch::query()->find($batchId);
        if (! $batch) {
            return;
        }

        $counts = $batch->files()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('completed','failed') THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->first();

        $total = (int) ($counts->total ?? 0);
        $completed = (int) ($counts->completed ?? 0);
        $successful = (int) ($counts->successful ?? 0);
        $failed = (int) ($counts->failed ?? 0);

        $status = $batch->status;
        $completedAt = null;
        if ($completed >= $total && $total > 0) {
            $status = $failed > 0 ? 'completed_with_failures' : 'completed';
            $completedAt = now();
        } elseif ($batch->status !== 'processing') {
            $status = 'processing';
        }

        $batch->update([
            'status' => $status,
            'processed_files' => $completed,
            'successful_files' => $successful,
            'failed_files' => $failed,
            'completed_at' => $completedAt,
        ]);
    }
}
