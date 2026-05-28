<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 200);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('nome_organizador', 150)->nullable();
            $table->string('telefone_organizador', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['data_inicio', 'data_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
