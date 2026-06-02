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
        Schema::create('importacoes', function (Blueprint $table) {
            $table->id();
            $table->string('arquivo_nome', 255);
            $table->string('empresa_nome', 200)->nullable();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->onDelete('set null');
            $table->enum('empresa_acao', ['existente', 'nova'])->nullable();
            $table->unsignedInteger('total_funcionarios')->default(0);
            $table->unsignedInteger('importados')->default(0);
            $table->unsignedInteger('com_erros')->default(0);
            $table->json('detalhes_erros')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacoes');
    }
};
