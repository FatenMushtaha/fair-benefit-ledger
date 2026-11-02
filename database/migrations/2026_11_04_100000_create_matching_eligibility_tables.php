<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_candidates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('import_row_id');
            $table->unsignedBigInteger('person_id');
            $table->string('method');
            $table->string('confidence_band');
            $table->json('reason_codes')->nullable();
            $table->string('blocking_key')->nullable();
            $table->string('match_policy_version')->nullable();
            $table->string('normalizer_version')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign(['organization_id', 'import_row_id'])
                ->references(['organization_id', 'id'])
                ->on('import_rows')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'import_row_id', 'person_id']);
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'blocking_key']);
            $table->index(['organization_id', 'confidence_band']);
        });

        Schema::create('cycle_applicants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('import_row_id');
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('status')->default('pending');
            $table->json('verified_data_flags')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->unsignedBigInteger('current_eligibility_evaluation_id')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'import_row_id'])
                ->references(['organization_id', 'id'])
                ->on('import_rows')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'cycle_id', 'import_row_id']);
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'cycle_id', 'status']);
        });

        Schema::create('match_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('import_row_id');
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedBigInteger('reviewer_id');
            $table->string('outcome');
            $table->text('reason');
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->foreign(['organization_id', 'import_row_id'])
                ->references(['organization_id', 'id'])
                ->on('import_rows')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'candidate_id'])
                ->references(['organization_id', 'id'])
                ->on('match_candidates')
                ->nullOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'import_row_id']);
            $table->index(['organization_id', 'reviewer_id', 'reviewed_at']);
        });

        Schema::create('eligibility_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('cycle_applicant_id');
            $table->unsignedBigInteger('rule_version_id');
            $table->date('evaluation_date');
            $table->string('input_fingerprint');
            $table->string('status');
            $table->json('reasons')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_applicant_id'])
                ->references(['organization_id', 'id'])
                ->on('cycle_applicants')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'rule_version_id'])
                ->references(['organization_id', 'id'])
                ->on('rule_versions')
                ->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'cycle_applicant_id', 'rule_version_id', 'input_fingerprint']);
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'cycle_id']);
        });

        Schema::table('cycle_applicants', function (Blueprint $table): void {
            $table->foreign(['organization_id', 'current_eligibility_evaluation_id'])
                ->references(['organization_id', 'id'])
                ->on('eligibility_evaluations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cycle_applicants', function (Blueprint $table): void {
            $table->dropForeign(['organization_id', 'current_eligibility_evaluation_id']);
        });
        Schema::dropIfExists('eligibility_evaluations');
        Schema::dropIfExists('match_reviews');
        Schema::dropIfExists('cycle_applicants');
        Schema::dropIfExists('match_candidates');
    }
};
