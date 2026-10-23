<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Busca no vocabulário sem acentos e tolerante a erros de digitação (J3-B9, VOC-01).
     * O unaccent não é IMMUTABLE; a função fixa o dicionário para poder indexar.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION fixa_search_text(text) RETURNS text
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $$ SELECT lower(public.unaccent('public.unaccent'::regdictionary, $1)) $$
        SQL);
        DB::statement('CREATE INDEX words_term_search ON words USING gin (fixa_search_text(term) gin_trgm_ops)');
        DB::statement('CREATE INDEX words_translation_search ON words USING gin (fixa_search_text(translation) gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS words_translation_search');
        DB::statement('DROP INDEX IF EXISTS words_term_search');
        DB::statement('DROP FUNCTION IF EXISTS fixa_search_text(text)');
    }
};
