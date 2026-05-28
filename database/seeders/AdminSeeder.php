<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => 'admin@ponciano.com.br'],
            [
                'nome'            => 'Administrador Ponciano',
                'cpf'             => '000.000.000-00',
                'email'           => 'admin@ponciano.com.br',
                'data_nascimento' => '1990-01-01',
                'senha'           => Hash::make('ponciano@2026'),
                'telefone1'       => '(62) 99999-0001',
                'cargo'           => 'Administrador',
                'ativo'           => true,
            ]
        );
        $this->command->info('Admin: admin@ponciano.com.br / ponciano@2026');
    }
}
