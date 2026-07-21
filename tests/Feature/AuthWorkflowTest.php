<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// Test 1: Login workflow
test('login page is accessible and returns 200', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
    // Page is in Russian - check for Russian text
    $response->assertSee('Войти');
});

// Test 2: User can login with valid credentials
test('user can login with valid credentials', function () {
    // Create a test user
    $user = User::factory()->create([
        'email' => 'testauthor@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'testauthor@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

// Test 3: Dashboard is accessible after login
test('dashboard is accessible after login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
});

// Test 4: Protected routes redirect to login when not authenticated
test('dashboard redirects to login when not authenticated', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('editorial dashboard redirects to login when not authenticated', function () {
    $response = $this->get('/dashboard/editorial');
    $response->assertRedirect('/login');
});

test('article creation redirects to login when not authenticated', function () {
    $response = $this->get('/dashboard/articles/create');
    $response->assertRedirect('/login');
});

// Test 5: Author submission workflow
test('author can access article creation form with permission', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Give user the submit-article permission
    $user->givePermissionTo('submit-article');

    $response = $this->actingAs($user)->get('/dashboard/articles/create');

    // Should return 200 if user has permission
    $response->assertStatus(200);
});

// Test 6: User without permission gets 403
test('user without permission cannot access article creation', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // User does NOT have submit-article permission
    $response = $this->actingAs($user)->get('/dashboard/articles/create');

    // Should return 403 Forbidden
    $response->assertStatus(403);
});

// Test 7: Check middleware is applied
test('auth middleware is applied to dashboard routes', function () {
    $routes = [
        '/dashboard',
        '/dashboard/articles/create',
        '/dashboard/editorial',
        '/dashboard/reviews',
    ];

    foreach ($routes as $route) {
        $response = $this->get($route);
        // 302 is redirect status code - check it redirects to login
        $location = $response->headers->get('Location');
        $isLoginRedirect = $response->isRedirect() && (str_contains($location, '/login') || $location === '/login');
        $this->assertTrue(
            $isLoginRedirect || $response->status() === 403,
            "Route $route should redirect to login or return 403, got status: ".$response->status().', location: '.$location
        );
    }
});

// Test 8: Invalid credentials fail
test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correctpassword'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    // Laravel redirects back to login with errors
    $this->assertGuest();
});
