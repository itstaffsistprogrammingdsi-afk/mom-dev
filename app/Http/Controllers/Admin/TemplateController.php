<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Models\MeetingTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Templates/Index', [
            'templates' => MeetingTemplate::query()->orderBy('name')->get(),
            'types' => collect(MeetingType::cases())->map(fn (MeetingType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $template = MeetingTemplate::create([
            ...$validated,
            'sections' => $this->defaultSections(),
        ]);

        $audit->log('template.created', $template, 'Menambahkan template notulen');

        return back()->with('success', 'Template ditambahkan.');
    }

    public function update(Request $request, MeetingTemplate $template, AuditLogger $audit): RedirectResponse
    {
        $validated = $this->validatePayload($request, $template);
        $template->update($validated);

        $audit->log('template.updated', $template, 'Memperbarui template notulen');

        return back()->with('success', 'Template diperbarui.');
    }

    public function toggleStatus(MeetingTemplate $template, AuditLogger $audit): RedirectResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        $audit->log('template.status_updated', $template, 'Mengubah status template notulen');

        return back()->with('success', 'Status template diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?MeetingTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('meeting_templates', 'name')->ignore($template?->id)],
            'meeting_type' => ['required', Rule::enum(MeetingType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return array<int, array{title: string, body: string}>
     */
    private function defaultSections(): array
    {
        return [
            ['title' => 'Agenda', 'body' => ''],
            ['title' => 'Pembahasan', 'body' => ''],
            ['title' => 'Keputusan', 'body' => ''],
            ['title' => 'Action Item', 'body' => ''],
        ];
    }
}
