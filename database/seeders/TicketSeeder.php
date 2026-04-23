<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        $catalog = [
            'it' => [
                'trading_orders' => [
                    'order_status_mismatch',
                    'order_pending',
                    'order_execution_error',
                    'order_stuck',
                ],
                'app_technical' => [
                    'performance_slow',
                    'app_crash',
                    'sync_issue',
                    'notification_bug',
                ],
                'portfolio_reports' => [
                    'pnl_wrong',
                    'portfolio_not_updated',
                    'statement_mismatch',
                ],
                'account_access' => [
                    'login_auth',
                    'reset_password',
                    'otp_not_received',
                    'session_expired',
                ],
            ],
            'finance' => [
                'funds' => [
                    'deposit_not_reflected',
                    'withdraw_pending',
                    'rdn_transfer_delay',
                    'balance_not_updated',
                ],
                'billing' => [
                    'fee_mismatch',
                    'charge_unknown',
                    'invoice_request',
                ],
                'settlement' => [
                    'settlement_delay',
                    'fund_release_issue',
                ],
            ],
            'compliance' => [
                'kyc_compliance' => [
                    'kyc_pending',
                    'document_rejected',
                    'name_mismatch',
                    'selfie_verification_failed',
                ],
                'account_restriction' => [
                    'account_blocked',
                    'source_of_funds_check',
                    'suspicious_activity_review',
                ],
                'document_review' => [
                    'document_expired',
                    'incomplete_documents',
                ],
            ],
        ];

        $platformTypes = ['web', 'mobile', 'desktop'];
        $flowTypes = ['login', 'order', 'deposit', 'withdrawal', 'verification', 'reporting'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $statuses = ['new', 'in_progress', 'waiting_info', 'resolved', 'closed'];

        $slaHoursByPriority = [
            'critical' => 2,
            'high'     => 6,
            'medium'   => 12,
            'low'      => 24,
        ];

        for ($i = 1; $i <= 80; $i++) {
            $team = $faker->randomElement(array_keys($catalog));
            $category = $faker->randomElement(array_keys($catalog[$team]));
            $issueType = $faker->randomElement($catalog[$team][$category]);

            $priority = $faker->randomElement($priorities);
            $status = $faker->randomElement($statuses);

            $createdAt = Carbon::instance(
                $faker->dateTimeBetween('-4 months', '-1 day')
            );

            $createdBy = $creatorIds->random();
            $holderId = in_array($status, ['in_progress', 'waiting_info', 'resolved', 'closed'], true)
                ? $resolverIds->random()
                : null;

            $title = $this->makeTitle($team, $category, $issueType, $faker);
            $description = $this->makeDescription($team, $category, $issueType);

            $requestTime = (clone $createdAt)->addMinutes($faker->numberBetween(0, 20));
            $slaDeadlineAt = (clone $createdAt)->addHours($slaHoursByPriority[$priority]);

            $claimedAt = null;
            $resolvedAt = null;
            $closedAt = null;
            $updatedAt = clone $createdAt;

            $historyRows = [];

            $historyRows[] = $this->makeHistoryRow(
                fromStatus: null,
                toStatus: 'new',
                changedBy: $createdBy,
                changedAt: clone $createdAt,
                note: 'Initial status on ticket creation'
            );

            if ($status !== 'new') {
                // First response / claim: realistis, dekat ke waktu dibuat
                $claimedAt = (clone $createdAt)->addMinutes($faker->numberBetween(5, 90));
                $updatedAt = clone $claimedAt;

                $nextStatusAfterClaim = $status === 'waiting_info' ? 'waiting_info' : 'in_progress';

                $historyRows[] = $this->makeHistoryRow(
                    fromStatus: 'new',
                    toStatus: $nextStatusAfterClaim,
                    changedBy: $holderId,
                    changedAt: clone $claimedAt,
                    note: $nextStatusAfterClaim === 'waiting_info'
                        ? 'Ticket requires additional customer information'
                        : 'Ticket claimed by resolver'
                );

                if (in_array($status, ['resolved', 'closed'], true)) {
                    // 70% met SLA, 30% breached tipis
                    $isBreached = $faker->boolean(30);

                    $baseResolvedAt = $this->makeResolvedAtNearSla(
                        createdAt: $createdAt,
                        claimedAt: $claimedAt,
                        slaDeadlineAt: $slaDeadlineAt,
                        isBreached: $isBreached,
                        faker: $faker
                    );

                    $finalResolvedAt = clone $baseResolvedAt;

                    // reopen kecil-kecilan, jangan bikin ngawur
                    $reopened = $faker->boolean(12);

                    if ($reopened) {
                        $reopenedAt = (clone $baseResolvedAt)->addMinutes($faker->numberBetween(15, 120));
                        $finalResolvedAt = (clone $reopenedAt)->addMinutes($faker->numberBetween(20, 180));

                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: $nextStatusAfterClaim,
                            toStatus: 'resolved',
                            changedBy: $holderId,
                            changedAt: clone $baseResolvedAt,
                            note: 'Issue resolved'
                        );

                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: 'resolved',
                            toStatus: 'in_progress',
                            changedBy: $createdBy,
                            changedAt: clone $reopenedAt,
                            note: 'Ticket reopened due to recurring issue'
                        );

                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: 'in_progress',
                            toStatus: 'resolved',
                            changedBy: $holderId,
                            changedAt: clone $finalResolvedAt,
                            note: 'Issue resolved after reopen'
                        );
                    } else {
                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: $nextStatusAfterClaim,
                            toStatus: 'resolved',
                            changedBy: $holderId,
                            changedAt: clone $finalResolvedAt,
                            note: 'Issue resolved'
                        );
                    }

                    $resolvedAt = clone $finalResolvedAt;
                    $updatedAt = clone $resolvedAt;

                    if ($status === 'closed') {
                        // close cepat setelah resolved, jangan beda berhari-hari
                        $closedAt = (clone $resolvedAt)->addMinutes($faker->numberBetween(5, 120));
                        $updatedAt = clone $closedAt;

                        $historyRows[] = $this->makeHistoryRow(
                            fromStatus: 'resolved',
                            toStatus: 'closed',
                            changedBy: $createdBy,
                            changedAt: clone $closedAt,
                            note: 'Ticket closed'
                        );
                    }
                }
            }

            $ticket = Ticket::query()->create([
                'ticket_code' => $this->generateTicketCode($faker),
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'team' => $team,
                'category' => $category,
                'issue_type' => $issueType,
                'client_name' => $faker->optional(0.85)->name(),
                'client_contact' => $faker->optional(0.85)->phoneNumber(),
                'client_email' => $faker->optional(0.8)->safeEmail(),
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

        $this->command->info('80 random tickets seeded successfully.');
    }

    private function makeResolvedAtNearSla(
        Carbon $createdAt,
        Carbon $claimedAt,
        Carbon $slaDeadlineAt,
        bool $isBreached,
        $faker
    ): Carbon {
        if ($isBreached) {
            // breached tipis: telat 10 menit sampai 3 jam
            $resolvedAt = (clone $slaDeadlineAt)->addMinutes($faker->numberBetween(10, 180));
        } else {
            // met SLA: selesai 15 menit sampai 2 jam sebelum SLA
            $resolvedAt = (clone $slaDeadlineAt)->subMinutes($faker->numberBetween(15, 120));
        }

        // jaga supaya resolved_at tidak lebih awal dari claimed_at + sedikit waktu kerja
        $minimumWorkFinish = (clone $claimedAt)->addMinutes($faker->numberBetween(20, 90));

        if ($resolvedAt->lessThan($minimumWorkFinish)) {
            $resolvedAt = $minimumWorkFinish;
        }

        // jaga juga supaya tidak absurd jauh dari created_at
        if ($resolvedAt->lessThan($createdAt)) {
            $resolvedAt = (clone $createdAt)->addMinutes(30);
        }

        return $resolvedAt;
    }

    private function generateTicketCode($faker): string
    {
        do {
            $code = $faker->numerify('10####');
        } while (Ticket::query()->where('ticket_code', $code)->exists());

        return $code;
    }

    private function makeTitle(string $team, string $category, string $issueType, $faker): string
    {
        $templates = [
            'it' => [
                'Aplikasi bermasalah pada ' . str_replace('_', ' ', $issueType),
                'Kendala sistem kategori ' . str_replace('_', ' ', $category),
                'Error pada fitur ' . str_replace('_', ' ', $issueType),
            ],
            'finance' => [
                'Masalah dana: ' . str_replace('_', ' ', $issueType),
                'Permintaan pengecekan transaksi ' . str_replace('_', ' ', $category),
                'Saldo / mutasi bermasalah',
            ],
            'compliance' => [
                'Kendala verifikasi: ' . str_replace('_', ' ', $issueType),
                'Review dokumen tertunda',
                'Permasalahan compliance pada akun client',
            ],
        ];

        return $faker->randomElement($templates[$team]);
    }

    private function makeDescription(string $team, string $category, string $issueType): string
    {
        return match ($team) {
            'it' => "Client melaporkan kendala {$issueType} pada kategori {$category}. Mohon dilakukan pengecekan pada aplikasi atau sistem terkait agar operasional kembali normal.",
            'finance' => "Client mengalami masalah {$issueType} pada proses {$category}. Dibutuhkan pengecekan lebih lanjut terhadap transaksi dan status dana.",
            'compliance' => "Client membutuhkan tindak lanjut untuk {$issueType} dalam area {$category}. Mohon review dan validasi sesuai prosedur compliance.",
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