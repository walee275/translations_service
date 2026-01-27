<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->index(['key', 'locale_id'], 'idx_translations_key_locale');
        });

        Schema::table('locales', function (Blueprint $table) {
            $table->index('code', 'idx_locales_code');
        });

        Schema::table('translation_tag', function (Blueprint $table) {
            $table->index('translation_id', 'idx_translation_tag_translation');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->index('name', 'idx_tags_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropIndex('idx_translations_key_locale');
        });

        Schema::table('locales', function (Blueprint $table) {
            $table->dropIndex('idx_locales_code');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('idx_tags_name');
        });
    }
};
