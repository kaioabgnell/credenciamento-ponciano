<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Evento;
use App\Models\Funcionario;
use App\Models\Ponto;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    //  INDEX — Relatório Geral
    // ──────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $dataFiltro = $request->input('data', '');
        $empresaId  = $request->input('empresa_id', '');
        // Usa evento da URL; se não informado, usa o evento ativo da sessão
        $eventoId   = $request->input('evento_id', session('evento_ativo_id', ''));

        // ── Indicadores em tempo real (sempre do dia de HOJE) ─────
        $hoje = today()->format('Y-m-d');

        $totalFuncionariosGlobal = Funcionario::ativos()->count();
        $totalHojeNoEvento       = Ponto::data($hoje)
                                        ->whereIn('status', ['presente', 'finalizado'])
                                        ->count();

        $indicadoresAoVivo = [
            'dentro_evento' => Ponto::data($hoje)->where('status', 'presente')->count(),
            'finalizados'   => Ponto::data($hoje)->where('status', 'finalizado')->count(),
            'total_dia'     => $totalHojeNoEvento,
            'nao_entraram'  => max(0, $totalFuncionariosGlobal - $totalHojeNoEvento),
            'coordenadores' => Ponto::data($hoje)
                                    ->whereHas('funcionario', fn ($q) => $q->where('coordenador', true))
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->count(),
            'empresas'      => Ponto::data($hoje)
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->distinct('empresa_id')
                                    ->count('empresa_id'),
        ];

        // ── Empresas com filtro opcional ──────────────────────────
        $query = Empresa::ativas()->orderBy('nome');

        if ($empresaId) {
            $query->where('id', $empresaId);
        }

        $empresas = $query->get();

        // ── Calcula dados por empresa ─────────────────────────────
        $relatorio         = [];
        $totalSegundosGeral = 0;

        foreach ($empresas as $empresa) {
            $totalFuncionarios = $empresa->funcionariosAtivos()->count();

            // Base da query com filtros opcionais de data e evento
            $base = Ponto::where('empresa_id', $empresa->id);
            if ($dataFiltro) $base->whereDate('data', $dataFiltro);
            if ($eventoId)   $base->where('evento_id', $eventoId);

            // Se há filtro de data: contagem simples (1 registro por dia por func.)
            // Se não há filtro de data: contagem de funcionários DISTINTOS que participaram
            if ($dataFiltro) {
                $totalNoEvento = (clone $base)->whereIn('status', ['presente', 'finalizado'])->count();
                $finalizados   = (clone $base)->where('status', 'finalizado')->count();
                $horasBruto    = (clone $base)->where('status', 'finalizado')
                                              ->whereNotNull('horas_trabalhadas')
                                              ->pluck('horas_trabalhadas');
            } else {
                $totalNoEvento = (clone $base)->whereIn('status', ['presente', 'finalizado'])
                                              ->distinct('funcionario_id')
                                              ->count('funcionario_id');
                $finalizados   = (clone $base)->where('status', 'finalizado')
                                              ->distinct('funcionario_id')
                                              ->count('funcionario_id');
                $horasBruto    = (clone $base)->where('status', 'finalizado')
                                              ->whereNotNull('horas_trabalhadas')
                                              ->pluck('horas_trabalhadas');
            }

            $naoEntraram     = max(0, $totalFuncionarios - $totalNoEvento);
            $presenca        = $totalFuncionarios > 0
                               ? round(($totalNoEvento / $totalFuncionarios) * 100, 1)
                               : 0;
            $horasTrabalhadas = $this->somarHoras($horasBruto);

            $totalSegundosGeral += $this->horasParaSegundos($horasTrabalhadas);

            $relatorio[] = [
                'empresa'            => $empresa,
                'total_funcionarios' => $totalFuncionarios,
                'total_no_evento'    => $totalNoEvento,
                'finalizados'        => $finalizados,
                'nao_entraram'       => $naoEntraram,
                'presenca'           => $presenca,
                'horas_trabalhadas'  => $horasTrabalhadas,
            ];
        }

        $totalHorasGeralFormatado = $this->segundosParaHoras($totalSegundosGeral);

        // ── Dados auxiliares para filtros ─────────────────────────
        $datasDisponiveis = Ponto::selectRaw('DATE(data) as d')
                                  ->distinct()
                                  ->orderByDesc('d')
                                  ->pluck('d');

        $empresasLista = Empresa::ativas()->orderBy('nome')->get();
        $eventosLista  = Evento::ativos()->orderByDesc('data_inicio')->get(['id', 'nome', 'data_inicio', 'data_fim']);

        return view('relatorio.index', compact(
            'relatorio',
            'indicadoresAoVivo',
            'totalHorasGeralFormatado',
            'dataFiltro',
            'empresaId',
            'eventoId',
            'datasDisponiveis',
            'empresasLista',
            'eventosLista'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    //  RELATÓRIO POR FUNCIONÁRIO
    // ──────────────────────────────────────────────────────────────
    public function funcionarios(Request $request)
    {
        $dataInicio = $request->input('data_inicio', today()->format('Y-m-d'));
        $dataFim    = $request->input('data_fim',    today()->format('Y-m-d'));
        $eventoId   = $request->input('evento_id',   '');
        $empresaId  = $request->input('empresa_id',  '');
        $busca      = $request->input('busca',        '');

        $query = Funcionario::select('funcionarios.*')
            ->with('empresa')
            ->when($empresaId, fn($q) => $q->where('funcionarios.empresa_id', $empresaId))
            ->when($busca,     fn($q) => $q->busca($busca))
            ->whereHas('pontos', function ($q) use ($dataInicio, $dataFim, $eventoId) {
                $q->whereBetween('data', [$dataInicio, $dataFim])
                  ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId));
            })
            ->addSelect([
                'total_registros' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_entradas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('entrada')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_saidas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('saida')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'dias_trabalhados' => Ponto::selectRaw('COUNT(DISTINCT data)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_segundos' => Ponto::selectRaw('COALESCE(SUM(TIME_TO_SEC(horas_trabalhadas)), 0)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('horas_trabalhadas')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),
            ])
            ->orderByDesc('total_segundos');

        $eventosLista = Evento::orderByDesc('data_inicio')->get(['id', 'nome']);
        $empresas     = Empresa::ativas()->orderBy('nome')->get(['id', 'nome']);

        return view('relatorio.funcionarios', compact(
            'dataInicio', 'dataFim', 'eventoId', 'empresaId',
            'busca', 'eventosLista', 'empresas'
        ) + [
            'funcionarios' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function exportarFuncionarios(Request $request)
    {
        $dataInicio = $request->input('data_inicio', today()->format('Y-m-d'));
        $dataFim    = $request->input('data_fim',    today()->format('Y-m-d'));
        $eventoId   = $request->input('evento_id',   '');
        $empresaId  = $request->input('empresa_id',  '');
        $busca      = $request->input('busca',        '');
        $formato    = $request->input('formato',      'xls');

        $funcionarios = Funcionario::select('funcionarios.*')
            ->with('empresa')
            ->when($empresaId, fn($q) => $q->where('funcionarios.empresa_id', $empresaId))
            ->when($busca,     fn($q) => $q->busca($busca))
            ->whereHas('pontos', function ($q) use ($dataInicio, $dataFim, $eventoId) {
                $q->whereBetween('data', [$dataInicio, $dataFim])
                  ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId));
            })
            ->addSelect([
                'total_entradas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('entrada')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_saidas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('saida')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'dias_trabalhados' => Ponto::selectRaw('COUNT(DISTINCT data)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_segundos' => Ponto::selectRaw('COALESCE(SUM(TIME_TO_SEC(horas_trabalhadas)), 0)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('horas_trabalhadas')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),
            ])
            ->orderByDesc('total_segundos')
            ->get();

        $nomeArquivo = 'relatorio-funcionarios-' . $dataInicio . '-a-' . $dataFim;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\RelatorioFuncionariosExport($funcionarios, $dataInicio, $dataFim),
            $nomeArquivo . '.xlsx'
        );
    }

    public function exportarFuncionariosPdf(Request $request)
    {
        $dataInicio = $request->input('data_inicio', today()->format('Y-m-d'));
        $dataFim    = $request->input('data_fim',    today()->format('Y-m-d'));
        $eventoId   = $request->input('evento_id',   '');
        $empresaId  = $request->input('empresa_id',  '');
        $busca      = $request->input('busca',        '');

        $funcionarios = Funcionario::select('funcionarios.*')
            ->with('empresa')
            ->when($empresaId, fn($q) => $q->where('funcionarios.empresa_id', $empresaId))
            ->when($busca,     fn($q) => $q->busca($busca))
            ->whereHas('pontos', function ($q) use ($dataInicio, $dataFim, $eventoId) {
                $q->whereBetween('data', [$dataInicio, $dataFim])
                  ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId));
            })
            ->addSelect([
                'total_entradas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('entrada')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_saidas' => Ponto::selectRaw('COUNT(*)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('saida')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'dias_trabalhados' => Ponto::selectRaw('COUNT(DISTINCT data)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),

                'total_segundos' => Ponto::selectRaw('COALESCE(SUM(TIME_TO_SEC(horas_trabalhadas)), 0)')
                    ->whereColumn('funcionario_id', 'funcionarios.id')
                    ->whereNotNull('horas_trabalhadas')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->when($eventoId, fn($q) => $q->where('evento_id', $eventoId)),
            ])
            ->orderByDesc('total_segundos')
            ->get();

        $eventoNome  = $eventoId  ? Evento::find($eventoId)?->nome  : null;
        $empresaNome = $empresaId ? Empresa::find($empresaId)?->nome : null;

        return view('relatorio.funcionarios-pdf', compact(
            'funcionarios', 'dataInicio', 'dataFim', 'eventoNome', 'empresaNome', 'busca'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    //  API — Indicadores ao vivo (polling AJAX)
    // ──────────────────────────────────────────────────────────────
    public function indicadoresAoVivo()
    {
        $hoje = today()->format('Y-m-d');

        $totalFuncs  = Funcionario::ativos()->count();
        $noEvento    = Ponto::data($hoje)->whereIn('status', ['presente', 'finalizado'])->count();

        return response()->json([
            'dentro_evento' => Ponto::data($hoje)->where('status', 'presente')->count(),
            'finalizados'   => Ponto::data($hoje)->where('status', 'finalizado')->count(),
            'total_dia'     => $noEvento,
            'nao_entraram'  => max(0, $totalFuncs - $noEvento),
            'coordenadores' => Ponto::data($hoje)
                                    ->whereHas('funcionario', fn ($q) => $q->where('coordenador', true))
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->count(),
            'empresas'      => Ponto::data($hoje)
                                    ->whereIn('status', ['presente', 'finalizado'])
                                    ->distinct('empresa_id')
                                    ->count('empresa_id'),
            'atualizado_em' => now()->format('H:i:s'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS — Manipulação de horas
    // ──────────────────────────────────────────────────────────────
    private function somarHoras($horas): string
    {
        $total = 0;
        foreach ($horas as $h) {
            $total += $this->horasParaSegundos($h);
        }
        return $this->segundosParaHoras($total);
    }

    private function horasParaSegundos(?string $horas): int
    {
        if (! $horas) {
            return 0;
        }
        $partes = explode(':', $horas);
        $h = (int) ($partes[0] ?? 0);
        $m = (int) ($partes[1] ?? 0);
        $s = (int) ($partes[2] ?? 0);
        return $h * 3600 + $m * 60 + $s;
    }

    private function segundosParaHoras(int $segundos): string
    {
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
