<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });
    }

    public function down(): void {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'name')) {
                // be cautious about dropping columns in production
                // $table->dropColumn('name');
            }
        });
    }
};
