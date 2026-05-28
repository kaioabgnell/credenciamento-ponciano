<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'nome', 'responsavel', 'telefone', 'email', 'observacoes', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // RELACIONAMENTOS
    public function funcionarios()
    {
        return $this->hasMany(Funcionario::class, 'empresa_id');
    }

    public function funcionariosAtivos()
    {
        return $this->hasMany(Funcionario::class, 'empresa_id')->where('ativo', true);
    }

    public function pontos()
    {
        return $this->hasMany(Ponto::class, 'empresa_id');
    }

    public function historico()
    {
        return $this->hasMany(HistoricoEmpresa::class, 'empresa_id')->orderByDesc('created_at');
    }

    // SCOPES
    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeBusca($query, string $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
              ->orWhere('responsavel', 'like', "%{$termo}%")
              ->orWhere('email', 'like', "%{$termo}%");
        });
    }

    // ACESSORS
    public function getTotalFuncionariosAttribute(): int
    {
        return $this->funcionariosAtivos()->count();
    }
}
