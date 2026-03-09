<?php

namespace Database\Seeders;

use App\Modules\CaseManagement\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class CaseSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('username', 'admin')->value('id');
        if (! $userId) {
            return;
        }

        $cases = [
            ['title' => 'Sample Case One', 'nature_of_claim' => 'Contract', 'claimant' => 'John Doe', 'defendant' => 'Acme Corp'],
            ['title' => 'Sample Case Two', 'nature_of_claim' => 'Employment', 'claimant' => 'Jane Smith', 'defendant' => 'XYZ Ltd'],
            ['title' => 'Sample Case Three', 'nature_of_claim' => 'Property', 'claimant' => 'Bob Wilson', 'defendant' => 'State'],
        ];

        $year = now()->format('Y');
        foreach ($cases as $i => $data) {
            CaseModel::firstOrCreate(
                ['case_number' => "CASE-{$year}-" . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT)],
                array_merge($data, [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'assigned_to' => $userId,
                    'date_filed' => now()->subDays(rand(1, 60)),
                ])
            );
        }
    }
}
