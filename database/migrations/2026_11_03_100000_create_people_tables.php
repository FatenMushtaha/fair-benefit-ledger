<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('display_name');
            $table->string('normalized_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'normalized_name']);
        });

        Schema::create('person_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('person_id');
            $table->string('type');
            $table->string('normalized_value');
            $table->string('authoritative_key')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'authoritative_key']);
            $table->index(['organization_id', 'person_id']);
            $table->index(['organization_id', 'type', 'normalized_value']);
        });

        Schema::create('person_names', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('person_id');
            $table->string('name');
            $table->string('normalized_name');
            $table->string('script')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->foreign(['organization_id', 'person_id'])
                ->references(['organization_id', 'id'])
                ->on('people')
                ->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->index(['organization_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_names');
        Schema::dropIfExists('person_identifiers');
        Schema::dropIfExists('people');
    }
};
