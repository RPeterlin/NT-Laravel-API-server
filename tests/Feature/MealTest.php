<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MealTest extends TestCase
{

    use RefreshDatabase;
    private $user1;
    private $user2;
    private $data;

    function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'name' => 'Rice',
            'unit' => 'Bowl',
            'calories' => 999,
            'category' => 'Breakfast',
            'tfat' => 10,
            'sfat' => 10,
            'carbs' => 10,
            'sugar' => 10,
            'protein' => 10,
        ];

        $this->user1 = User::factory()
            ->has(Meal::factory()->count(2))
            ->create();
        $this->user2 = User::factory()
            ->has(Meal::factory()->count(2))
            ->create();
    }


    // INDEX
    public function test_index_success(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->get('/api/meals');

        $response->assertStatus(200);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'meals',
                2,
                fn (AssertableJson $json) =>
                $json->where('user_id', $this->user1->id)
                    ->hasAll(['name', 'unit', 'calories', 'category', 'tfat', 'sfat', 'carbs', 'sugar', 'protein'])
                    ->etc()
            )
        );
    }
    public function test_index_where_not_authenticated(): void
    {
        $response = $this->get('/api/meals');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // STORE
    public function test_store_success_where_all_optional_fieds_are_given(): void
    {
        Sanctum::actingAs($this->user1);
        $endResult = $this->data;
        $endResult['user_id'] = $this->user1->id;

        $response = $this->post('/api/meals', $this->data);

        $response->assertStatus(201);
        $response->assertJson([
            'meal' => $endResult,
        ]);
        $this->assertDatabaseCount('meals', 5);
        $this->assertDatabaseHas('meals', $endResult);
    }
    public function test_store_success_where_not_all_optional_fields_are_given(): void
    {
        Sanctum::actingAs($this->user1);
        $data = $this->data;
        unset($data['category']);
        $endResult = $data;
        $endResult['user_id'] = $this->user1->id;

        $response = $this->post('/api/meals', $data);

        $response->assertStatus(201);
        $response->assertJson([
            'meal' => $endResult,
        ]);
        $this->assertDatabaseCount('meals', 5);
        $endResult['category'] = null;
        $this->assertDatabaseHas('meals', $endResult);
    }
    public function test_store_where_not_authenticated(): void
    {
        $response = $this->post('/api/meals', $this->data);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
    public function test_store_where_name_not_given(): void
    {
        $data = $this->data;
        unset($data['name']);
        Sanctum::actingAs($this->user1);

        $response = $this->post('/api/meals', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('name', 'The name field is required.'));
        $this->assertDatabaseCount('meals', 4);
    }
    public function test_store_where_unit_not_given(): void
    {
        $data = $this->data;
        unset($data['unit']);
        Sanctum::actingAs($this->user1);

        $response = $this->post('/api/meals', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('unit', 'The unit field is required.'));
        $this->assertDatabaseCount('meals', 4);
    }
    public function test_store_where_calories_not_given(): void
    {
        $data = $this->data;
        unset($data['calories']);
        Sanctum::actingAs($this->user1);

        $response = $this->post('/api/meals', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('calories', 'The calories field is required.'));
        $this->assertDatabaseCount('meals', 4);
    }
    public function test_store_where_user_id_given_in_request_body(): void
    {
        Sanctum::actingAs($this->user1);
        $data = $this->data;
        $data['user_id'] = 2;
        $endResult = $data;
        $endResult['user_id'] = $this->user1->id;

        $response = $this->post('/api/meals', $data);

        $response->assertStatus(201);
        $response->assertJson([
            'meal' => $endResult,
        ]);
        $this->assertDatabaseCount('meals', 5);
        $this->assertDatabaseHas('meals', $endResult);
    }

    // UPDATE
    public function test_update_success(): void
    {
        Sanctum::actingAs($this->user1);
        $data = $this->data;
        $data['user_id'] = $this->user1->id;
        $meal = Meal::factory()->create($data);
        $updatedData = [
            'calories' => 888,
            'protein' => 99,
        ];
        $endResult = [
            'calories' => $updatedData['calories'],
            'protein' => $updatedData['protein'],
            'name' => $this->data['name'],
            'unit' => $this->data['unit'],
            'category' => $this->data['category'],
            'tfat' => $this->data['tfat'],
            'sfat' => $this->data['sfat'],
            'carbs' => $this->data['carbs'],
            'sugar' => $this->data['sugar'],
            'user_id' => $this->user1->id,
            'id' => $meal->id,
        ];

        $response = $this->put('/api/meals/' . $meal->id, $updatedData);

        $response->assertStatus(200);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('meals', 5);
        $this->assertDatabaseHas('meals', $endResult);
    }
    public function test_update_where_not_authenticated(): void
    {
        $response = $this->put('/api/meals/254', []);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
    public function test_update_where_meal_id_doesn_not_exist(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->put('/api/meals/254', []);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
    }
    public function test_update_where_meal_id_belongs_to_different_user(): void
    {
        Sanctum::actingAs($this->user1);
        $mealFromDifferentUser = $this->user2->meals->first();

        $response = $this->put('/api/meals/' . $mealFromDifferentUser->id, []);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
    }

    // DELETE
    public function test_delete_success(): void
    {
        Sanctum::actingAs($this->user1);
        $data = $this->data;
        $data['user_id'] = $this->user1->id;
        $meal = Meal::factory()->create($data);
        $endResult = $data;
        $endResult['id'] = $meal->id;

        $response = $this->delete('/api/meals/' . $meal->id);

        $response->assertStatus(200);
        $response->assertJson([
            'meal' => $endResult
        ]);
        $this->assertDatabaseCount('meals', 4);
        $this->assertDatabaseMissing('meals', [
            'id' => $meal->id,
        ]);
    }
    public function test_delete_where_not_authenticated(): void
    {
        $response = $this->delete('/api/meals/254');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
    public function test_delete_where_meal_id_doesn_not_exist(): void
    {
        Sanctum::actingAs($this->user1);

        $response = $this->delete('/api/meals/254');

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
    }
    public function test_delete_where_meal_id_belongs_to_different_user(): void
    {
        Sanctum::actingAs($this->user1);
        $mealFromDifferentUser = $this->user2->meals->first();

        $response = $this->delete('/api/meals/' . $mealFromDifferentUser->id);

        $response->assertStatus(404);
        $response->assertExactJson([
            'message' => 'No such meal in your library.',
        ]);
    }
}
