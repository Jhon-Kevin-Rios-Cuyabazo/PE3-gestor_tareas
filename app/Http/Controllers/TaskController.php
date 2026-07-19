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
        
        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'sort_by' => $request->get('sort_by', 'created_at'),
            'sort_dir' => $request->get('sort_dir', 'desc'),
        ];
        
        // Inicio de perfilado para medición del Speedup
        $startTime = microtime(true);
        
        $tasks = $this->repository->getPaginatedTasks($userId, $filters, 10);
        
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

    public function store(Request $request)
    {
        $userId = 1; // Usuario simulado
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed'
        ]);
        
        $validated['user_id'] = $userId;
        $validated['status'] = $validated['status'] ?? 'pending';

        $task = $this->repository->create($validated);
        
        return response()->json($task, 201);
    }

    public function show(Task $task)
    {
        return response()->json($task, 200);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed'
        ]);

        $task = $this->repository->update($task, $validated);
        
        return response()->json($task, 200);
    }

    public function destroy(Task $task)
    {
        $this->repository->delete($task);
        return response()->json(null, 204);
    }
}
