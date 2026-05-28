<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('restrict');
            $table->string('nome', 200);
            $table->string('cpf', 14)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('foto')->nullable();
            $table->string('funcao_cargo', 100);
            $table->string('area_acesso', 100)->default('TODOS');
            $table->boolean('coordenador')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // Índices para busca rápida
            $table->index('nome');
            $table->index('cpf');
            $table->index('empresa_id');
            $table->index('coordenador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
