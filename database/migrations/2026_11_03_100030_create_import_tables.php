<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('purpose');
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->string('private_path');
            $table->string('format');
            $table->string('status')->default('uploaded');
            $table->json('counts')->nullable();
            $table->unsignedInteger('cursor')->nullable();
            $table->json('quality_summary')->nullable();
            $table->json('error_summary')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'cycle_id'])
                ->references(['organization_id', 'id'])
                ->on('cycles')
                ->nullOnDelete();
            $table->foreign(['organization_id', 'program_id'])
                ->references(['organization_id', 'id'])
                ->on('programs')
                ->nullOnDelete();
            $table->foreign('uploader_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('import_batch_id');
            $table->unsignedInteger('row_number');
            $table->json('raw_payload');
            $table->json('mapped_payload')->nullable();
            $table->json('cleaned_payload')->nullable();
            $table->string('status')->default('staged');
            $table->json('errors')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'import_batch_id'])
                ->references(['organization_id', 'id'])
                ->on('import_batches')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->nullOnDelete();
            $table->unique(['organization_id', 'import_batch_id', 'row_number']);
            $table->index(['organization_id', 'import_batch_id', 'status']);
        });

        Schema::create('column_mappings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->json('source_headers');
            $table->json('canonical_map');
            $table->json('locale_hints')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['organization_id', 'approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('column_mappings');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
