<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
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
        $user = User::factory()->create();

        $user->assignRole('product_manager');
        
        Product::factory(3)->create();
        
        $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/products');
        
        // dd($response);
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

}
