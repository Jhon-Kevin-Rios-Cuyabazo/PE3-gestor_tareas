<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\TaskRepository;
use Illuminate\Support\Facades\Cache;

class BenchmarkCache extends Command
{
    protected $signature = 'benchmark:cache
                            {--user=1 : ID del usuario a usar en el benchmark}
                            {--iterations=10 : Número de repeticiones por escenario}';

    protected $description = 'Mide el speedup del patrón Cache-Aside comparando consultas sin caché vs con caché.';

    public function handle(TaskRepository $repository): void
    {
        $userId = (int) $this->option('user');
        $iterations = (int) $this->option('iterations');

        $this->info("Iniciando benchmark de caché para el usuario ID {$userId}...");
        $this->newLine();

        // ── Escenario 1: Sin caché (cache miss en cada iteración) ─────────────
        $this->comment("Escenario 1: Sin caché ({$iterations} iteraciones)");
        $timesSin = [];
        for ($i = 0; $i < $iterations; $i++) {
            Cache::tags(["user_{$userId}"])->flush();
            $start = microtime(true);
            $repository->getPaginatedTasks($userId);
            $timesSin[] = (microtime(true) - $start) * 1000;
        }

        // ── Escenario 2: Con caché (cache hit a partir de la 2ª iteración) ────
        $this->comment("Escenario 2: Con caché ({$iterations} iteraciones)");
        Cache::tags(["user_{$userId}"])->flush();
        $repository->getPaginatedTasks($userId); // Llenar caché inicialmente
        $timesCon = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $repository->getPaginatedTasks($userId);
            $timesCon[] = (microtime(true) - $start) * 1000;
        }

        // ── Tabla de resultados ───────────────────────────────────────────────
        $this->newLine();
        $this->info('Resultados por iteración (ms):');

        $rows = [];
        for ($i = 0; $i < $iterations; $i++) {
            $rows[] = [
                $i + 1,
                round($timesSin[$i], 3),
                round($timesCon[$i], 3),
            ];
        }
        $this->table(['Iteración', 'Sin caché (ms)', 'Con caché (ms)'], $rows);

        // ── Speedup ───────────────────────────────────────────────────────────
        $avgSin = array_sum($timesSin) / count($timesSin);
        $avgCon = array_sum($timesCon) / count($timesCon);
        $speedup = $avgCon > 0 ? ($avgSin / $avgCon) : 0;

        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Promedio sin caché', round($avgSin, 3) . ' ms'],
                ['Promedio con caché', round($avgCon, 3) . ' ms'],
                ['Speedup (S = T_sin / T_con)', round($speedup, 2) . 'x'],
            ]
        );

        if ($speedup >= 10) {
            $this->info("El uso de Redis produce una mejora de {$speedup}x. La caché se justifica plenamente.");
        } else {
            $this->warn("Speedup de {$speedup}x. Verificar que Redis está activo y los datos de prueba sean suficientes.");
        }
    }
}
