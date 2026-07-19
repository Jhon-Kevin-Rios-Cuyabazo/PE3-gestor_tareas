<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Repositories\TaskRepository;

class TaskController extends Controller
{
    protected $repository;

    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        // Se asume un identificador de sesión para efectos de la demostración de la API
        $userId = 1; 
        
        $status = $request->get('status');
        
        // Inicio de perfilado para medición del Speedup
        $startTime = microtime(true);
        
        $tasks = $this->repository->getPaginatedTasks($userId, 10, $status);
        
        $endTime = microtime(true);
        $timeMs = round(($endTime - $startTime) * 1000, 2);

        return response()->json([
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
                'query_time_ms' => $timeMs
            ],
            'data' => $tasks->items()
        ]);
    }
}
