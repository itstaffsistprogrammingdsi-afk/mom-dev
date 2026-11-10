<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\DocumentType;
use App\Models\ActionItem;
use App\Models\AiSummary;
use App\Models\Decision;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\MeetingTemplate;
use App\Models\Topic;
use App\Models\User;
use App\Services\TemplateContentFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = collect([
            ['name' => 'Sekretariat & Umum', 'description' => 'Administrasi, notulensi, dan koordinasi lintas divisi.'],
            ['name' => 'Keuangan', 'description' => 'Anggaran, realisasi, dan pelaporan keuangan.'],
            ['name' => 'Teknologi Informasi', 'description' => 'Infrastruktur, aplikasi, dan keamanan informasi.'],
            ['name' => 'Sumber Daya Manusia', 'description' => 'Rekrutmen, kepegawaian, dan pengembangan SDM.'],
            ['name' => 'Operasional', 'description' => 'Operasional harian dan dukungan fasilitas.'],
        ])->mapWithKeys(function (array $data) {
            $division = Division::firstOrCreate(['slug' => Str::slug($data['name'])], $data);

            return [$division->name => $division];
        });

        $users = collect([
            ['name' => 'Admin Sistem', 'email' => 'admin@mom.test', 'role' => 'admin', 'division' => 'Teknologi Informasi', 'job_title' => 'Administrator Sistem'],
            ['name' => 'Rina Notulis', 'email' => 'notulis@mom.test', 'role' => 'notulis', 'division' => 'Sekretariat & Umum', 'job_title' => 'Sekretaris'],
            ['name' => 'Budi Manajer', 'email' => 'manager@mom.test', 'role' => 'notulis', 'division' => 'Teknologi Informasi', 'job_title' => 'Kepala Divisi IT'],
            ['name' => 'Sari Keuangan', 'email' => 'keuangan@mom.test', 'role' => 'member', 'division' => 'Keuangan', 'job_title' => 'Kepala Divisi Keuangan'],
            ['name' => 'Dewi SDM', 'email' => 'hr@mom.test', 'role' => 'member', 'division' => 'Sumber Daya Manusia', 'job_title' => 'Kepala Divisi SDM'],
            ['name' => 'Andi Staf', 'email' => 'staff@mom.test', 'role' => 'member', 'division' => 'Teknologi Informasi', 'job_title' => 'Staf IT'],
            ['name' => 'Dewi Direksi', 'email' => 'direksi@mom.test', 'role' => 'reader', 'division' => 'Sekretariat & Umum', 'job_title' => 'Direktur'],
        ])->mapWithKeys(function (array $data) use ($divisions) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'role' => $data['role'],
                    'division_id' => $divisions[$data['division']]->id,
                    'job_title' => $data['job_title'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            return [$user->email => $user];
        });

        $divisions['Teknologi Informasi']->update(['head_id' => $users['manager@mom.test']->id]);
        $divisions['Keuangan']->update(['head_id' => $users['keuangan@mom.test']->id]);
        $divisions['Sumber Daya Manusia']->update(['head_id' => $users['hr@mom.test']->id]);

        $templateFactory = app(TemplateContentFactory::class);

        $templateDefinitions = [
            ['name' => 'Rapat Mingguan Standar', 'document_type' => 'meeting', 'meeting_type' => 'weekly', 'description' => 'Agenda, pembahasan, keputusan, dan action item mingguan.'],
            ['name' => 'Rapat Divisi', 'document_type' => 'meeting', 'meeting_type' => 'division', 'description' => 'Laporan progres dan kendala divisi.'],
            ['name' => 'Rapat Manajemen', 'document_type' => 'meeting', 'meeting_type' => 'management', 'description' => 'Keputusan strategis dan lintas divisi.'],
            ['name' => 'Notulen Lintas Divisi', 'document_type' => 'meeting', 'meeting_type' => 'cross_division', 'description' => 'Koordinasi lintas divisi, keputusan bersama, dan tindak lanjut.'],
            ['name' => 'Proposal Proyek', 'document_type' => 'proposal', 'meeting_type' => 'other', 'description' => 'Usulan proyek dengan tujuan, ruang lingkup, timeline, dan anggaran.'],
            ['name' => 'Laporan Bulanan', 'document_type' => 'report', 'meeting_type' => 'other', 'description' => 'Laporan berkala dengan temuan, analisis, dan rekomendasi.'],
            ['name' => 'Brosur Produk', 'document_type' => 'brochure', 'meeting_type' => 'other', 'description' => 'Materi promosi singkat untuk produk, layanan, atau program.'],
        ];

        $templates = collect($templateDefinitions)->map(function (array $data) use ($templateFactory) {
            $type = DocumentType::from($data['document_type']);
            $content = $templateFactory->default($type);

            return MeetingTemplate::updateOrCreate(['name' => $data['name']], [
                ...$data,
                'sections' => $templateFactory->sectionTitles($content),
                'content' => $content,
                'is_active' => true,
            ]);
        });

        $topics = collect([
            ['name' => 'Anggaran', 'description' => 'Perencanaan dan realisasi anggaran.'],
            ['name' => 'Jaringan', 'description' => 'Infrastruktur jaringan dan konektivitas.'],
            ['name' => 'Rekrutmen', 'description' => 'Penambahan dan seleksi pegawai.'],
            ['name' => 'Digitalisasi', 'description' => 'Digitalisasi arsip dan proses kerja.'],
            ['name' => 'Kepatuhan', 'description' => 'Kepatuhan regulasi dan audit internal.'],
        ])->mapWithKeys(function (array $data) use ($users) {
            $topic = Topic::firstOrCreate(['slug' => Str::slug($data['name'])], [
                ...$data,
                'status' => 'active',
                'created_by' => $users['admin@mom.test']->id,
            ]);

            return [$data['name'] => $topic];
        });

        $notulis = $users['notulis@mom.test'];
        $manager = $users['manager@mom.test'];
        $keuangan = $users['keuangan@mom.test'];
        $staff = $users['staff@mom.test'];
        $direksi = $users['direksi@mom.test'];

        $meetingOne = Meeting::firstOrCreate(
            ['title' => 'Rapat Mingguan Divisi Teknologi Informasi'],
            [
                'meeting_type' => 'weekly',
                'meeting_template_id' => $templates->firstWhere('meeting_type', 'weekly')?->id,
                'division_id' => $divisions['Teknologi Informasi']->id,
                'created_by' => $notulis->id,
                'scheduled_at' => now()->subDays(3)->setTime(9, 0),
                'location' => 'Ruang Rapat Utama',
                'agenda' => 'Evaluasi gangguan jaringan, progres digitalisasi arsip, dan rencana rekrutmen staf IT.',
                'confidentiality' => 'division',
                'status' => 'draft',
                'content' => $this->content([
                    'Evaluasi gangguan jaringan minggu lalu. ',
                    ['mention', $manager->id, $manager->name, 'person'],
                    ' melaporkan pemulihan selesai dalam 2 jam. ',
                    ['mention', $divisions['Teknologi Informasi']->id, $divisions['Teknologi Informasi']->name, 'division'],
                    ' akan menyusun prosedur eskalasi baru.',
                    "\n\nPembahasan topik ",
                    ['mention', $topics['Jaringan']->id, $topics['Jaringan']->name, 'topic'],
                    ' mencakup penggantian perangkat switch lama dan penambahan bandwidth.',
                ]),
                'finalized_at' => null,
            ],
        );

        $this->participants($meetingOne, [
            [$notulis, AttendanceStatus::Present],
            [$manager, AttendanceStatus::Present],
            [$staff, AttendanceStatus::Present],
            [$direksi, AttendanceStatus::Excused],
        ]);

        $this->tags($meetingOne, [
            ['person', $manager->id, $manager->name],
            ['division', $divisions['Teknologi Informasi']->id, $divisions['Teknologi Informasi']->name],
            ['topic', $topics['Jaringan']->id, $topics['Jaringan']->name],
        ], $notulis->id);

        $meetingTwo = Meeting::firstOrCreate(
            ['title' => 'Rapat Anggaran Triwulan IV'],
            [
                'meeting_type' => 'management',
                'meeting_template_id' => $templates->firstWhere('meeting_type', 'management')?->id,
                'division_id' => $divisions['Keuangan']->id,
                'created_by' => $notulis->id,
                'scheduled_at' => now()->subDays(10)->setTime(14, 0),
                'location' => 'Ruang Direksi',
                'agenda' => 'Pembahasan pagu anggaran triwulan IV dan efisiensi belanja operasional.',
                'confidentiality' => 'restricted',
                'status' => 'final',
                'finalized_at' => now()->subDays(9),
                'approved_by' => $direksi->id,
                'content' => $this->content([
                    'Rapat dibuka oleh ',
                    ['mention', $direksi->id, $direksi->name, 'person'],
                    '. ',
                    ['mention', $keuangan->id, $keuangan->name, 'person'],
                    ' memaparkan realisasi anggaran sebesar 78%. Pembahasan ',
                    ['mention', $topics['Anggaran']->id, $topics['Anggaran']->name, 'topic'],
                    ' menyepakati efisiensi perjalanan dinas dan penundaan pengadaan non-prioritas.',
                ]),
            ],
        );

        $this->participants($meetingTwo, [
            [$notulis, AttendanceStatus::Present],
            [$keuangan, AttendanceStatus::Present],
            [$direksi, AttendanceStatus::Present],
            [$hr = $users['hr@mom.test'], AttendanceStatus::Absent],
        ]);

        $this->tags($meetingTwo, [
            ['person', $direksi->id, $direksi->name],
            ['person', $keuangan->id, $keuangan->name],
            ['division', $divisions['Keuangan']->id, $divisions['Keuangan']->name],
            ['topic', $topics['Anggaran']->id, $topics['Anggaran']->name],
        ], $notulis->id);

        Decision::firstOrCreate(['meeting_id' => $meetingTwo->id, 'content' => 'Efisiensi perjalanan dinas minimal 20% pada triwulan IV.'], [
            'topic_id' => $topics['Anggaran']->id,
            'decided_at' => now()->subDays(10),
        ]);

        Decision::firstOrCreate(['meeting_id' => $meetingTwo->id, 'content' => 'Pengadaan perangkat non-prioritas ditunda ke tahun anggaran berikutnya.'], [
            'topic_id' => $topics['Anggaran']->id,
            'decided_at' => now()->subDays(10),
        ]);

        AiSummary::firstOrCreate(['meeting_id' => $meetingTwo->id, 'version' => 1], [
            'scope' => 'meeting',
            'status' => 'approved',
            'language' => 'id',
            'model' => 'gpt-4o-mini',
            'generated_by' => $notulis->id,
            'approved_by' => $direksi->id,
            'approved_at' => now()->subDays(9),
            'content' => [
                'ringkasan' => 'Realisasi anggaran triwulan IV mencapai 78%. Rapat menyepakati efisiensi perjalanan dinas dan penundaan pengadaan non-prioritas.',
                'poin' => [
                    'Realisasi anggaran 78% dari pagu.',
                    'Efisiensi perjalanan dinas minimal 20%.',
                    'Pengadaan non-prioritas ditunda.',
                ],
                'keputusan' => [
                    'Efisiensi perjalanan dinas minimal 20%.',
                    'Penundaan pengadaan non-prioritas.',
                ],
            ],
        ]);

        $meetingThree = Meeting::firstOrCreate(
            ['title' => 'Rapat Lintas Divisi: Digitalisasi Arsip'],
            [
                'meeting_type' => 'cross_division',
                'meeting_template_id' => $templates->firstWhere('meeting_type', 'division')?->id,
                'division_id' => $divisions['Sekretariat & Umum']->id,
                'created_by' => $manager->id,
                'scheduled_at' => now()->addDays(2)->setTime(10, 0),
                'location' => 'Daring',
                'meeting_link' => 'https://meet.example.com/digitalisasi-arsip',
                'agenda' => 'Sinkronisasi kebutuhan arsip digital antara Sekretariat, IT, dan SDM.',
                'confidentiality' => 'open',
                'status' => 'draft',
                'content' => $this->content([
                    'Rencana migrasi arsip fisik ke repositori digital. ',
                    ['mention', $divisions['Sekretariat & Umum']->id, $divisions['Sekretariat & Umum']->name, 'division'],
                    ' menyiapkan inventaris, ',
                    ['mention', $divisions['Teknologi Informasi']->id, $divisions['Teknologi Informasi']->name, 'division'],
                    ' menyiapkan infrastruktur, dan ',
                    ['mention', $divisions['Sumber Daya Manusia']->id, $divisions['Sumber Daya Manusia']->name, 'division'],
                    ' menangani pelatihan pengguna. Topik terkait: ',
                    ['mention', $topics['Digitalisasi']->id, $topics['Digitalisasi']->name, 'topic'],
                    '.',
                ]),
            ],
        );

        $this->participants($meetingThree, [
            [$manager, AttendanceStatus::Invited],
            [$notulis, AttendanceStatus::Invited],
            [$users['hr@mom.test'], AttendanceStatus::Invited],
            [$staff, AttendanceStatus::Invited],
        ]);

        $this->tags($meetingThree, [
            ['division', $divisions['Sekretariat & Umum']->id, $divisions['Sekretariat & Umum']->name],
            ['division', $divisions['Teknologi Informasi']->id, $divisions['Teknologi Informasi']->name],
            ['division', $divisions['Sumber Daya Manusia']->id, $divisions['Sumber Daya Manusia']->name],
            ['topic', $topics['Digitalisasi']->id, $topics['Digitalisasi']->name],
        ], $manager->id);

        ActionItem::firstOrCreate(['meeting_id' => $meetingOne->id, 'title' => 'Susun prosedur eskalasi gangguan jaringan'], [
            'description' => 'Dokumen prosedur disosialisasikan ke seluruh staf IT.',
            'pic_id' => $manager->id,
            'division_id' => $divisions['Teknologi Informasi']->id,
            'due_date' => now()->subDays(2)->toDateString(),
            'status' => 'in_progress',
            'source' => 'manual',
        ]);

        ActionItem::firstOrCreate(['meeting_id' => $meetingOne->id, 'title' => 'Inventarisasi perangkat switch lama'], [
            'pic_id' => $staff->id,
            'division_id' => $divisions['Teknologi Informasi']->id,
            'due_date' => now()->addDays(4)->toDateString(),
            'status' => 'new',
        ]);

        ActionItem::firstOrCreate(['meeting_id' => $meetingTwo->id, 'title' => 'Revisi kebijakan perjalanan dinas'], [
            'pic_id' => $keuangan->id,
            'division_id' => $divisions['Keuangan']->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'new',
            'source' => 'ai',
            'ai_confirmed' => true,
        ]);

        ActionItem::firstOrCreate(['meeting_id' => $meetingTwo->id, 'title' => 'Kirim laporan realisasi anggaran ke direksi'], [
            'pic_id' => $keuangan->id,
            'division_id' => $divisions['Keuangan']->id,
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => 'done',
            'completed_at' => now()->subDays(5),
        ]);

        foreach ([$meetingOne, $meetingTwo, $meetingThree] as $meeting) {
            $meeting->update(['content_text' => Meeting::contentToText($meeting->content)]);
        }

        if ($meetingThree->attachments()->count() === 0) {
            $meetingThree->attachments()->create([
                'uploaded_by' => $manager->id,
                'kind' => 'link',
                'url' => 'https://example.com/rencana-digitalisasi-arsip',
                'original_name' => 'Rencana Digitalisasi Arsip.pdf',
                'mime_type' => 'application/pdf',
            ]);
        }

        Meeting::query()
            ->whereNull('content_text')
            ->get()
            ->each(fn (Meeting $meeting) => $meeting->update([
                'content_text' => Meeting::contentToText($meeting->content),
            ]));

        $this->seedNotifications($notulis->id, $manager->id, $meetingOne->id);
    }

    private function participants(Meeting $meeting, array $rows): void
    {
        foreach ($rows as [$user, $attendance]) {
            $meeting->participants()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'attendance' => $attendance,
                    'is_external' => false,
                ],
            );
        }
    }

    private function tags(Meeting $meeting, array $rows, int $userId): void
    {
        foreach ($rows as [$type, $id, $name]) {
            $meeting->tags()->firstOrCreate(
                ['type' => $type, 'target_id' => $id],
                ['target_name' => $name, 'created_by' => $userId],
            );
        }
    }

    /**
     * @param  array<int, string|array{0: string, 1: int, 2: string, 3: string}>  $parts
     */
    private function content(array $parts): array
    {
        $inline = [];

        foreach ($parts as $part) {
            if (is_array($part)) {
                $inline[] = [
                    'type' => 'mention',
                    'attrs' => ['id' => $part[1], 'label' => $part[2], 'type' => $part[3]],
                ];
            } else {
                $inline[] = ['type' => 'text', 'text' => $part];
            }
        }

        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Pembahasan']]],
                ['type' => 'paragraph', 'content' => $inline],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Keputusan']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph']]],
                ]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Action Item']]],
                ['type' => 'paragraph'],
            ],
        ];
    }

    private function seedNotifications(int $notulisId, int $managerId, int $meetingId): void
    {
        if (DB::table('notifications')->count() > 0) {
            return;
        }

        $rows = [
            [$notulisId, 'Notulen difinalkan', 'Rapat Anggaran Triwulan IV telah difinalkan.', "/notulen/{$meetingId}"],
            [$managerId, 'Anda di-tag', 'Anda disebut pada Rapat Mingguan Divisi Teknologi Informasi.', "/notulen/{$meetingId}"],
        ];

        foreach ($rows as [$userId, $title, $message, $url]) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\MeetingActivity',
                'notifiable_type' => User::class,
                'notifiable_id' => $userId,
                'data' => json_encode(['title' => $title, 'message' => $message, 'url' => $url]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
