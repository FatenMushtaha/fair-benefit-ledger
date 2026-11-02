<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table): void {
            $table->unsignedBigInteger('current_cycle_run_id')->nullable()->after('automation_mode');
            $table->foreign(['organization_id', 'current_cycle_run_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_runs')
                ->nullOnDelete();
        });

        Schema::create('cycle_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('rule_version_id');
            $table->unsignedInteger('run_sequence');
            $table->string('input_fingerprint');
            $table->string('status')->default('completed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'rule_version_id'])
                ->references(['organization_id', 'id'])
                ->on('rule_versions')
                ->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'cycle_id', 'run_sequence']);
            $table->index(['organization_id', 'cycle_id']);
        });

        Schema::create('applicant_scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('cycle_run_id');
            $table->unsignedBigInteger('cycle_applicant_id');
            $table->json('criterion_points');
            $table->unsignedBigInteger('weighted_numerator');
            $table->decimal('display_score', 8, 2);
            $table->json('missing_flags')->nullable();
            $table->json('reason_snapshot')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_run_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_runs')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_applicant_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_applicants')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'cycle_run_id', 'cycle_applicant_id']);
            $table->index(['organization_id', 'cycle_run_id']);
        });

        Schema::create('cycle_results', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('cycle_run_id');
            $table->unsignedBigInteger('cycle_applicant_id');
            $table->unsignedInteger('rank');
            $table->string('outcome');
            $table->json('tie_values')->nullable();
            $table->boolean('cutoff_marker')->default(false);
            $table->json('reason_snapshot')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_run_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_runs')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_applicant_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_applicants')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'cycle_run_id', 'cycle_applicant_id']);
            $table->index(['organization_id', 'cycle_run_id', 'rank']);
            $table->index(['organization_id', 'cycle_run_id', 'outcome']);
        });

        Schema::create('decisions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('cycle_run_id');
            $table->unsignedBigInteger('cycle_result_id');
            $table->string('outcome');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('actor_id');
            $table->timestamp('decided_at');
            $table->unsignedBigInteger('supersedes_id')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_run_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_runs')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_result_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_results')
                ->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['organization_id', 'supersedes_id'])
                ->references(['organization_id', 'id'])
                ->on('decisions')
                ->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'cycle_id']);
        });

        Schema::create('final_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('decision_id');
            $table->unsignedBigInteger('final_approver_id');
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->foreign(['organization_id', 'decision_id'])
                ->references(['organization_id', 'id'])
                ->on('decisions')
                ->cascadeOnDelete();
            $table->foreign('final_approver_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'decision_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_approvals');
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('cycle_results');
        Schema::dropIfExists('applicant_scores');
        Schema::dropIfExists('cycle_runs');
        Schema::table('cycles', function (Blueprint $table): void {
            $table->dropForeign(['organization_id', 'current_cycle_run_id']);
            $table->dropColumn('current_cycle_run_id');
        });
    }
};
