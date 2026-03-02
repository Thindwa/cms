<?php

namespace App\Modules\CaseManagement\Services;

use App\Core\Audit\AuditService;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CaseManagementService
{
    public function __construct(
        protected AuditService $audit
    ) {}

    public function generateCaseNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "CASE-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $last = CaseModel::withTrashed()
                ->where('case_number', 'like', $prefix . '%')
                ->orderByDesc('case_number')
                ->value('case_number');

            $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
            return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
        });
    }

    public function create(array $data): CaseModel
    {
        $data['case_number'] = $data['case_number'] ?? $this->generateCaseNumber();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $case = CaseModel::create($data);
        $this->audit->log('case.created', CaseModel::class, $case->id, null, $case->toArray());
        return $case;
    }

    public function update(CaseModel $case, array $data): CaseModel
    {
        $old = $case->toArray();
        $data['updated_by'] = Auth::id();

        if (isset($data['status'])) {
            if ($data['status'] === CaseModel::STATUS_CLOSED && empty($data['closed_at'])) {
                $data['closed_at'] = now();
            }
            if ($data['status'] !== CaseModel::STATUS_CLOSED) {
                $data['closed_at'] = null;
            }
        }

        $case->update($data);
        $this->audit->log('case.updated', CaseModel::class, $case->id, $old, $case->fresh()->toArray());
        return $case;
    }
}
