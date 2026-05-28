<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoEmpresa extends Model
{
    protected $table = 'historico_empresas';

    protected $fillable = [
        'empresa_id', 'usuario_id', 'campo_alterado', 'valor_anterior', 'valor_novo',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Labels amigáveis para os campos
    public static function labelCampo(string $campo): string
    {
        $labels = [
            'nome'        => 'Nome da Empresa',
            'responsavel' => 'Responsável',
            'telefone'    => 'Telefone',
            'email'       => 'E-mail',
            'observacoes' => 'Observações',
            'ativo'       => 'Status',
        ];
        return $labels[$campo] ?? ucfirst($campo);
    }
}
