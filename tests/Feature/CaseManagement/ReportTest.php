<?php

namespace Tests\Feature\CaseManagement;

use App\Modules\CaseManagement\Models\CaseModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('reports.view');
    }

    public function test_report_index_returns_successful_response(): void
    {
        $response = $this->actingAs($this->admin)->get(route('cases.reports'));

        $response->assertStatus(200);
        $response->assertSee('Executive Summary');
    }

    public function test_summary_report_shows_metrics(): void
    {
        CaseModel::create(['case_number' => 'RPT-1', 'title' => 'O', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('cases.reports', [
            'report_type' => 'summary',
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Total cases');
    }

    public function test_monthly_report_returns_data(): void
    {
        CaseModel::create(['case_number' => 'MONTH-1', 'title' => 'O', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('cases.reports', [
            'report_type' => 'monthly',
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }

    public function test_quarterly_report_returns_data(): void
    {
        CaseModel::create(['case_number' => 'QTR-1', 'title' => 'O', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('cases.reports', [
            'report_type' => 'quarterly',
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
    }
}
