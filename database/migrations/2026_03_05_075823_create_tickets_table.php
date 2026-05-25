<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('ticket_code', 20)->nullable()->unique();

            // Ticket content
            $table->string('title');
            $table->text('description');

            // Workflow
            $table->enum('status', ['new', 'in_progress', 'waiting_info', 'resolved', 'closed'])
                ->default('new');

            $table->string('priority', 50)
                ->default('medium');

            $table->string('team', 50)
                ->default('it');

            // Classification
            $table->string('category', 50)->nullable();
            $table->string('issue_type', 80)->nullable();

            // Client / request information
            $table->string('client_name')->nullable();
            $table->string('client_contact', 100)->nullable();
            $table->string('client_email')->nullable();

            $table->string('platform_type', 50)->nullable();
            $table->string('amount', 100)->nullable();
            $table->string('flow_type', 50)->nullable();
            $table->dateTime('request_time')->nullable();

            // Internal notes
            $table->text('internal_notes')->nullable();

            // Ownership
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('holder_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // SLA and lifecycle timestamps
            $table->dateTime('sla_deadline_at')->nullable();
            $table->dateTime('claimed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'priority', 'team']);
            $table->index(['category', 'issue_type']);
            $table->index('sla_deadline_at');
            $table->index('created_by');
            $table->index('holder_id');
            $table->index('claimed_at');
            $table->index('resolved_at');
            $table->index('closed_at');
            $table->index('request_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};