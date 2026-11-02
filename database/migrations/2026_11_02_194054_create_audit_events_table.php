<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Append-only by application policy and deployment grants. The runtime
     * database user must not receive UPDATE or DELETE privileges on this table.
     */
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('subject_type', 120);
            $table->string('subject_id', 64);
            $table->string('reason', 500);
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->uuid('correlation_id')->index();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['organization_id', 'subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
