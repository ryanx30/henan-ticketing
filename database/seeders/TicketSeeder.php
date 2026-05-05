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
    public function run(): void
    {
        $faker = Factory::create('id_ID');

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
        $statuses = ['new', 'in_progress', 'waiting_info', 'resolved', 'closed'];

        for ($i = 1; $i <= 80; $i++) {
            $team = $teams->random();
            $category = $usableCategories->random();
            $issueType = $issueTypesByCategory[$category->id]->random();
            $priority = $priorities->random();
            $status = $faker->randomElement($statuses);

            $createdAt = Carbon::instance($faker->dateTimeBetween('-4 months', '-1 day'));
            $createdBy = $creatorIds->random();

            $needsResolver = in_array($status, ['in_progress', 'waiting_info', 'resolved', 'closed'], true);
            $holderId = $needsResolver ? $resolverIds->random() : null;

            $title = $this->makeTitle($team, $category, $issueType, $faker);
            $description = $this->makeDescription($team, $category, $issueType);

            $requestTime = $createdAt->copy()->addMinutes($faker->numberBetween(0, 20));
            $slaHours = $this->getSlaHoursForTicket($team, $priority);
            $slaDeadlineAt = $slaHours ? $createdAt->copy()->addHours($slaHours) : null;

            $claimedAt = null;
            $resolvedAt = null;
            $closedAt = null;
            $updatedAt = $createdAt->copy();
            $historyRows = [];

            $historyRows[] = $this->makeHistoryRow(
                fromStatus: null,
                toStatus: 'new',
                changedBy: $createdBy,
                changedAt: $createdAt->copy(),
                note: 'Initial status on ticket creation'
            );

            if ($status !== 'new') {
                $claimedAt = $createdAt->copy()->addMinutes($faker->numberBetween(5, 90));
                $updatedAt = $claimedAt->copy();

                $historyRows[] = $this->makeHistoryRow(
                    fromStatus: 'new',
                    toStatus: 'in_progress',
                    changedBy: $holderId,
                    changedAt: $claimedAt->copy(),
                    note: 'Ticket claimed by resolver'
                );

                if ($status === 'waiting_info') {
                    $waitingAt = $claimedAt->copy()->addMinutes($faker->numberBetween(15, 120));
                    $updatedAt = $waitingAt->copy();

                    $historyRows[] = $this->makeHistoryRow(
                        fromStatus: 'in_progress',
                        toStatus: 'waiting_info',
                        changedBy: $holderId,
                        changedAt: $waitingAt->copy(),
                        note: 'Ticket requires additional customer information'
                    );
                }

                if (in_array($status, ['resolved', 'closed'], true)) {
                    $isBreached = $faker->boolean(30);

                    $baseResolvedAt = $this->makeResolvedAtNearSla(
                        createdAt: $createdAt,
                        claimedAt: $claimedAt,
                        slaDeadlineAt: $slaDeadlineAt,
                        isBreached: $isBreached,
                        faker: $faker
                    );

                    $finalResolvedAt = $baseResolvedAt->copy();
                    $reopened = $faker->boolean(12);

                    if ($reopened) {
                        $reopenedAt = $baseResolvedAt->copy()->addMinutes($faker->numberBetween(15, 120));
                        $finalResolvedAt = $reopenedAt->copy()->addMinutes($faker->numberBetween(20, 180));

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
                            changedAt: $reopenedAt->copy(),
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
                            note: 'Issue resolved'
                        );
                    }

                    $resolvedAt = $finalResolvedAt->copy();
                    $updatedAt = $resolvedAt->copy();

                    if ($status === 'closed') {
                        $closedAt = $resolvedAt->copy()->addMinutes($faker->numberBetween(5, 120));
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

            $clientName = $faker->optional(0.85)->name();
            $clientContact = $faker->optional(0.85)->phoneNumber();
            $clientEmail = $faker->optional(0.8)->safeEmail();

            $client = Client::resolveForTicket([
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'client_email' => $clientEmail,
            ]);

            $ticket = Ticket::query()->create([
                // Database menyimpan angka structured code saja.
                // UI index/detail yang menampilkan prefix T- agar terlihat sebagai T-code.
                'ticket_code' => $this->generateStructuredTicketCode($team, $category, $issueType, $priority),
                'title' => $title,
                'description' => $description,
                'status' => $status,

                // Snapshot string dari Master Data supaya backward-compatible dengan page lama/report lama.
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
                'platform_type' => $faker->optional(0.75)->randomElement($platformTypes),
                'amount' => $faker->optional(0.45)->numberBetween(100000, 50000000),
                'flow_type' => $faker->optional(0.65)->randomElement($flowTypes),
                'request_time' => $requestTime,
                'internal_notes' => $faker->optional(0.35)->sentence(),
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

        $this->command->info('80 tickets seeded successfully with updated Master Data ticket code format.');
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
            'critical' => 6,
            'high' => 12,
            'medium' => 18,
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
        if (!$slaDeadlineAt) {
            $slaDeadlineAt = $createdAt->copy()->addHours(24);
        }

        if ($isBreached) {
            $resolvedAt = $slaDeadlineAt->copy()->addMinutes($faker->numberBetween(10, 180));
        } else {
            $resolvedAt = $slaDeadlineAt->copy()->subMinutes($faker->numberBetween(15, 120));
        }

        $minimumWorkFinish = $claimedAt->copy()->addMinutes($faker->numberBetween(20, 90));

        if ($resolvedAt->lessThan($minimumWorkFinish)) {
            $resolvedAt = $minimumWorkFinish;
        }

        if ($resolvedAt->lessThan($createdAt)) {
            $resolvedAt = $createdAt->copy()->addMinutes(30);
        }

        return $resolvedAt;
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

    private function makeTitle(Team $team, Category $category, IssueType $issueType, FakerGenerator $faker): string
    {
        $teamCode = $this->normalizeCode($team->code ?: $team->name);
        $categoryName = $category->name;
        $issueName = $issueType->name;

        $templates = match ($teamCode) {
            'it' => [
                "Aplikasi bermasalah pada {$issueName}",
                "Kendala sistem kategori {$categoryName}",
                "Error pada fitur {$issueName}",
            ],
            'finance' => [
                "Masalah dana: {$issueName}",
                "Permintaan pengecekan transaksi {$categoryName}",
                "Saldo atau mutasi bermasalah",
            ],
            'compliance' => [
                "Kendala verifikasi: {$issueName}",
                "Review dokumen tertunda",
                "Permasalahan compliance pada akun client",
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
