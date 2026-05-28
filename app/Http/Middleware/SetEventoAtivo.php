<?php

namespace App\Http\Middleware;

use App\Models\Evento;
use Closure;
use Illuminate\Http\Request;

class SetEventoAtivo
{
    /**
     * Se não houver evento selecionado na sessão, busca automaticamente
     * o evento mais recente que esteja dentro do período (data_inicio–data_fim).
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && ! $request->session()->has('evento_ativo_id')) {
            $evento = Evento::emAndamento()->orderByDesc('id')->first();

            if ($evento) {
                $request->session()->put('evento_ativo_id', $evento->id);
                $request->session()->put('evento_ativo_nome', $evento->nome);
            }
        }

        return $next($request);
    }
}
