<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    
    use DatabaseTransactions;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create()->assignRole('product_manager');
    }

    /** @test */
    public function it_can_list_all_categories()
    {
        // $user = User::factory()->create()->assignRole('product_manager');
        
        Category::factory(3)->create();
        $CategoriesNum = Category::count();
        
        $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/admin/categories');
        $response->assertStatus(200)->assertJsonCount($CategoriesNum, 'categories');
    }

    /** @test */
    public function it_can_show_a_category_by_id()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'category' => ['id' => $category->id]
            ]);
    }

    /** @test */
    public function it_can_create_a_new_category()
    {
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'test-category'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
               'category' => ['name' => 'Test Category']
            ]);

        $this->assertDatabaseHas('categories', data: $categoryData);
    }

    /** @test */
    public function it_can_update_a_category()
    {
        $category = Category::factory()->create();

        $updateData = ['name' => 'Updated Name', 'slug' => 'updated-name'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'category' => ['name' => 'Updated Name']
            ]);

        $this->assertDatabaseHas('categories', $updateData);
    }

    /** @test */
    public function it_can_delete_a_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

}
