<?php

namespace Tests\Feature\CaseManagement;

use App\Modules\CaseManagement\Models\CaseCategory;
use App\Modules\CaseManagement\Models\CaseModel;
use App\Modules\CaseManagement\Models\CaseActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CaseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('cases.view', 'cases.create', 'cases.edit', 'cases.delete', 'cases.categories.view');
        $this->category = CaseCategory::create(['name' => 'Test Category', 'slug' => 'test-category']);
    }

    public function test_index_returns_successful_response(): void
    {
        CaseModel::create([
            'case_number' => 'CMS-2026-0001',
            'title' => 'Test Officer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('cases.index'));

        $response->assertStatus(200);
        $response->assertSee('CMS-2026-0001');
    }

    public function test_case_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cases.store'), [
            'title' => 'Officer Name',
            'claimant' => 'John Doe',
            'defendant' => 'Jane Roe',
            'status' => 'active',
            'category_id' => $this->category->id,
            'nature_of_claim' => 'Breach of contract',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cases', ['title' => 'Officer Name']);
    }

    public function test_case_activity_is_logged_on_creation(): void
    {
        $case = CaseModel::create([
            'case_number' => 'CMS-2026-0003',
            'title' => 'Officer',
            'status' => 'active',
        ]);

        CaseActivity::create([
            'case_id' => $case->id,
            'user_id' => $this->admin->id,
            'action' => 'case.created',
            'description' => 'Case registered',
        ]);

        $this->assertDatabaseHas('case_activities', [
            'case_id' => $case->id,
            'action' => 'case.created',
        ]);
    }

    public function test_case_can_be_updated(): void
    {
        $case = CaseModel::create([
            'case_number' => 'CMS-2026-0004',
            'title' => 'Old Officer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('cases.update', $case), [
            'case_number' => 'CMS-2026-0004',
            'title' => 'New Officer',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cases', ['id' => $case->id, 'title' => 'New Officer']);
    }

    public function test_case_can_be_deleted(): void
    {
        $case = CaseModel::create([
            'case_number' => 'CMS-2026-0005',
            'title' => 'Officer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('cases.destroy', $case));

        $response->assertRedirect();
        $this->assertSoftDeleted('cases', ['id' => $case->id]);
    }

    public function test_case_index_filters_by_party(): void
    {
        CaseModel::create(['case_number' => 'P1', 'title' => 'Off', 'claimant' => 'Alice', 'status' => 'active']);
        CaseModel::create(['case_number' => 'P2', 'title' => 'Off', 'defendant' => 'Bob', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('cases.index', ['party' => 'Alice']));

        $response->assertStatus(200);
        $response->assertSee('P1');
        $response->assertDontSee('P2');
    }
}
