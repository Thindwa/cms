<?php

namespace App\Console\Commands;

use App\Core\Settings\SettingsService;
use App\Modules\CaseManagement\Models\CaseDocument;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;

class CasesCheckDormant extends Command
{
    protected $signature = 'cases:check-dormant';

    protected $description = 'Mark cases as dormant when there has been no document upload activity for the configured period';

    public function handle(SettingsService $settings): int
    {
        $dormantYears = (int) $settings->get('dormant_years', 3);
        $threshold = now()->subYears($dormantYears);
        $count = 0;

        $latestDocSub = CaseDocument::query()
            ->selectRaw('case_id, MAX(created_at) as latest_doc_date')
            ->groupBy('case_id');

        CaseModel::query()
            ->select('cases.*', 'latest_doc_dates.latest_doc_date')
            ->leftJoinSub($latestDocSub, 'latest_doc_dates', function (JoinClause $join) {
                $join->on('cases.id', '=', 'latest_doc_dates.case_id');
            })
            ->where('status', 'active')
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '')
            ->chunk(100, function ($cases) use ($threshold, &$count) {
                foreach ($cases as $case) {
                    if ($case->latest_doc_date && $case->latest_doc_date < $threshold) {
                        $case->update(['status' => 'dormant']);
                        $count++;
                    } elseif (!$case->latest_doc_date && $case->created_at < $threshold) {
                        $case->update(['status' => 'dormant']);
                        $count++;
                    }
                }
            });

        $this->info("Checked active cases. {$count} marked as dormant.");

        return self::SUCCESS;
    }
}
