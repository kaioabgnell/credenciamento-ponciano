<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Importacao extends Model
{
    protected $table = 'importacoes';

    protected $fillable = [
        'arquivo_nome', 'empresa_nome', 'empresa_id', 'empresa_acao',
        'total_funcionarios', 'importados', 'com_erros', 'detalhes_erros', 'usuario_id',
    ];

    protected $casts = [
        'detalhes_erros' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
