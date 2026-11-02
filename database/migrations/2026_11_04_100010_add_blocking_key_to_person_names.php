<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_names', function (Blueprint $table): void {
            $table->string('blocking_key')->nullable()->after('normalized_name');
            $table->index(['organization_id', 'blocking_key']);
        });
    }

    public function down(): void
    {
        Schema::table('person_names', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'blocking_key']);
            $table->dropColumn('blocking_key');
        });
    }
};
