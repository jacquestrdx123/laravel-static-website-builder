<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Demo sites are shown on the public /examples page to logged-out visitors.
     * A flag rather than a hardcoded slug list, so any published site can be
     * promoted to a showcase without a deploy.
     */
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
