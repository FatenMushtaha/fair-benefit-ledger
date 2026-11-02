<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_sets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name');
            $table->string('purpose');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('rule_set_id');
            $table->unsignedInteger('version');
            $table->json('config');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign(['organization_id', 'rule_set_id'])
                ->references(['organization_id', 'id'])
                ->on('rule_sets')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'rule_set_id', 'version']);
            $table->index(['organization_id', 'rule_set_id']);
        });

        Schema::create('cycles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('rule_version_id');
            $table->string('name');
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->unsignedInteger('available_quantity')->nullable();
            $table->string('automation_mode')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'program_id'])
                ->references(['organization_id', 'id'])
                ->on('programs')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'rule_version_id'])
                ->references(['organization_id', 'id'])
                ->on('rule_versions')
                ->restrictOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'program_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('rule_sets', function (Blueprint $table): void {
            $table->foreign(['organization_id', 'current_version_id'])
                ->references(['organization_id', 'id'])
                ->on('rule_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rule_sets', function (Blueprint $table): void {
            $table->dropForeign(['organization_id', 'current_version_id']);
        });
        Schema::dropIfExists('cycles');
        Schema::dropIfExists('rule_versions');
        Schema::dropIfExists('rule_sets');
    }
};
