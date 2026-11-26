<?php

namespace App\Http\Controllers;

use App\Services\CollabAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(CollabAvailability $collab): JsonResponse
    {
        $checks = [
            'database' => $this->probe(fn () => DB::select('select 1') !== []),
            'cache' => $this->probe(function () {
                Cache::put('health:probe', 'ok', 10);

                return Cache::get('health:probe') === 'ok';
            }),
            'queue' => $this->probe(fn () => $this->queueIsConfigured()),
            'ai' => $this->probe(fn () => config('ai.driver') === 'mock' || filled(config('ai.api_key'))),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'collab' => $this->probe(fn () => $collab->isAvailable()),
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function probe(callable $probe): bool
    {
        try {
            return (bool) $probe();
        } catch (\Throwable) {
            return false;
        }
    }

    private function queueIsConfigured(): bool
    {
        if (config('queue.default') !== 'database') {
            return true;
        }

        return Schema::hasTable('jobs') && Schema::hasTable('failed_jobs');
    }
}
