<?php

namespace App\Console\Commands;

use App\Core\Settings\SettingsService;
use App\Modules\CaseManagement\Models\CaseModel;
use Illuminate\Console\Command;

class CasesCheckDormant extends Command
{
    protected $signature = 'cases:check-dormant';

    protected $description = 'Mark cases as dormant when there has been no document upload activity for the configured period';

    public function handle(SettingsService $settings): int
    {
        $dormantYears = (int) $settings->get('dormant_years', 3);
        $threshold = now()->subYears($dormantYears);
        $count = 0;

        CaseModel::query()
            ->where('status', 'active')
            ->whereNotNull('case_number')
            ->where('case_number', '!=', '')
            ->chunk(100, function ($cases) use ($threshold, &$count) {
                foreach ($cases as $case) {
                    $latestDocument = $case->documents()
                        ->latest('created_at')
                        ->first();

                    if ($latestDocument) {
                        if ($latestDocument->created_at->lt($threshold)) {
                            $case->update(['status' => 'dormant']);
                            $count++;
                        }
                    } elseif ($case->created_at->lt($threshold)) {
                        $case->update(['status' => 'dormant']);
                        $count++;
                    }
                }
            });

        $this->info("Checked active cases. {$count} marked as dormant.");

        return self::SUCCESS;
    }
}
