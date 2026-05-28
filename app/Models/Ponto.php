<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ponto extends Model
{
    protected $table = 'pontos';

    protected $fillable = [
        'funcionario_id', 'empresa_id', 'evento_id', 'data', 'entrada',
        'saida', 'horas_trabalhadas', 'status', 'registrado_por', 'obs',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    // RELACIONAMENTOS
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    // SCOPES
    public function scopeHoje($query)
    {
        return $query->whereDate('data', today());
    }

    public function scopeData($query, $data)
    {
        return $query->whereDate('data', $data);
    }

    public function scopePresentes($query)
    {
        return $query->where('status', 'presente');
    }

    public function scopeFinalizados($query)
    {
        return $query->where('status', 'finalizado');
    }

    // MÉTODOS
    public function calcularHoras(): void
    {
        if ($this->entrada && $this->saida) {
            $entrada = Carbon::parse($this->data->format('Y-m-d') . ' ' . $this->entrada);
            $saida   = Carbon::parse($this->data->format('Y-m-d') . ' ' . $this->saida);
            $diff    = $entrada->diff($saida);
            $this->horas_trabalhadas = $diff->format('%H:%I:%S');
            $this->status = 'finalizado';
            $this->save();
        }
    }

    // Badge de status para view
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'presente'   => '<span class="badge badge-presente">● Presente</span>',
            'finalizado' => '<span class="badge badge-finalizado">✓ Finalizado</span>',
            default      => '<span class="badge badge-ausente">— Ausente</span>',
        };
    }
}
