<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', static function (Blueprint $table) {
            $table->string('legacy_code')->nullable()->index()
                ->comment('The original upload code from an imported legacy XBackBone instance.');
        });
    }

    public function down(): void
    {
        Schema::table('resources', static function (Blueprint $table) {
            $table->dropIndex(['legacy_code']);
            $table->dropColumn('legacy_code');
        });
    }
};
