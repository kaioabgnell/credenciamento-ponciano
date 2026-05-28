<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Evento extends Model
{
    protected $table = 'eventos';

    protected $fillable = [
        'nome', 'data_inicio', 'data_fim',
        'nome_organizador', 'telefone_organizador', 'ativo',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim'    => 'date',
        'ativo'       => 'boolean',
    ];

    // RELACIONAMENTOS
    public function pontos()
    {
        return $this->hasMany(Ponto::class, 'evento_id');
    }

    // SCOPES
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /** Eventos em andamento numa data específica (padrão: hoje) */
    public function scopeEmAndamento($query, ?string $data = null)
    {
        $data = $data ?? today()->format('Y-m-d');
        return $query->where('ativo', true)
                     ->where('data_inicio', '<=', $data)
                     ->where('data_fim', '>=', $data);
    }

    // ACCESSORS
    public function getPeriodoFormatadoAttribute(): string
    {
        if ($this->data_inicio->eq($this->data_fim)) {
            return $this->data_inicio->format('d/m/Y');
        }
        return $this->data_inicio->format('d/m/Y') . ' – ' . $this->data_fim->format('d/m/Y');
    }

    public function getDuracaoDiasAttribute(): int
    {
        return $this->data_inicio->diffInDays($this->data_fim) + 1;
    }

    public function getStatusAttribute(): string
    {
        $hoje = today();
        if ($hoje->lt($this->data_inicio)) return 'futuro';
        if ($hoje->gt($this->data_fim))    return 'encerrado';
        return 'em_andamento';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'em_andamento' => '<span class="badge badge-presente">● Em Andamento</span>',
            'futuro'       => '<span class="badge badge-coordenador">◷ Futuro</span>',
            default        => '<span class="badge badge-ausente">✓ Encerrado</span>',
        };
    }
}
