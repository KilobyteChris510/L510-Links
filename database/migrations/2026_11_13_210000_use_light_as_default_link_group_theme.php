<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_groups', function (Blueprint $table) {
            $table->string('theme')->default('light')->change();
        });

        DB::table('link_groups')->where('theme', 'default')->update(['theme' => 'light']);
    }

    public function down(): void
    {
        Schema::table('link_groups', function (Blueprint $table) {
            $table->string('theme')->default('default')->change();
        });
    }
};
