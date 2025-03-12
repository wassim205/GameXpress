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
    // use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    /** @test */
    public function it_can_show_the_products_list()
    {
        $user = User::factory()->create()->assignRole('product_manager');
        
        Product::factory(3)->create();
        $productsNum = Product::count();
        
        $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/products');
        $response->assertStatus(200)->assertJsonCount($productsNum, 'data');
    }

}
