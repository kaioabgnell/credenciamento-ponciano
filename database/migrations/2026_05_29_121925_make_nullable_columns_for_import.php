<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // empresa_id nullable em funcionarios
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');
        });

        // empresa_id e data nullable em pontos
        Schema::table('pontos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('set null');

            $table->date('data')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('restrict');
        });

        Schema::table('pontos', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('restrict');

            $table->date('data')->nullable(false)->change();
        });
    }
};
