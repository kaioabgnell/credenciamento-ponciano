<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Evento;
use App\Models\Funcionario;
use App\Models\Ponto;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $data      = $request->input('data', today()->format('Y-m-d'));
        $eventoId  = session('evento_ativo_id');
        $eventoAtivo = $eventoId ? Evento::find($eventoId) : null;

        $indicadores = [
            'presentes'     => Ponto::data($data)->where('status', 'presente')
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'finalizados'   => Ponto::data($data)->where('status', 'finalizado')
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'total_dia'     => Ponto::data($data)
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'coordenadores' => Ponto::data($data)
                                    ->whereHas('funcionario', fn ($q) => $q->where('coordenador', true))
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'empresas'      => Empresa::ativas()->count(),
            'funcionarios'  => Funcionario::ativos()->count(),
        ];

        $presentes = Ponto::with(['funcionario.empresa'])
                          ->data($data)
                          ->where('status', 'presente')
                          ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                          ->orderBy('entrada')
                          ->take(10)
                          ->get();

        $por_empresa = Empresa::withCount(['pontos as total_ponto' => function ($q) use ($data, $eventoId) {
                $q->whereDate('data', $data);
                if ($eventoId) $q->where('evento_id', $eventoId);
            }])
            ->having('total_ponto', '>', 0)
            ->orderByDesc('total_ponto')
            ->take(8)
            ->get();

        $datas_disponiveis = Ponto::selectRaw('DATE(data) as d')
                                  ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                  ->distinct()
                                  ->orderByDesc('d')
                                  ->take(30)
                                  ->pluck('d');

        return view('dashboard.index', compact(
            'indicadores', 'presentes', 'por_empresa', 'data', 'datas_disponiveis', 'eventoAtivo'
        ));
    }

    public function indicadores(Request $request)
    {
        $data     = $request->input('data', today()->format('Y-m-d'));
        $eventoId = session('evento_ativo_id');

        return response()->json([
            'presentes'     => Ponto::data($data)->where('status', 'presente')
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'finalizados'   => Ponto::data($data)->where('status', 'finalizado')
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'total_dia'     => Ponto::data($data)
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'coordenadores' => Ponto::data($data)
                                    ->whereHas('funcionario', fn ($q) => $q->where('coordenador', true))
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                                    ->count(),
            'atualizado_em' => now()->format('H:i:s'),
        ]);
    }
}
