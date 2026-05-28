<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('cpf', 14)->unique();
            $table->string('email')->unique();
            $table->date('data_nascimento');
            $table->string('foto')->nullable();
            $table->string('senha');
            $table->string('telefone1', 20);
            $table->string('telefone2', 20)->nullable();
            $table->string('cargo', 100);
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
