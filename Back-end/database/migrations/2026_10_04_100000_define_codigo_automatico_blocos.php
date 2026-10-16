<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS blocos_codigo_seq');
        DB::statement(
            "SELECT setval('blocos_codigo_seq', COALESCE((SELECT MAX(codigo::bigint) FROM blocos WHERE codigo ~ '^[0-9]{1,18}$'), 0) + 1, false)"
        );
        DB::statement('ALTER SEQUENCE blocos_codigo_seq OWNED BY blocos.codigo');
        DB::statement("ALTER TABLE blocos ALTER COLUMN codigo SET DEFAULT nextval('blocos_codigo_seq')::text");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE blocos ALTER COLUMN codigo DROP DEFAULT');
        DB::statement('DROP SEQUENCE IF EXISTS blocos_codigo_seq');
    }
};
