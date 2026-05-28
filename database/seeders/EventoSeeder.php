<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('eventos')->updateOrInsert(
            ['id' => 1],
            [
                'nome'                 => 'Totus Goiânia',
                'data_inicio'          => '2026-05-25',
                'data_fim'             => '2026-06-02',
                'nome_organizador'     => 'Leonardo Ponciano',
                'telefone_organizador' => '(62) 98287-9658',
                'ativo'                => true,
                'created_at'           => '2026-05-28 11:34:04',
                'updated_at'           => '2026-05-28 11:34:04',
            ]
        );
    }
}
