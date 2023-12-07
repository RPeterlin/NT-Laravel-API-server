<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{

    use RefreshDatabase;
    private $data;

    function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'name' => 'Rok',
            'email' => 'r@r.com',
            'password' => 'secret',
            'password_confirmation' => 'secret'
        ];
    }

    // REGISTER
    public function test_register_success(): void
    {
        $response = $this->postJson('/api/register', $this->data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'user',
            'token'
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'name' => $this->data['name'],
            'email' => $this->data['email']
        ]);
    }
    public function test_register_where_email_not_given(): void
    {
        $data = $this->data;
        unset($data['email']);

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('email', 'The email field is required.'));
        $this->assertDatabaseCount('users', 0);
    }
    public function test_register_where_password_not_given(): void
    {
        $data = $this->data;
        unset($data['password']);

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('password', 'The password field is required.'));
        $this->assertDatabaseCount('users', 0);
    }
    public function test_register_where_password_confirmation_not_given(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('password', 'The password field confirmation does not match.'));
        $this->assertDatabaseCount('users', 0);
    }
    public function test_register_where_email_not_an_email(): void
    {
        $data = $this->data;
        $data['email'] = 'notAValidEmailFormat';

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('email', 'The email field must be a valid email address.'));
        $this->assertDatabaseCount('users', 0);
    }
    public function test_register_where_password_confirmation_does_not_match_password(): void
    {
        $data = $this->data;
        $data['password_confirmation'] = 'notTheSameAsPasswordField';

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('password', 'The password field confirmation does not match.'));
        $this->assertDatabaseCount('users', 0);
    }
    public function test_register_where_email_already_exists(): void
    {
        $user = User::factory()->create();
        $data = $this->data;
        $data['email'] = $user->email;

        $response = $this->post('/api/register', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('email', 'The email has already been taken.'));
        $this->assertDatabaseCount('users', 1);
    }

    // LOGIN
    public function test_login_success(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        $user = User::factory()->create($data);
        unset($data['name']);

        $response = $this->post('/api/login', $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user',
            'token'
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id
        ]);
    }
    public function test_login_where_email_not_given(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        User::factory()->create($data);
        // Remove multiple key-value pairs from array
        $data = array_diff_key($data, array_flip(['name', 'email']));

        $response = $this->post('/api/login', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('email', 'The email field is required.'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_login_where_password_not_given(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        User::factory()->create($data);
        // Remove multiple key-value pairs from array
        $data = array_diff_key($data, array_flip(['name', 'password']));

        $response = $this->post('/api/login', $data);

        $response->assertStatus(422);
        $response->assertJson($this->failedJSONtemplate('password', 'The password field is required.'));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_login_where_email_not_in_db(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        User::factory()->create($data);
        unset($data['name']);
        $data['email'] = 'somethingNotIn@Db.com';

        $response = $this->post('/api/login', $data);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Email and password don\'t match',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_login_where_password_does_not_match_email(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        User::factory()->create($data);
        unset($data['name']);
        $data['password'] = 'somethingNotInDb';

        $response = $this->post('/api/login', $data);

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Email and password don\'t match',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // LOGOUT
    public function test_logout_success(): void
    {
        $data = $this->data;
        unset($data['password_confirmation']);
        $user = User::factory()->create($data);
        Sanctum::actingAs($user);

        $response = $this->get('/api/logout');

        $response->assertStatus(200);
        $response->assertExactJson([
            'message' => 'Logged out',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
    public function test_logout_where_not_authenticated(): void
    {
        $response = $this->get('/api/logout');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
