<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Support\Facades\Cache;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository();
    }

    public function test_get_paginated_tasks_returns_correct_data_and_uses_cache()
    {
        $user = User::factory()->create();
        Task::factory()->count(15)->create(['user_id' => $user->id, 'status' => 'pending']);

        // Aserción mediante Mocking para verificar que el repositorio intente resolver la lectura desde la caché (Cache-aside)
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(Task::where('user_id', $user->id)->paginate(10));

        $result = $this->repository->getPaginatedTasks($user->id, 10);

        $this->assertCount(10, $result->items());
    }

    public function test_clear_user_cache_invalidates_expected_keys()
    {
        // Se valida que el repositorio emita las directivas de borrado correspondientes (10 páginas x 3 estados esperados = 30 llamadas)
        Cache::shouldReceive('forget')->times(30);

        $this->repository->clearUserCache(1);
    }
}
