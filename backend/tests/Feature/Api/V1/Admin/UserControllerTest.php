<?php

namespace Tests\Feature\Api\V1\Admin;


use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserControllerTest extends TestCase
{

    use DatabaseTransactions;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create()->assignRole('user_manager');
    }

    /** @test */
    public function it_can_list_all_users()
    {
        // $user = User::factory()->create()->assignRole('product_manager');

        user::factory(3)->create();
        $UsersNum = user::count();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/admin/users');
        $response->assertStatus(200)->assertJsonCount($UsersNum, 'users');
    }

    /** @test */
    public function it_can_show_a_user_by_id()
    {
        $user = user::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'user' => ['id' => $user->id]
            ]);
    }

    /** @test */
    public function it_can_create_a_new_user()
    {
        $userData = [
            'name' => 'Test user',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user_manager',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/admin/users', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'user' => [
                    'name' => 'Test user',
                   
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test user',
            'email' => 'test@gmail.com',
        ]);
    }

    /** @test */
    public function it_can_update_a_user()
    {
        $user = user::factory()->create();

        $updateData = ['name' => 'Updated Name', 'email' => 'email@gmail.com', 'role' => 'super_admin'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/admin/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'user' => ['name' => 'Updated Name']
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Updated Name',
            'email' => 'email@gmail.com',
        ]);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = user::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$user->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
