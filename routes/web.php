<?php

use App\Http\Controllers\ActionItemController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DivisionController as AdminDivisionController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\TemplateController as AdminTemplateController;
use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CollabTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\MeetingAiController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingExportController;
use App\Http\Controllers\MeetingShareController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\RelatedController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('notulen', [MeetingController::class, 'index'])->name('meetings.index');
    Route::get('notulen/buat', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('notulen', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('notulen/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('notulen/{meeting}/sunting', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::patch('notulen/{meeting}/autosave', [MeetingController::class, 'autosave'])->name('meetings.autosave');
    Route::patch('notulen/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::post('notulen/{meeting}/finalkan', [MeetingController::class, 'finalize'])->name('meetings.finalize');
    Route::post('notulen/{meeting}/kunci', [MeetingController::class, 'lock'])->name('meetings.lock');
    Route::post('notulen/{meeting}/buka-kunci', [MeetingController::class, 'unlock'])->name('meetings.unlock');
    Route::post('notulen/{meeting}/versi/{version}/pulihkan', [MeetingController::class, 'restoreVersion'])->name('meetings.versions.restore');

    Route::post('notulen/{meeting}/lampiran', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('lampiran/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('lampiran/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('notulen/{meeting}/ekspor/pdf', [MeetingExportController::class, 'pdf'])->name('meetings.export.pdf');
    Route::get('notulen/{meeting}/ekspor/word', [MeetingExportController::class, 'docx'])->name('meetings.export.word');
    Route::post('notulen/{meeting}/bagikan', [MeetingShareController::class, 'send'])
        ->middleware('throttle:20,1')
        ->name('meetings.share');

    Route::post('notulen/{meeting}/ai/resume', [MeetingAiController::class, 'generate'])->name('meetings.ai.generate');
    Route::post('ai-summaries/{aiSummary}/approve', [MeetingAiController::class, 'approve'])->name('ai-summaries.approve');
    Route::post('ai-summaries/{aiSummary}/reject', [MeetingAiController::class, 'reject'])->name('ai-summaries.reject');
    Route::patch('ai-summaries/{aiSummary}', [MeetingAiController::class, 'update'])->name('ai-summaries.update');
    Route::post('ai-summaries/{aiSummary}/terapkan', [MeetingAiController::class, 'applyActionItems'])->name('ai-summaries.apply');

    Route::get('terkait-saya', RelatedController::class)->name('related.index');

    Route::get('topik', [TopicController::class, 'index'])->name('topics.index');
    Route::get('topik/id/{topic}', [TopicController::class, 'show'])->name('topics.show-id');
    Route::get('topik/{topic:slug}', [TopicController::class, 'show'])->name('topics.show');

    Route::get('divisi/id/{division}', [DivisionController::class, 'show'])->name('divisions.show-id');
    Route::get('divisi/{division:slug}', [DivisionController::class, 'show'])->name('divisions.show');
    Route::get('orang/{user}', [PeopleController::class, 'show'])->name('people.show');

    Route::get('tugas', [ActionItemController::class, 'index'])->name('action-items.index');
    Route::post('notulen/{meeting}/tugas', [ActionItemController::class, 'store'])->name('action-items.store');
    Route::patch('tugas/{actionItem}', [ActionItemController::class, 'update'])->name('action-items.update');
    Route::delete('tugas/{actionItem}', [ActionItemController::class, 'destroy'])->name('action-items.destroy');
    Route::patch('tugas/{actionItem}/status', [ActionItemController::class, 'updateStatus'])->name('action-items.status');

    Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::post('collab/token/{meeting}', CollabTokenController::class)->name('collab.token');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('divisi', [AdminDivisionController::class, 'index'])->name('divisions.index');
        Route::post('divisi', [AdminDivisionController::class, 'store'])->name('divisions.store');

        Route::get('peran', [AdminRoleController::class, 'index'])->name('roles.index');

        Route::get('topik', [AdminTopicController::class, 'index'])->name('topics.index');
        Route::post('topik', [AdminTopicController::class, 'store'])->name('topics.store');
        Route::patch('topik/{topic}/status', [AdminTopicController::class, 'toggleStatus'])->name('topics.toggle');

        Route::get('template', [AdminTemplateController::class, 'index'])->name('templates.index');
        Route::post('template', [AdminTemplateController::class, 'store'])->name('templates.store');
        Route::get('template/{template}/sunting', [AdminTemplateController::class, 'edit'])->name('templates.edit');
        Route::patch('template/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
        Route::patch('template/{template}/status', [AdminTemplateController::class, 'toggleStatus'])->name('templates.toggle');

        Route::get('pengguna', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('pengguna', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('pengguna/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
