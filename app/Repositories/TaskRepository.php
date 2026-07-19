<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskRepository
{
    /**
     * Obtener tareas paginadas aplicando el patrón Cache-Aside.
     */
    public function getPaginatedTasks(int $userId, int $perPage = 10, string $status = null)
    {
        // Generación de clave única basada en el usuario, estado filtrado y página actual
        $page = request()->get('page', 1);
        $statusKey = $status ?? 'all';
        $cacheKey = "tasks:user_{$userId}:status_{$statusKey}:page_{$page}";

        // Implementación del Patrón Cache-Aside (Lazy Loading) mediante Redis.
        // Se busca el registro en caché; ante un 'miss', se ejecuta la consulta SQL y se almacena con un TTL de 300 segundos.
        return Cache::remember($cacheKey, 300, function () use ($userId, $perPage, $status) {
            $query = Task::where('user_id', $userId);
            
            if ($status) {
                $query->where('status', $status);
            }

            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Invalidación manual de la caché asociada a un usuario.
     */
    public function clearUserCache(int $userId)
    {
        // En implementaciones nativas de Redis se emplean tags o iteración de claves.
        // A modo de simplificación técnica, se purgan las primeras 10 páginas cacheadas para cada estado posible.
        for ($i = 1; $i <= 10; $i++) {
            Cache::forget("tasks:user_{$userId}:status_all:page_{$i}");
            Cache::forget("tasks:user_{$userId}:status_pending:page_{$i}");
            Cache::forget("tasks:user_{$userId}:status_completed:page_{$i}");
        }
    }
}
