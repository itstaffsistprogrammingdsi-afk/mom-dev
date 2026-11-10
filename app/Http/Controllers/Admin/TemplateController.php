<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentType;
use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Models\MeetingTemplate;
use App\Services\AuditLogger;
use App\Services\TemplateContentFactory;
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
            'templates' => MeetingTemplate::query()->orderBy('document_type')->orderBy('name')->get(),
            'documentTypes' => $this->documentTypes(),
            'meetingTypes' => $this->meetingTypes(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit, TemplateContentFactory $contentFactory): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $validated['meeting_type'] ??= MeetingType::Other->value;
        $type = DocumentType::from($validated['document_type']);
        $content = $contentFactory->default($type);

        $template = MeetingTemplate::create([
            ...$validated,
            'sections' => $contentFactory->sectionTitles($content),
            'content' => $content,
        ]);

        $audit->log('template.created', $template, 'Menambahkan template dokumen');

        return redirect()
            ->route('admin.templates.edit', $template)
            ->with('success', 'Template dibuat. Lengkapi isi dan strukturnya.');
    }

    public function edit(MeetingTemplate $template, TemplateContentFactory $contentFactory): Response
    {
        return Inertia::render('Admin/Templates/Edit', [
            'template' => $template,
            'defaultContent' => $template->content ?? $contentFactory->default($template->document_type),
            'documentTypes' => $this->documentTypes(),
            'meetingTypes' => $this->meetingTypes(),
        ]);
    }

    public function update(Request $request, MeetingTemplate $template, AuditLogger $audit, TemplateContentFactory $contentFactory): RedirectResponse
    {
        $validated = $this->validatePayload($request, $template);
        $validated['meeting_type'] ??= MeetingType::Other->value;
        $content = $validated['content'] ?? $template->content;
        unset($validated['content']);

        $template->update([
            ...$validated,
            'sections' => $contentFactory->sectionTitles($content),
            'content' => $content,
        ]);

        $audit->log('template.updated', $template, 'Memperbarui template dokumen');

        return back()->with('success', 'Template diperbarui.');
    }

    public function toggleStatus(MeetingTemplate $template, AuditLogger $audit): RedirectResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        $audit->log('template.status_updated', $template, 'Mengubah status template dokumen');

        return back()->with('success', 'Status template diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?MeetingTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('meeting_templates', 'name')->ignore($template?->id)],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'meeting_type' => ['nullable', Rule::enum(MeetingType::class)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'content' => ['nullable', 'array'],
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string, description: string}>
     */
    private function documentTypes(): array
    {
        return collect(DocumentType::cases())->map(fn (DocumentType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ])->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function meetingTypes(): array
    {
        return collect(MeetingType::cases())->map(fn (MeetingType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->all();
    }
}
