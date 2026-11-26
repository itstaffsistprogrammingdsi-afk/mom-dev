<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim((string) $request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json(['meetings' => [], 'topics' => [], 'people' => []]);
        }

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->searchText($query)
            ->select(['id', 'title', 'scheduled_at', 'status', 'confidentiality'])
            ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('simple', ?)) DESC", [$query])
            ->orderByDesc('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn (Meeting $meeting) => [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'subtitle' => $meeting->scheduled_at?->translatedFormat('d M Y') ?? 'Tanpa tanggal',
                'url' => route('meetings.show', $meeting),
            ]);

        $topics = Topic::query()
            ->where('status', 'active')
            ->whereLike('name', "%{$query}%", caseSensitive: false)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'title' => $topic->name,
                'subtitle' => 'Topik',
                'url' => route('topics.show', $topic->slug),
            ]);

        $people = User::query()
            ->where('is_active', true)
            ->whereLike('name', "%{$query}%", caseSensitive: false)
            ->with('division:id,name')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'job_title', 'division_id'])
            ->map(fn (User $person) => [
                'id' => $person->id,
                'title' => $person->name,
                'subtitle' => $person->job_title ?: ($person->division?->name ?? 'Pengguna'),
                'url' => route('people.show', $person),
            ]);

        return response()->json([
            'meetings' => $meetings,
            'topics' => $topics,
            'people' => $people,
        ]);
    }
}
