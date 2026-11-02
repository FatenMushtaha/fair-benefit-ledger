<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('name');
            $table->string('sector')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('participations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('import_row_id')->nullable();
            $table->unsignedBigInteger('source_decision_id')->nullable();
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'program_id'])
                ->references(['organization_id', 'id'])
                ->on('programs')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'person_id']);
            $table->index(['organization_id', 'program_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
        Schema::dropIfExists('programs');
    }
};
