<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pontos', function (Blueprint $table) {
            $table->foreignId('evento_id')
                  ->nullable()
                  ->after('empresa_id')
                  ->constrained('eventos')
                  ->onDelete('set null');

            $table->index('evento_id');
        });
    }

    public function down(): void
    {
        Schema::table('pontos', function (Blueprint $table) {
            $table->dropForeign(['evento_id']);
            $table->dropIndex(['evento_id']);
            $table->dropColumn('evento_id');
        });
    }
};
