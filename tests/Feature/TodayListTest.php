<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\Today;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TodayListTest extends TestCase
{
    use RefreshDatabase;
    private $user1;
    private $user2;
    private $meal1;
    private $meal2;
    private $data1;
    private $data2;

    function setUp(): void
    {
        parent::setUp();

        // Create two users each having 2 meals
        $this->user1 = User::factory()
            ->has(Meal::factory()->count(2))
            ->create();
        $this->user2 = User::factory()
            ->has(Meal::factory()->count(2))
            ->create();
        // For each user get its first meal
        $this->meal1 = $this->user1->meals->first();
        $this->meal2 = $this->user2->meals->first();

        $this->data1 = [
            'user_id' => $this->user1->id,
            'meal_id' => $this->meal1->id,
            'amount' => 1,
        ];
        $this->data2 = [
            'user_id' => $this->user2->id,
            'meal_id' => $this->meal2->id,
            'amount' => 1,
        ];
    }


    // INDEX
    public function test_index_success(): void
    {
        Sanctum::actingAs($this->user1);
        // For each user add its first meal to the TodayList
        Today::factory()->create($this->data1);
        Today::factory()->create($this->data2);

        $response = $this->get('/api/today-list');

        $response->assertStatus(200);
        $response->assertJson([
            'todayList' => [
                [
                    'user_id' => $this->user1->id,
                    'id' => $this->meal1->id,
                    'amount' => 1,
                ]
            ]
        ]);
    }
    public function test_index_where_not_authenticated(): void
    {
        $response = $this->get('/api/today-list');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }


    // STORE
    public function test_store_success_where_meal_not_yet_on_today_list(): void
    {
        Sanctum::actingAs($this->user1);
        $endResult = $this->data1;

        $response = $this->post('/api/today-list/' . $this->meal1->id);

        $response->assertStatus(201);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('todays', 1);
        $endResult['id'] = $response['meal']['id'];
        $this->assertDatabaseHas('todays', $endResult);
    }
    public function test_store_success_where_meal_already_on_today_list(): void
    {
        Sanctum::actingAs($this->user1);
        Today::factory()->create($this->data1);
        $endResult = $this->data1;
        $endResult['amount'] = 2;

        $response = $this->post('/api/today-list/' . $this->meal1->id);

        $response->assertStatus(201);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('todays', 1);
        $endResult['id'] = $response['meal']['id'];
        $this->assertDatabaseHas('todays', $endResult);
    }
    public function test_store_where_not_authenticated(): void
    {
        $response = $this->post('/api/today-list/' . $this->meal1->id);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
        $this->assertDatabaseCount('todays', 0);
    }
    public function test_store_where_meal_does_not_exist(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->post('/api/today-list/4559999999999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
        $this->assertDatabaseCount('todays', 0);
    }
    public function test_store_where_meal_belongs_to_different_user(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->post('/api/today-list/' . $this->meal2->id);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
        $this->assertDatabaseCount('todays', 0);
    }

    // UPDATE
    public function test_update_success(): void
    {
        Sanctum::actingAs($this->user1);
        $today = Today::factory()->create($this->data1);
        $endResult = $this->data1;
        $endResult['amount'] = 17;
        $endResult['id'] = $today->id;

        $response = $this->put('/api/today-list/' . $today->id, ['amount' => 17]);

        $response->assertStatus(200);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('todays', 1);
        $this->assertDatabaseHas('todays', $endResult);
    }
    public function test_update_where_not_authenticated(): void
    {
        $today = Today::factory()->create($this->data1);

        $response = $this->put('/api/today-list/' . $today->id, ['amount' => 17]);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
    public function test_update_where_new_amount_not_numeric(): void
    {
        Sanctum::actingAs($this->user1);
        $today = Today::factory()->create($this->data1);

        $response = $this->put('/api/today-list/' . $today->id, ['amount' => 'not_numeric']);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('amount', 'The amount field must be a number.'));
    }
    public function test_update_where_meal_does_not_exist(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->put('/api/today-list/459999', ['amount' => 17]);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal on your TodayList.',
        ]);
    }
    public function test_update_where_meal_belongs_to_different_user(): void
    {
        Sanctum::actingAs($this->user1);
        // For each user add its first meal to the TodayList
        $today = Today::factory()->create($this->data2);

        $response = $this->put('/api/today-list/' . $today->id, ['amount' => 17]);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal on your TodayList.',
        ]);
    }

    // DELETE
    public function test_delete_success(): void
    {
        Sanctum::actingAs($this->user1);
        $today = Today::factory()->create($this->data1);
        $endResult = $this->data1;
        $endResult['id'] = $today->id;

        $response = $this->delete('/api/today-list/' . $today->id);

        $response->assertStatus(200);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('todays', 0);
    }
    public function test_delete_where_not_authenticated(): void
    {
        $today = Today::factory()->create($this->data1);

        $response = $this->delete('/api/today-list/' . $today->id);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
    public function test_delete_where_meal_does_not_exist(): void
    {
        Sanctum::actingAs($this->user1);
        Today::factory()->create($this->data1);

        $response = $this->delete('/api/today-list/4599999');

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'No such meal on your TodayList.'
        ]);
        $this->assertDatabaseCount('todays', 1);
    }
    public function test_delete_where_meal_belongs_to_different_user(): void
    {
        Sanctum::actingAs($this->user1);
        $today = Today::factory()->create($this->data2);

        $response = $this->delete('/api/today-list/' . $today->id);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'No such meal on your TodayList.'
        ]);
        $this->assertDatabaseCount('todays', 1);
    }

    // DROP
    public function test_drop_success(): void
    {
        Sanctum::actingAs($this->user1);
        // For each user add its first meal to the TodayList
        Today::factory()->create($this->data1);
        Today::factory()->create($this->data2);

        $response = $this->get('/api/today-list/drop');

        $response->assertStatus(200);
        $this->assertDatabaseCount('todays', 1);
        $this->assertDatabaseHas('todays', $this->data2);
    }
    public function test_drop_where_not_authenticated(): void
    {
        Today::factory()->create($this->data1);

        $response = $this->get('/api/today-list/drop');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // ON-DELETE CASCADE (when removing meal from 'meals' it should also be removed from 'TodayList')
    public function test_on_delete_cascade(): void
    {
        Sanctum::actingAs($this->user1);
        // For each user add its first meal to the TodayList
        Today::factory()->create($this->data1);
        Today::factory()->create($this->data2);
        $this->meal1->delete();

        $response = $this->get('/api/today-list');

        $response->assertStatus(200);
        $response->assertJson([
            'todayList' => []
        ]);
    }
}
