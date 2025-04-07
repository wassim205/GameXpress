<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use DatabaseTransactions;
    private $user;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create()->assignRole('super_admin');
        $this->user = User::factory()->create()->assignRole('product_manager');
    }

    /** @test */
    public function it_can_list_all_products()
    {
        Product::factory(3)->create();
        $productsNum = Product::count();
        
        $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/admin/products');
        // $response->dump();
        $response->assertStatus(200)->assertJsonCount($productsNum, 'data');
    }

    /** @test */
    public function it_can_show_a_product_by_id()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id
            ]);
    }

    /** @test */
    public function it_can_create_a_new_product()
    {
        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'slug' => 'test-product',
            'stock' => 20,
            'status' => 'available',
            'category_id' => 2
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
               'product' => ['name' => 'Test Product']
            ]);

        $this->assertDatabaseHas('products', $productData);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create();

        $updateData = ['name' => 'Updated Name', 'price' => 120];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'product' => ['name' => 'Updated Name']
            ]);

        $this->assertDatabaseHas('products', $updateData);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }



}
