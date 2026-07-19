<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // Configurar el entorno de prueba para usar 'array' como driver de caché.
        // Esto soporta Cache Tags nativamente sin necesitar Redis instalado.
        Config::set('cache.default', 'array'); 
        $this->repository = new TaskRepository();
    }

    public function test_get_paginated_tasks_uses_actual_cache()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id, 'title' => 'Titulo Original']);

        // 1. Primera lectura: Miss en caché, consulta a BD y cachea el resultado.
        $result1 = $this->repository->getPaginatedTasks($user->id);
        $this->assertEquals('Titulo Original', $result1->items()[0]->title);

        // 2. Modificación simulada externa: actualizamos la BD directo sin usar el repositorio.
        // Esto NO dispara la invalidación de la caché.
        Task::where('id', $task->id)->update(['title' => 'Titulo Modificado']);

        // 3. Segunda lectura: Hit en caché. Debería retornar el valor antiguo, confirmando el Cache-Aside.
        $result2 = $this->repository->getPaginatedTasks($user->id);
        $this->assertEquals('Titulo Original', $result2->items()[0]->title);
    }

    public function test_crud_operations_invalidate_cache()
    {
        $user = User::factory()->create();
        
        // Creación inicial
        $task = $this->repository->create(['user_id' => $user->id, 'title' => 'Tarea 1', 'status' => 'pending']);

        // 1. Lectura inicial (llena la caché)
        $result1 = $this->repository->getPaginatedTasks($user->id);
        $this->assertCount(1, $result1->items());
        $this->assertEquals('Tarea 1', $result1->items()[0]->title);

        // 2. Actualización usando el Repositorio (debería limpiar los Tags y purgar la caché)
        $this->repository->update($task, ['title' => 'Tarea Modificada']);

        // 3. Segunda lectura: al estar purgada, debe consultar MySQL y traer el dato nuevo.
        $result2 = $this->repository->getPaginatedTasks($user->id);
        $this->assertEquals('Tarea Modificada', $result2->items()[0]->title);

        // 4. Eliminación usando el Repositorio (vuelve a limpiar los Tags)
        $this->repository->delete($task);
        
        // 5. Tercera lectura: lista debe venir vacía.
        $result3 = $this->repository->getPaginatedTasks($user->id);
        $this->assertCount(0, $result3->items());
    }
}
