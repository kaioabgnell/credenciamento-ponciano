@extends('layouts.app')
@section('title', 'Relatório por Funcionário')
@section('breadcrumb')
    <a href="{{ route('relatorio.index') }}">Relatório</a>
    <span class="sep">/</span>
    <span class="current">Por Funcionário</span>
@endsection

@push('styles')
    <style>
        .resumo-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .resumo-card {
            background: #fff;
            border-radius: var(--border-radius-sm);
            border: 1.5px solid var(--cinza-200);
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .resumo-card .rc-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--cinza-400);
        }

        .resumo-card .rc-valor {
            font-size: 22px;
            font-weight: 800;
            color: var(--cinza-900);
            font-family: var(--font-mono);
        }

        .resumo-card .rc-sub {
            font-size: 11px;
            color: var(--cinza-400);
        }

        .btn-exportar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-body);
            border: 1.5px solid transparent;
            text-decoration: none;
            transition: opacity .15s;
        }

        .btn-exportar:hover {
            opacity: .85;
        }

        .btn-xls {
            background: #16a34a;
            color: #fff;
            border-color: #15803d;
        }

        .btn-print {
            background: var(--cinza-100);
            color: var(--cinza-700);
            border-color: var(--cinza-300);
        }

        @media print {

            .sidebar,
            .topbar,
            .filtros-bar,
            .pagination-wrap,
            .page-header .d-flex,
            .btn-exportar,
            .btn-icon,
            .no-print {
                display: none !important;
            }

            .main-wrapper {
                margin: 0 !important;
            }

            .main-content {
                padding: 0 !important;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            table {
                font-size: 11px;
            }
        }

        @media (max-width: 768px) {
            .resumo-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .export-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h1 class="page-title"> Relatório por Funcionário</h1>
            <p class="page-subtitle">
                Levantamento de horas e presenças por colaborador
                @if ($dataInicio === $dataFim)
                    — {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}
                @else
                    — {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} até
                    {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
                @endif
            </p>
        </div>
        <a href="{{ route('relatorio.index') }}" class="btn btn-secondary">← Relatório Geral</a>
    </div>

    {{-- ── FILTROS ─────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('relatorio.funcionarios') }}" class="filtros-bar"
        style="flex-wrap:wrap; gap:10px; margin-bottom:20px" id="form-filtros">

        {{-- Busca --}}
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="busca" class="form-control" id="campo-busca-global"
                placeholder="Nome ou CPF do funcionário..." value="{{ $busca }}">
        </div>

        {{-- Evento --}}
        <select name="evento_id" class="form-control form-select" style="width:200px" onchange="this.form.submit()">
            <option value="">Todos os eventos</option>
            @foreach ($eventosLista as $ev)
                <option value="{{ $ev->id }}" {{ $eventoId == $ev->id ? 'selected' : '' }}>{{ $ev->nome }}
                </option>
            @endforeach
        </select>

        {{-- Data início --}}
        <div style="display:flex; align-items:center; gap:6px">
            <label style="font-size:12px; font-weight:600; color:var(--cinza-500); white-space:nowrap">De:</label>
            <input type="date" name="data_inicio" class="form-control" style="width:150px" value="{{ $dataInicio }}"
                onchange="this.form.submit()">
        </div>

        {{-- Data fim --}}
        <div style="display:flex; align-items:center; gap:6px">
            <label style="font-size:12px; font-weight:600; color:var(--cinza-500); white-space:nowrap">Até:</label>
            <input type="date" name="data_fim" class="form-control" style="width:150px" value="{{ $dataFim }}"
                onchange="this.form.submit()">
        </div>

        {{-- Empresa --}}
        <select name="empresa_id" class="form-control form-select" style="width:200px" onchange="this.form.submit()">
            <option value="">Todas as empresas</option>
            @foreach ($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $empresaId == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}
                </option>
            @endforeach
        </select>

        @if (
            $busca ||
                $eventoId ||
                $empresaId ||
                $dataInicio !== today()->format('Y-m-d') ||
                $dataFim !== today()->format('Y-m-d'))
            <a href="{{ route('relatorio.funcionarios') }}" class="btn btn-secondary">Limpar</a>
        @endif

    </form>

    {{-- ── CARDS DE RESUMO ─────────────────────────────────────── --}}
    @php
        $col = $funcionarios->getCollection();
        $totalSecs = $col->sum('total_segundos');
        $totalH = intdiv($totalSecs, 3600);
        $totalM = intdiv($totalSecs % 3600, 60);
        $mediaSecs = $col->avg('total_segundos') ?? 0;
        $mediaH = intdiv($mediaSecs, 3600);
        $mediaM = intdiv($mediaSecs % 3600, 60);
        $totalEntr = $col->sum('total_entradas');
        $totalSai = $col->sum('total_saidas');
        $pendSaida = $totalEntr - $totalSai;
    @endphp

    <div class="resumo-cards">
        <div class="resumo-card">
            <div class="rc-label">👤 Funcionários</div>
            <div class="rc-valor">{{ $funcionarios->total() }}</div>
            <div class="rc-sub">com registros no período</div>
        </div>
        <div class="resumo-card">
            <div class="rc-label">⏱ Total Horas</div>
            <div class="rc-valor" style="color:var(--azul-primario)">{{ $totalH }}h
                {{ str_pad($totalM, 2, '0', STR_PAD_LEFT) }}m</div>
            <div class="rc-sub">somado de todos os turnos</div>
        </div>
        <div class="resumo-card">
            <div class="rc-label">📊 Média por Func.</div>
            <div class="rc-valor" style="color:var(--roxo)">{{ $mediaH }}h
                {{ str_pad($mediaM, 2, '0', STR_PAD_LEFT) }}m</div>
            <div class="rc-sub">média de horas nesta página</div>
        </div>
        <div class="resumo-card">
            <div class="rc-label">✅ Entradas</div>
            <div class="rc-valor" style="color:var(--verde)">{{ $totalEntr }}</div>
            <div class="rc-sub">registros de entrada</div>
        </div>
        <div class="resumo-card">
            <div class="rc-label">🔴 Saídas</div>
            <div class="rc-valor" style="color:var(--vermelho)">{{ $totalSai }}</div>
            <div class="rc-sub">registros de saída</div>
        </div>
        <div class="resumo-card">
            <div class="rc-label">⚠ Sem Saída</div>
            <div class="rc-valor" style="color:{{ $pendSaida > 0 ? '#d97706' : 'var(--cinza-400)' }}">
                {{ max(0, $pendSaida) }}</div>
            <div class="rc-sub">entradas sem saída</div>
        </div>
    </div>

    {{-- ── BARRA EXPORTAR ───────────────────────────────────────── --}}
    @if (false)
        <div class="d-flex gap-10 align-center export-row no-print" style="margin-bottom:16px; flex-wrap:wrap">
            <span style="font-size:13px; color:var(--cinza-500); font-weight:600">Exportar:</span>

            <a href="{{ route(
                'relatorio.funcionarios.exportar',
                array_filter([
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'evento_id' => $eventoId,
                    'empresa_id' => $empresaId,
                    'busca' => $busca,
                    'formato' => 'xls',
                ]),
            ) }}"
                class="btn-exportar btn-xls">
                📊 Excel (.xlsx)
            </a>

            <button onclick="window.print()" class="btn-exportar btn-print">
                🖨 Imprimir / PDF
            </button>
        </div>
    @endif
    <span style="font-size:12px; color:var(--cinza-400); margin-left:4px">
        {{ $funcionarios->total() }} funcionário(s) encontrado(s)
    </span>
    {{-- ── TABELA ───────────────────────────────────────────────── --}}
    <div class="card" style="padding:0">
        <div class="table-container" style="border:none">
            <table>
                <thead>
                    <tr>
                        <th>Funcionário</th>
                        <th>Empresa</th>
                        <th>Função</th>
                        <th style="text-align:center">Coord.</th>
                        <th style="text-align:center">Dias</th>
                        <th style="text-align:center">Entradas</th>
                        <th style="text-align:center">Saídas</th>
                        <th style="text-align:center">Total Horas</th>
                        <th style="text-align:center">Média/Turno</th>
                        <th style="text-align:center; width:80px" class="no-print">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($funcionarios as $func)
                        @php
                            $secs = (int) ($func->total_segundos ?? 0);
                            $entrs = (int) ($func->total_entradas ?? 0);
                            $mediaSc = $entrs > 0 ? intdiv($secs, $entrs) : 0;
                            $hT = intdiv($secs, 3600);
                            $mT = intdiv($secs % 3600, 60);
                            $hM = intdiv($mediaSc, 3600);
                            $mM = intdiv($mediaSc % 3600, 60);
                            $semSaida = $entrs - (int) ($func->total_saidas ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-center gap-8">
                                    <img class="td-avatar" src="{{ $func->foto_url }}" alt="">
                                    <div>
                                        <a href="{{ route('ponto.historico', $func) }}"
                                            style="font-weight:700; color:var(--cinza-900); font-size:13.5px">
                                            {{ $func->nome }}
                                        </a>
                                        @if ($func->cpf)
                                            <div
                                                style="font-size:11px; color:var(--cinza-400); font-family:var(--font-mono)">
                                                {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $func->cpf) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:13px">{{ $func->empresa?->nome ?? 'Sem empresa' }}</td>
                            <td style="font-size:12.5px; color:var(--cinza-600)">{{ $func->funcao_cargo }}</td>
                            <td style="text-align:center">
                                @if ($func->coordenador)
                                    <span
                                        style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">⭐
                                        Sim</span>
                                @else
                                    <span style="color:var(--cinza-400); font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="text-align:center">
                                <span class="mono" style="font-weight:700; font-size:14px; color:var(--cinza-800)">
                                    {{ $func->dias_trabalhados ?? 0 }}
                                </span>
                            </td>
                            <td style="text-align:center">
                                <span
                                    style="background:var(--verde-light,#d1fae5);color:var(--verde);border:1px solid #6ee7b7;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
                                    {{ $entrs }}
                                </span>
                            </td>
                            <td style="text-align:center">
                                @if ($semSaida > 0)
                                    <div>
                                        <span
                                            style="background:#fef9c3;color:#854d0e;border:1px solid #fde047;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
                                            {{ (int) ($func->total_saidas ?? 0) }}
                                        </span>
                                        <div style="font-size:10px;color:#d97706;margin-top:2px">{{ $semSaida }}
                                            pendente(s)</div>
                                    </div>
                                @else
                                    <span
                                        style="background:var(--cinza-100);color:var(--cinza-600);border:1px solid var(--cinza-300);padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
                                        {{ (int) ($func->total_saidas ?? 0) }}
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:center">
                                <span class="mono" style="font-weight:800; font-size:14px; color:var(--azul-primario)">
                                    {{ $hT }}h {{ str_pad($mT, 2, '0', STR_PAD_LEFT) }}m
                                </span>
                            </td>
                            <td style="text-align:center">
                                <span class="mono" style="font-size:12.5px; color:var(--cinza-600)">
                                    {{ $hM }}h {{ str_pad($mM, 2, '0', STR_PAD_LEFT) }}m
                                </span>
                            </td>
                            <td style="text-align:center" class="no-print">
                                <a href="{{ route('ponto.historico', $func) }}" class="btn-icon" title="Ver Pontos"
                                    style="width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center">
                                    👁
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:56px; color:var(--cinza-400)">
                                <div style="font-size:40px; margin-bottom:12px">👤</div>
                                <div style="font-size:15px; font-weight:600; margin-bottom:4px">Nenhum funcionário
                                    encontrado</div>
                                <div style="font-size:13px">Tente ajustar os filtros de data, empresa ou evento.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── PAGINAÇÃO ────────────────────────────────────────────── --}}
    <div class="pagination-wrap no-print">
        <div class="pagination-info">
            Exibindo {{ $funcionarios->firstItem() }}–{{ $funcionarios->lastItem() }} de {{ $funcionarios->total() }}
        </div>
        {{ $funcionarios->links('components.pagination') }}
    </div>

@endsection
