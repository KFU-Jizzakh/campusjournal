<?php

use App\Models\Article;
use App\Models\CrossrefDeposit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can view the system health page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertSee('Здоровье системы')
        ->assertSee('Задач в очереди')
        ->assertSee('Неудачных задач');
});

test('non-admin users cannot access the health page', function () {
    $contentManager = User::factory()->create();
    $contentManager->assignRole('content-manager');

    $this->actingAs($contentManager)
        ->get('/admin/system-health')
        ->assertForbidden();
});

test('failed jobs are listed with the exception first line', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    DB::table('failed_jobs')->insert([
        'uuid' => 'failed-uuid-123',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'RuntimeException: Something broke'."\n".'#0 /app/vendor/trace.php(10)',
        'failed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertSee('failed-uuid-123')
        ->assertSee('RuntimeException: Something broke')
        ->assertDontSee('/app/vendor/trace.php');
});

test('crossref deposits render with localized status badges', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $article = Article::factory()->published()->create();

    CrossrefDeposit::factory()->create([
        'article_id' => $article->id,
        'doi' => '10.12345/accepted-1',
        'status' => CrossrefDeposit::STATUS_ACCEPTED,
        'http_status' => 200,
    ]);

    CrossrefDeposit::factory()->create([
        'article_id' => $article->id,
        'doi' => '10.12345/failed-1',
        'status' => CrossrefDeposit::STATUS_FAILED,
        'http_status' => 500,
    ]);

    $this->actingAs($admin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertSee('10.12345/accepted-1')
        ->assertSee('10.12345/failed-1')
        ->assertSee('Принят')
        ->assertSee('Ошибка');
});

test('review reminder schedule shows missing log as no data', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $log = storage_path('logs/schedule-reviews-reminders.log');

    if (file_exists($log)) {
        unlink($log);
    }

    $this->actingAs($admin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertSee('Нет данных');

    touch($log);

    $this->actingAs($admin)
        ->get('/admin/system-health')
        ->assertOk()
        ->assertDontSee('Нет данных');

    unlink($log);
});
