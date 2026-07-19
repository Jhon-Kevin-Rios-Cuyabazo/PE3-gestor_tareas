<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskRepository
{
    /**
     * Obtener tareas paginadas aplicando el patrón Cache-Aside con filtros dinámicos.
     */
    public function getPaginatedTasks(int $userId, array $filters = [], int $perPage = 10)
    {
        // Generación de clave única basada en todos los parámetros de entrada
        $page = request()->get('page', 1);
        $statusKey = $filters['status'] ?? 'all';
        $searchKey = $filters['search'] ?? 'none';
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        
        // Hashear los filtros para mantener la clave corta y manejable ante combinaciones largas
        $filterHash = md5("{$statusKey}_{$searchKey}_{$sortBy}_{$sortDir}_{$page}");
        
        $cacheKey = "tasks:user_{$userId}:list:{$filterHash}";

        // Implementación del Patrón Cache-Aside (Lazy Loading) mediante Redis con Tags.
        // Los Tags permiten agrupar claves para invalidarlas simultáneamente.
        return Cache::tags(['tasks', "user_{$userId}"])->remember($cacheKey, 300, function () use ($userId, $filters, $perPage, $sortBy, $sortDir) {
            $query = Task::where('user_id', $userId);
            
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['search'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('title', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('description', 'like', '%' . $filters['search'] . '%');
                });
            }

            // Validar dirección del sort
            $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

            return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
        });
    }

    public function create(array $data)
    {
        $task = Task::create($data);
        $this->clearUserCache($task->user_id);
        return $task;
    }

    public function update(Task $task, array $data)
    {
        $task->update($data);
        $this->clearUserCache($task->user_id);
        return $task;
    }

    public function delete(Task $task)
    {
        $userId = $task->user_id;
        $task->delete();
        $this->clearUserCache($userId);
    }

    /**
     * Invalidación nativa de la caché asociada a un usuario mediante Cache Tags.
     */
    public function clearUserCache(int $userId)
    {
        // Al usar un driver como Redis o Array, podemos invalidar instantáneamente 
        // todas las listas (páginas y filtros combinados) del usuario.
        Cache::tags(["user_{$userId}"])->flush();
    }
}
