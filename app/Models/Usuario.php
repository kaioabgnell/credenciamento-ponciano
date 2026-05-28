<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $authPasswordName = 'senha';

    protected $fillable = [
        'nome', 'cpf', 'email', 'data_nascimento', 'foto',
        'senha', 'telefone1', 'telefone2', 'cargo', 'ativo',
    ];

    protected $hidden = ['senha', 'remember_token'];

    protected $casts = [
        'data_nascimento' => 'date',
        'ativo'           => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function pontos()
    {
        return $this->hasMany(Ponto::class, 'registrado_por');
    }

    public function historicoEmpresas()
    {
        return $this->hasMany(HistoricoEmpresa::class, 'usuario_id');
    }

    public function getFotoUrlAttribute(): string
    {
        if ($this->foto && \Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }
        return asset('images/avatar-default.svg');
    }

    public function getNomeAbreviadoAttribute(): string
    {
        $partes = explode(' ', trim($this->nome));
        if (count($partes) === 1) return $partes[0];
        return $partes[0] . ' ' . end($partes);
    }
}
