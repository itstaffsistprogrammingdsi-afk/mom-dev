<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$count = (int) ($argv[1] ?? 100000);
$user = User::query()->orderBy('id')->first();

if (! $user) {
    fwrite(STDERR, "Tidak ada user untuk benchmark.\n");
    exit(1);
}

$search = 'anggaran';
$iterations = 7;

$time = function (callable $callback) use ($iterations): array {
    $samples = [];

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $callback();
        $samples[] = (hrtime(true) - $start) / 1_000_000;
    }

    sort($samples);

    return [
        'median_ms' => round($samples[intdiv(count($samples), 2)], 2),
        'min_ms' => round($samples[0], 2),
        'max_ms' => round($samples[count($samples) - 1], 2),
    ];
};

DB::statement('VACUUM (ANALYZE) meetings');

DB::beginTransaction();

try {
    $insertStart = hrtime(true);

    DB::statement(<<<'SQL'
        INSERT INTO meetings (
            title, meeting_type, created_by, scheduled_at, agenda, content_text,
            status, confidentiality, created_at, updated_at
        )
        SELECT
            CASE WHEN g % 10 = 0
                THEN 'Rapat Anggaran ' || g || ' Divisi ' || (g % 20)
                ELSE 'Rapat Operasional ' || g || ' Divisi ' || (g % 20)
            END,
            'weekly',
            ?,
            now() - (g || ' days')::interval,
            CASE WHEN g % 10 = 0
                THEN 'Agenda anggaran dan evaluasi efisiensi'
                ELSE 'Agenda operasional rutin'
            END,
            CASE WHEN g % 10 = 0
                THEN 'Pembahasan anggaran, efisiensi, dan rencana kerja unit ' || (g % 500)
                ELSE 'Pembahasan operasional dan koordinasi unit ' || (g % 500)
            END,
            'final',
            'open',
            now(),
            now()
        FROM generate_series(1, ?) AS g
    SQL, [$user->id, $count]);

    $insertSeconds = round((hrtime(true) - $insertStart) / 1_000_000_000, 2);

    DB::statement('ANALYZE meetings');

    $ftsQuery = Meeting::query()
        ->visibleTo($user)
        ->searchText($search)
        ->select(['id', 'title', 'document_type', 'meeting_type', 'division_id', 'created_by', 'scheduled_at', 'status', 'confidentiality'])
        ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('simple', ?)) DESC", [$search])
        ->orderByDesc('scheduled_at')
        ->limit(20);

    $fts = $time(fn () => $ftsQuery->get());

    $like = $time(fn () => Meeting::query()
        ->visibleTo($user)
        ->where(fn ($query) => $query
            ->whereLike('title', "%{$search}%", caseSensitive: false)
            ->orWhereLike('content_text', "%{$search}%", caseSensitive: false))
        ->orderByDesc('scheduled_at')
        ->limit(20)
        ->get());

    $plan = DB::select('EXPLAIN (ANALYZE, BUFFERS) '.$ftsQuery->toSql(), $ftsQuery->getBindings());
    $planText = implode("\n", array_map(fn ($row) => $row->{'QUERY PLAN'}, $plan));

    $result = [
        'rows' => $count,
        'search' => $search,
        'insert_seconds' => $insertSeconds,
        'fts' => $fts,
        'like' => $like,
        'fts_uses_gin' => str_contains($planText, 'meetings_search_vector_gin'),
        'plan' => $planText,
        'measured_at' => now()->toIso8601String(),
    ];

    DB::rollBack();
    DB::statement('VACUUM (ANALYZE) meetings');

    $path = __DIR__.'/../storage/app/audit/benchmark-search-'.$count.'.json';
    file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $exception) {
    DB::rollBack();
    DB::statement('VACUUM (ANALYZE) meetings');

    throw $exception;
}
