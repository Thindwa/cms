<?php

namespace Tests\Feature\CaseManagement;

use App\Modules\CaseManagement\Models\CaseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('cases.categories.view', 'cases.categories.create', 'cases.categories.edit', 'cases.categories.delete');
    }

    public function test_category_index_returns_successful_response(): void
    {
        CaseCategory::create(['name' => 'Civil', 'slug' => 'civil']);

        $response = $this->actingAs($this->admin)->get(route('cases.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Civil');
    }

    public function test_category_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cases.categories.store'), [
            'name' => 'Criminal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('case_categories', ['name' => 'Criminal']);
    }

    public function test_category_can_be_updated(): void
    {
        $category = CaseCategory::create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->actingAs($this->admin)->put(route('cases.categories.update', $category), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('case_categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_category_can_be_deleted(): void
    {
        $category = CaseCategory::create(['name' => 'Temp', 'slug' => 'temp']);

        $response = $this->actingAs($this->admin)->delete(route('cases.categories.destroy', $category));

        $response->assertRedirect();
        $this->assertDatabaseMissing('case_categories', ['id' => $category->id]);
    }
}
