<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\IssueType;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketSequence;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    private const TOTAL_TICKETS = 90;
    private const SLA_BREACH_TARGET = 18; // Total breached SLA: 9 overdue active + 9 resolved breached.
    private const SLA_OVERDUE_OPEN_TARGET = 9;
    private const SLA_RESOLVED_BREACH_TARGET = 9;

    private Carbon $periodStart;
    private Carbon $periodEnd;

    public function run(): void
    {
        $faker = Factory::create('id_ID');

        // Data dibuat sesuai kebutuhan laporan/demo: Januari 2026 sampai 11 Juni 2026.
        $this->periodStart = Carbon::create(2026, 1, 1, 8, 0, 0);
        $this->periodEnd = Carbon::create(2026, 6, 11, 17, 0, 0);

        $creatorIds = User::query()
            ->whereIn('role', ['admin', 'cs'])
            ->pluck('id')
            ->values();

        $resolverIds = User::query()
            ->whereIn('role', ['admin', 'it'])
            ->pluck('id')
            ->values();

        if ($creatorIds->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: user creator (admin/cs) tidak ditemukan.');
            return;
        }

        if ($resolverIds->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: user resolver (admin/it) tidak ditemukan.');
            return;
        }

        $teams = Team::query()
            ->where('is_active', true)
            ->whereNotNull('code')
            ->whereNotNull('code_num')
            ->orderBy('code_num')
            ->get();

        $itTeams = $teams
            ->filter(fn (Team $team) => $this->isItTeam($team))
            ->values();

        $nonItTeams = $teams
            ->reject(fn (Team $team) => $this->isItTeam($team))
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNotNull('code_num')
            ->orderBy('code_num')
            ->get();

        $priorities = Priority::query()
            ->where('is_active', true)
            ->whereNotNull('code')
            ->whereNotNull('code_num')
            ->orderBy('sort_order')
            ->orderBy('code_num')
            ->get();

        $issueTypesByCategory = IssueType::query()
            ->where('is_active', true)
            ->whereNotNull('category_id')
            ->whereNotNull('code_num')
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderBy('code_num')
            ->get()
            ->groupBy('category_id');

        $usableCategories = $categories
            ->filter(fn ($category) => isset($issueTypesByCategory[$category->id]) && $issueTypesByCategory[$category->id]->isNotEmpty())
            ->values();

        if ($teams->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: master data Teams aktif dengan code dan code_num belum tersedia.');
            return;
        }

        if ($itTeams->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: minimal harus ada team IT aktif agar SLA workflow bisa dibuat.');
            return;
        }

        if ($usableCategories->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: master data Categories/Issue Types aktif dengan code_num belum lengkap.');
            return;
        }

        if ($priorities->isEmpty()) {
            $this->command->warn('Seeder dibatalkan: master data Priorities aktif dengan code dan code_num belum tersedia.');
            return;
        }

        $this->resetTicketTables();

        $platformTypes = ['web', 'mobile', 'desktop'];
        $flowTypes = ['login', 'order', 'deposit', 'withdrawal', 'verification', 'reporting'];
        $plans = $this->buildTicketPlans($nonItTeams->isNotEmpty(), $faker);

        $breachedCreated = 0;

        foreach ($plans as $plan) {
            $isItTeam = (bool) $plan['is_it'];
            $status = $plan['status'];
            $mustBreachSla = (bool) $plan['breach_sla'];
            $mustBeOverdueOpen = (bool) ($plan['overdue_sla'] ?? false);

            $team = $isItTeam
                ? $itTeams->random()
                : ($nonItTeams->isNotEmpty() ? $nonItTeams->random() : $itTeams->random());

            $category = $usableCategories->random();
            $issueType = $issueTypesByCategory[$category->id]->random();
            $priority = $this->pickPriorityForPlan($priorities, $mustBreachSla || $mustBeOverdueOpen);

            // Safety net: kalau environment cuma punya team IT, semua ticket tetap dibuat valid sebagai IT ticket.
            if (! $this->isItTeam($team)) {
                $isItTeam = false;
                $status = 'closed';
                $mustBreachSla = false;
                $mustBeOverdueOpen = false;
            }

            $slaHours = $isItTeam ? $this->getSlaHoursForTicket($team, $priority) : null;
            $needsCompletionTimeline = $isItTeam && in_array($status, ['resolved', 'closed'], true);

            $createdAt = $this->makeCreatedAt(
                faker: $faker,
                needsCompletionTimeline: $needsCompletionTimeline,
                slaHours: $slaHours,
                distributionIndex: $plan['distribution_index'] ?? null,
                forcePastSlaDeadline: $mustBeOverdueOpen
            );

            $createdBy = $creatorIds->random();

            // Sesuai create ticket rule: hanya ticket IT yang masuk workflow resolver.
            $needsResolver = $isItTeam && in_array($status, ['in_progress', 'waiting_info', 'resolved', 'closed'], true);
            $holderId = $needsResolver ? $resolverIds->random() : null;

            $title = $this->makeTitle($team, $category, $issueType, $faker);
            $description = $this->makeDescription($team, $category, $issueType);
            $requestTime = $createdAt->copy()->addMinutes($faker->numberBetween(0, 20));
            $slaDeadlineAt = $slaHours ? $createdAt->copy()->addHours($slaHours) : null;

            $claimedAt = null;
            $resolvedAt = null;
            $closedAt = null;
            $updatedAt = $createdAt->copy();
            $historyRows = [];

            if (! $isItTeam) {
                // Ticket non-IT mengikuti rule create ticket di TicketService: langsung closed dan tidak memakai SLA IT.
                $status = 'closed';
                $closedAt = $this->clampToPeriodEnd($createdAt->copy()->addMinutes($faker->numberBetween(5, 60)));
                $resolvedAt = $closedAt->copy();
                $updatedAt = $closedAt->copy();
                $holderId = null;
                $claimedAt = null;
                $slaDeadlineAt = null;

                $historyRows[] = $this->makeHistoryRow(
                    fromStatus: null,
                    toStatus: 'closed',
                    changedBy: $createdBy,
                    changedAt: $closedAt->copy(),
                    note: 'Ticket auto-closed because it is routed to a non-IT team.'
                );
            } else {
                $historyRows[] = $this->makeHistoryRow(
                    fromStatus: null,
                    toStatus: 'new',
                    changedBy: $createdBy,
                    changedAt: $createdAt->copy(),
                    note: 'Initial status on ticket creation'
                );

                if ($status !== 'new') {
                    $claimedAt = $this->clampToPeriodEnd($createdAt->copy()->addMinutes($faker->numberBetween(5, 90)));
                    $updatedAt = $claimedAt->copy();

                    $historyRows[] = $this->makeHistoryRow(
                        fromStatus: 'new',
                        toStatus: 'in_progress',
                        changedBy: $holderId,
                        changedAt: $claimedAt->copy(),
                        note: 'Ticket claimed by resolver'
                    );

                    if ($status === 'waiting_info') {
                        $waitingAt = $this->clampToPeriodEnd($claimedAt->copy()->addMinutes($faker->numberBetween(15, 120)));
                        $updatedAt = $waitingAt->copy();

                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: 'in_progress',
                            toStatus: 'waiting_info',
                            changedBy: $holderId,
                            changedAt: $waitingAt->copy(),
                            note: 'Ticket requires additional customer information'
                        );
                    }

                    if ($needsCompletionTimeline) {
                        $baseResolvedAt = $this->makeResolvedAtNearSla(
                            createdAt: $createdAt,
                            claimedAt: $claimedAt,
                            slaDeadlineAt: $slaDeadlineAt,
                            isBreached: $mustBreachSla,
                            faker: $faker
                        );

                        $finalResolvedAt = $baseResolvedAt->copy();
                        $reopened = ! $mustBreachSla && $faker->boolean(10);

                        if ($reopened) {
                            $reopenedAt = $this->clampToPeriodEnd($baseResolvedAt->copy()->addMinutes($faker->numberBetween(15, 120)));
                            $finalResolvedAt = $this->clampToPeriodEnd($reopenedAt->copy()->addMinutes($faker->numberBetween(20, 180)));

                            // Jangan sampai reopen membuat ticket non-breach berubah jadi breach karena melewati SLA.
                            if ($slaDeadlineAt && $finalResolvedAt->greaterThan($slaDeadlineAt)) {
                                $reopened = false;
                                $finalResolvedAt = $baseResolvedAt->copy();
                            }
                        }

                        if ($reopened) {
                            $historyRows[] = $this->makeHistoryRow(
                                fromStatus: 'in_progress',
                                toStatus: 'resolved',
                                changedBy: $holderId,
                                changedAt: $baseResolvedAt->copy(),
                                note: 'Issue resolved'
                            );

                            $historyRows[] = $this->makeHistoryRow(
                                fromStatus: 'resolved',
                                toStatus: 'in_progress',
                                changedBy: $createdBy,
                                changedAt: $baseResolvedAt->copy()->addMinutes(20),
                                note: 'Ticket reopened due to recurring issue'
                            );

                            $historyRows[] = $this->makeHistoryRow(
                                fromStatus: 'in_progress',
                                toStatus: 'resolved',
                                changedBy: $holderId,
                                changedAt: $finalResolvedAt->copy(),
                                note: 'Issue resolved after reopen'
                            );
                        } else {
                            $historyRows[] = $this->makeHistoryRow(
                                fromStatus: 'in_progress',
                                toStatus: 'resolved',
                                changedBy: $holderId,
                                changedAt: $finalResolvedAt->copy(),
                                note: $mustBreachSla ? 'Issue resolved after SLA deadline' : 'Issue resolved'
                            );
                        }

                        $resolvedAt = $finalResolvedAt->copy();
                        $updatedAt = $resolvedAt->copy();

                        if ($mustBreachSla) {
                            $breachedCreated++;
                        }

                        if ($status === 'closed') {
                            $closedAt = $this->clampToPeriodEnd($resolvedAt->copy()->addMinutes($faker->numberBetween(5, 120)));
                            $updatedAt = $closedAt->copy();

                            $historyRows[] = $this->makeHistoryRow(
                                fromStatus: 'resolved',
                                toStatus: 'closed',
                                changedBy: $createdBy,
                                changedAt: $closedAt->copy(),
                                note: 'Ticket closed'
                            );
                        }
                    }
                }
            }

            $clientName = $faker->name();
            $clientContact = $this->makeValidClientContact($faker);
            $clientEmail = $faker->safeEmail();

            $client = Client::resolveForTicket([
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'client_email' => $clientEmail,
            ]);

            $ticket = Ticket::query()->create([
                // Database menyimpan structured numeric code. UI yang menampilkan prefix T-.
                'ticket_code' => $this->generateStructuredTicketCode($team, $category, $issueType, $priority),
                'title' => $title,
                'description' => $description,
                'status' => $status,

                // Snapshot string dari Master Data supaya backward-compatible dengan page/report lama.
                'priority' => $this->normalizeCode($priority->code ?: $priority->name),
                'team' => $this->normalizeCode($team->code ?: $team->name),
                'team_id' => $team->id,
                'priority_id' => $priority->id,
                'category' => $category->name,
                'category_id' => $category->id,
                'issue_type' => $issueType->name,
                'issue_type_id' => $issueType->id,

                'client_id' => $client?->id,
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'client_email' => $clientEmail,
                'platform_type' => $faker->randomElement($platformTypes),
                'amount' => (string) $faker->numberBetween(100000, 50000000),
                'flow_type' => $faker->randomElement($flowTypes),
                'request_time' => $requestTime,
                'internal_notes' => $faker->optional(0.45)->sentence(),
                'created_by' => $createdBy,
                'holder_id' => $holderId,
                'claimed_at' => $claimedAt,
                'resolved_at' => $resolvedAt,
                'closed_at' => $closedAt,
                'created_at' => $createdAt,
                'sla_deadline_at' => $slaDeadlineAt,
                'updated_at' => $updatedAt,
            ]);

            foreach ($historyRows as $row) {
                $row['ticket_id'] = $ticket->id;
                DB::table('ticket_status_histories')->insert($row);
            }
        }

        $this->command->info(sprintf(
'%d tickets seeded successfully from 2026-01-01 to 2026-06-11. SLA overdue open: %d, resolved breached: %d.',
            self::TOTAL_TICKETS,
            self::SLA_OVERDUE_OPEN_TARGET,
            $breachedCreated
        ));
    }

    private function resetTicketTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'resolver_messages',
            'ticket_attachments',
            'ticket_status_histories',
            'tickets',
            'ticket_sequences',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Plan sengaja dibuat stabil supaya demo dashboard/report konsisten:
     * - 9 ticket IT masih active overdue: status in_progress/waiting_info, SLA deadline sudah lewat.
     * - 9 ticket IT sudah resolved tetapi resolved_at melewati SLA deadline.
     * - Sisanya tetap campuran normal untuk dashboard, queue, history, dan report.
     */
    private function buildTicketPlans(bool $hasNonItTeams, FakerGenerator $faker): array
    {
        $plans = [];

        for ($i = 0; $i < self::SLA_OVERDUE_OPEN_TARGET; $i++) {
            $plans[] = [
                'is_it' => true,
                'status' => $i % 2 === 0 ? 'in_progress' : 'waiting_info',
                'breach_sla' => false,
                'overdue_sla' => true,
                'distribution_index' => $i,
            ];
        }

        for ($i = 0; $i < self::SLA_RESOLVED_BREACH_TARGET; $i++) {
            $plans[] = [
                'is_it' => true,
                'status' => 'resolved',
                'breach_sla' => true,
                'overdue_sla' => false,
                'distribution_index' => $i + self::SLA_OVERDUE_OPEN_TARGET,
            ];
        }

        $itStatusQuotas = [
            'new' => 10,
            'in_progress' => 8,
            'waiting_info' => 7,
            'resolved' => 8,
            'closed' => 9,
        ];

        foreach ($itStatusQuotas as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $plans[] = [
                    'is_it' => true,
                    'status' => $status,
                    'breach_sla' => false,
                    'overdue_sla' => false,
                    'distribution_index' => null,
                ];
            }
        }

        $nonItCount = self::TOTAL_TICKETS - count($plans);

        for ($i = 0; $i < $nonItCount; $i++) {
            $plans[] = [
                'is_it' => $hasNonItTeams ? false : true,
                'status' => $hasNonItTeams ? 'closed' : 'new',
                'breach_sla' => false,
                'overdue_sla' => false,
                'distribution_index' => null,
            ];
        }

        $plans = $faker->shuffleArray($plans);

        return array_slice($plans, 0, self::TOTAL_TICKETS);
    }

    private function makeCreatedAt(
        FakerGenerator $faker,
        bool $needsCompletionTimeline,
        ?int $slaHours,
        ?int $distributionIndex = null,
        bool $forcePastSlaDeadline = false
    ): Carbon {
        if ($distributionIndex !== null) {
            return $this->makeDistributedCreatedAt(
                faker: $faker,
                distributionIndex: $distributionIndex,
                slaHours: $slaHours,
                forcePastSlaDeadline: $forcePastSlaDeadline || $needsCompletionTimeline
            );
        }

        $latest = $this->periodEnd->copy();

        if ($needsCompletionTimeline) {
            $latest = $this->periodEnd->copy()->subHours(($slaHours ?: 24) + 8);
        }

        if ($latest->lessThanOrEqualTo($this->periodStart)) {
            $latest = $this->periodEnd->copy()->subDays(2);
        }

        return Carbon::instance($faker->dateTimeBetween($this->periodStart, $latest));
    }

    private function makeDistributedCreatedAt(
        FakerGenerator $faker,
        int $distributionIndex,
        ?int $slaHours,
        bool $forcePastSlaDeadline
    ): Carbon {
        $windows = [
            [Carbon::create(2026, 1, 3, 8, 0, 0), Carbon::create(2026, 1, 27, 17, 0, 0)],
            [Carbon::create(2026, 2, 3, 8, 0, 0), Carbon::create(2026, 2, 24, 17, 0, 0)],
            [Carbon::create(2026, 3, 3, 8, 0, 0), Carbon::create(2026, 3, 27, 17, 0, 0)],
            [Carbon::create(2026, 4, 3, 8, 0, 0), Carbon::create(2026, 4, 27, 17, 0, 0)],
            [Carbon::create(2026, 5, 3, 8, 0, 0), Carbon::create(2026, 5, 27, 17, 0, 0)],
            [Carbon::create(2026, 6, 1, 8, 0, 0), Carbon::create(2026, 6, 8, 17, 0, 0)],
        ];

        [$start, $end] = $windows[$distributionIndex % count($windows)];

        if ($forcePastSlaDeadline) {
            $end = $end->min($this->periodEnd->copy()->subHours(($slaHours ?: 24) + 24));
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addDays(1);
        }

        return Carbon::instance($faker->dateTimeBetween($start, $end));
    }

    private function pickPriorityForPlan($priorities, bool $isSlaSpecial): Priority
    {
        if (! $isSlaSpecial) {
            return $priorities->random();
        }

        $slaFriendlyPriorities = $priorities
            ->filter(fn (Priority $priority) => in_array(
                $this->normalizeCode($priority->code ?: $priority->name),
                ['critical', 'high', 'medium'],
                true
            ))
            ->values();

        return $slaFriendlyPriorities->isNotEmpty()
            ? $slaFriendlyPriorities->random()
            : $priorities->random();
    }

    private function getSlaHoursForTicket(Team $team, Priority $priority): ?int
    {
        if (Schema::hasTable('sla_rules')) {
            $rule = SlaRule::query()
                ->where('team_id', $team->id)
                ->where('priority_id', $priority->id)
                ->where('is_active', true)
                ->first();

            if ($rule) {
                return (int) $rule->hours;
            }
        }

        return match ($this->normalizeCode($priority->code ?: $priority->name)) {
            'critical' => 2,
            'high' => 6,
            'medium' => 12,
            'low' => 24,
            default => 24,
        };
    }

    private function makeResolvedAtNearSla(
        Carbon $createdAt,
        Carbon $claimedAt,
        ?Carbon $slaDeadlineAt,
        bool $isBreached,
        FakerGenerator $faker
    ): Carbon {
        if (! $slaDeadlineAt) {
            $slaDeadlineAt = $createdAt->copy()->addHours(24);
        }

        if ($isBreached) {
            // Resolved breached SLA: tetap selesai, tapi 1/2 sampai 1 hari setelah ticket di-claim.
            $resolvedAt = $claimedAt->copy()->addMinutes($faker->numberBetween(720, 1440));

            if ($resolvedAt->lessThanOrEqualTo($slaDeadlineAt)) {
                $resolvedAt = $slaDeadlineAt->copy()->addMinutes($faker->numberBetween(30, 180));
            }
        } else {
            $resolvedAt = $slaDeadlineAt->copy()->subMinutes($faker->numberBetween(30, 180));
        }

        $minimumWorkFinish = $claimedAt->copy()->addMinutes($faker->numberBetween(20, 90));

        if ($resolvedAt->lessThan($minimumWorkFinish)) {
            $resolvedAt = $minimumWorkFinish;
        }

        if (! $isBreached && $resolvedAt->greaterThanOrEqualTo($slaDeadlineAt)) {
            $resolvedAt = $slaDeadlineAt->copy()->subMinutes(15);
        }

        if ($resolvedAt->lessThan($createdAt)) {
            $resolvedAt = $createdAt->copy()->addMinutes(30);
        }

        return $this->clampToPeriodEnd($resolvedAt);
    }

    private function generateStructuredTicketCode(
        Team $team,
        Category $category,
        IssueType $issueType,
        Priority $priority
    ): string {
        $prefix =
            $this->padCode($team->code_num, 1) .
            $this->padCode($category->code_num, 2) .
            $this->padCode($issueType->code_num, 3) .
            $this->padCode($priority->code_num, 1);

        return TicketSequence::nextCode($prefix);
    }

    private function padCode(null|string|int $value, int $length): string
    {
        return str_pad((string) $value, $length, '0', STR_PAD_LEFT);
    }

    private function normalizeCode(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->toString();
    }

    private function isItTeam(Team $team): bool
    {
        return $this->normalizeCode($team->code ?: $team->name) === 'it';
    }

    private function makeValidClientContact(FakerGenerator $faker): string
    {
        // Create Ticket validation: numeric only, maksimal 13 digit.
        return '08' . $faker->numerify('##########');
    }

    private function clampToPeriodEnd(Carbon $date): Carbon
    {
        return $date->greaterThan($this->periodEnd)
            ? $this->periodEnd->copy()
            : $date;
    }

    private function makeTitle(Team $team, Category $category, IssueType $issueType, FakerGenerator $faker): string
    {
        $teamCode = $this->normalizeCode($team->code ?: $team->name);
        $categoryName = $category->name;
        $issueName = $issueType->name;

        $templates = match ($teamCode) {
            'it' => [
                "Aplikasi mengalami kendala {$issueName}",
                "Kendala sistem kategori {$categoryName}",
                "Error pada fitur {$issueName}",
                "Performa aplikasi lambat saat proses {$issueName}",
            ],
            'finance' => [
                "Masalah dana: {$issueName}",
                "Permintaan pengecekan transaksi {$categoryName}",
                "Saldo atau mutasi bermasalah",
                "Validasi transaksi membutuhkan pengecekan finance",
            ],
            'compliance' => [
                "Kendala verifikasi: {$issueName}",
                "Review dokumen tertunda",
                "Permasalahan compliance pada akun client",
                "Data client membutuhkan validasi compliance",
            ],
            default => [
                "Kendala {$issueName}",
                "Permintaan bantuan kategori {$categoryName}",
                "Ticket terkait {$issueName}",
            ],
        };

        return $faker->randomElement($templates);
    }

    private function makeDescription(Team $team, Category $category, IssueType $issueType): string
    {
        $teamCode = $this->normalizeCode($team->code ?: $team->name);
        $categoryName = $category->name;
        $issueName = $issueType->name;

        return match ($teamCode) {
            'it' => "Client melaporkan kendala {$issueName} pada kategori {$categoryName}. Mohon dilakukan pengecekan pada aplikasi atau sistem terkait agar operasional kembali normal.",
            'finance' => "Client mengalami masalah {$issueName} pada proses {$categoryName}. Dibutuhkan pengecekan lebih lanjut terhadap transaksi dan status dana.",
            'compliance' => "Client membutuhkan tindak lanjut untuk {$issueName} dalam area {$categoryName}. Mohon review dan validasi sesuai prosedur compliance.",
            default => "Client melaporkan kendala {$issueName} pada kategori {$categoryName}. Mohon dilakukan pengecekan dan tindak lanjut sesuai alur operasional.",
        };
    }

    private function makeHistoryRow(
        ?string $fromStatus,
        string $toStatus,
        ?int $changedBy,
        Carbon $changedAt,
        ?string $note = null
    ): array {
        return [
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedBy,
            'changed_at' => $changedAt,
            'note' => $note,
            'created_at' => $changedAt,
            'updated_at' => $changedAt,
        ];
    }
}
