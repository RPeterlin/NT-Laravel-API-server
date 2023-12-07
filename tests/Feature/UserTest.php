<?php

namespace Tests\Feature;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private $data;
    private $emptyData;

    function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'calories' => 999,
            'tfat' => 10,
            'sfat' => 10,
            'carbs' => 10,
            'sugar' => 10,
            'protein' => 10,
        ];
        $this->emptyData = [
            'calories' => null,
            'tfat' => null,
            'sfat' => null,
            'carbs' => null,
            'sugar' => null,
            'protein' => null,
        ];

        $this->user = User::factory($this->emptyData)
            ->has(Meal::factory()->count(2))
            ->create();
    }

    // UPDATE TARGET MACROS (UTM)
    public function test_utm_success_where_all_optional_fields_are_given(): void
    {
        Sanctum::actingAs($this->user);
        $endResult = $this->data;
        $endResult['id'] = $this->user->id;

        $response = $this->post('/api/target-macros', $this->data);

        $response->assertStatus(200);
        $response->assertJson([
            'user' => $endResult,
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', $endResult);
    }
    public function test_utm_success_where_not_all_optional_fields_are_given(): void
    {
        Sanctum::actingAs($this->user);
        $data = $this->data;
        unset($data['calories']);
        $endResult = $data;
        $endResult['calories'] = null;
        $endResult['id'] = $this->user->id;

        $response = $this->post('/api/target-macros', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'user' => $endResult
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', $endResult);
    }
    public function test_utm_where_not_authenticated(): void
    {
        $response = $this->post('/api/target-macros', $this->data);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    public function test_utm_where_input_are_wrong_type(): void
    {
        Sanctum::actingAs($this->user);
        $data = [
            'calories' => 'notNumeric',
            'tfat' => 'notNumeric',
            'sfat' => 'notNumeric',
            'carbs' => 'notNumeric',
            'sugar' => 'notNumeric',
            'protein' => 'notNumeric',
        ];

        $response = $this->post('/api/target-macros', $data);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'calories' => [
                    'The calories field must be an integer.'
                ],
                'tfat' => [
                    'The tfat field must be an integer.'
                ],
                'sfat' => [
                    'The sfat field must be an integer.'
                ],
                'carbs' => [
                    'The carbs field must be an integer.'
                ],
                'sugar' => [
                    'The sugar field must be an integer.'
                ],
                'protein' => [
                    'The protein field must be an integer.'
                ],
            ]
        ]);
        $this->assertDatabaseHas('users', $this->emptyData);
    }
}
