<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('movies', 'tmdb_id')) {
            Schema::table('movies', function (Blueprint $table) {
                $table->unsignedBigInteger('tmdb_id')->unique()->after('id');
                $table->string('title')->after('tmdb_id');
                $table->text('overview')->nullable()->after('title');
                $table->string('poster_path')->nullable()->after('overview');
                $table->date('release_date')->nullable()->after('poster_path');
                $table->unsignedSmallInteger('rating')->nullable()->after('release_date');
                $table->text('review')->nullable()->after('rating');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('movies', 'tmdb_id')) {
            Schema::table('movies', function (Blueprint $table) {
                $table->dropUnique(['tmdb_id']);
                $table->dropColumn([
                    'tmdb_id',
                    'title',
                    'overview',
                    'poster_path',
                    'release_date',
                    'rating',
                    'review',
                ]);
            });
        }
    }
};
