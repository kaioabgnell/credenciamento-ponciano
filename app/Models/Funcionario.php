<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'funcionarios';

    protected $fillable = [
        'empresa_id', 'nome', 'cpf', 'telefone', 'foto',
        'funcao_cargo', 'area_acesso', 'coordenador', 'ativo',
    ];

    protected $casts = [
        'coordenador' => 'boolean',
        'ativo'       => 'boolean',
    ];

    // RELACIONAMENTOS
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function pontos()
    {
        return $this->hasMany(Ponto::class, 'funcionario_id');
    }

    public function pontoHoje()
    {
        return $this->hasOne(Ponto::class, 'funcionario_id')
                    ->whereDate('data', today());
    }

    // SCOPES
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeCoordenadores($query)
    {
        return $query->where('coordenador', true);
    }

    public function scopeBusca($query, string $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
              ->orWhere('cpf', 'like', "%{$termo}%")
              ->orWhere('funcao_cargo', 'like', "%{$termo}%");
        });
    }

    // ACESSORS
    public function getFotoUrlAttribute(): string
    {
        if ($this->foto && \Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }
        return asset('images/avatar-default.svg');
    }

    public function getCpfFormatadoAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf ?? '');
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $this->cpf ?? '-';
    }

    public function getStatusPontoHojeAttribute(): string
    {
        $ponto = $this->pontoHoje;
        if (!$ponto) return 'ausente';
        return $ponto->status;
    }
}
