<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onDelete('restrict');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('restrict');
            $table->date('data');
            $table->time('entrada')->nullable();
            $table->time('saida')->nullable();
            $table->string('horas_trabalhadas', 10)->nullable(); // HH:MM:SS
            $table->enum('status', ['ausente', 'presente', 'finalizado'])->default('ausente');
            $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->string('obs')->nullable();
            $table->timestamps();

            // Índices para filtros rápidos
            $table->index('data');
            $table->index('status');
            $table->index(['funcionario_id', 'data']);
            $table->index(['empresa_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pontos');
    }
};
