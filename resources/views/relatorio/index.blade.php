@extends('layouts.app')

@section('title', 'Relatório Geral')
@section('pagina', 'relatorio')
@section('breadcrumb')
  <span class="sep">/</span>
  <span class="current">Relatório Geral</span>
@endsection

@push('styles')
<style>
  /* Variante vermelho para indicador-card */
  .indicador-card.vermelho::before { background: var(--vermelho); }

  /* Barra de progresso de presença */
  .presenca-bar { display: flex; align-items: center; justify-content: center; gap: 8px; }
  .presenca-bar-track {
    width: 70px; height: 7px;
    background: var(--cinza-200);
    border-radius: 999px;
    overflow: hidden;
    flex-shrink: 0;
  }
  .presenca-bar-fill { height: 100%; border-radius: 999px; transition: width .5s ease; }
  .presenca-bar-fill.alta  { background: var(--verde); }
  .presenca-bar-fill.media { background: var(--azul-primario); }
  .presenca-bar-fill.baixa { background: var(--vermelho); }

  .presenca-pct       { font-family: var(--font-mono); font-size: 13px; font-weight: 700; }
  .presenca-pct.alta  { color: var(--verde); }
  .presenca-pct.media { color: var(--azul-primario); }
  .presenca-pct.baixa { color: var(--vermelho); }

  /* Card de horas totais */
  .horas-total-card {
    background: linear-gradient(135deg, var(--azul-escuro) 0%, #0a6fa8 100%);
    border-radius: var(--border-radius);
    border: none;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-azul);
    color: #fff;
  }
  .horas-total-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: rgba(255,255,255,.65);
    margin-bottom: 4px;
  }
  .horas-total-valor {
    font-family: var(--font-mono);
    font-size: 2.4rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
  }
  .horas-total-sub {
    font-size: 12px;
    color: rgba(255,255,255,.5);
    margin-top: 4px;
  }
  .horas-total-date {
    text-align: right;
  }
  .horas-total-date .date-label {
    font-size: 11px;
    color: rgba(255,255,255,.5);
    text-transform: uppercase;
    letter-spacing: .5px;
  }
  .horas-total-date .date-val {
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    margin-top: 2px;
  }
  .horas-total-date .date-dia {
    font-size: 12px;
    color: rgba(255,255,255,.45);
  }

  /* Seção de indicadores ao vivo */
  .section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: var(--cinza-500);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--cinza-300);
  }

  /* Live dot animado */
  .live-dot {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--verde);
    animation: pulse-verde 1.5s infinite;
    margin-right: 4px;
  }

  /* Filtros card */
  .filtros-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
    padding: 0;
  }
  .filtros-form .form-group { display: flex; flex-direction: column; gap: 4px; }
  .filtros-form .form-group label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--cinza-500);
  }

  /* Tabela: células de número centralizadas */
  td.num { text-align: center; font-family: var(--font-mono); font-size: 14px; font-weight: 600; }

  /* Badge de valor simples */
  .badge-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    padding: 3px 10px;
    border-radius: 20px;
    font-family: var(--font-mono);
    font-size: 13px;
    font-weight: 700;
  }
  .badge-num.verde   { background: var(--verde-light);    color: var(--verde); }
  .badge-num.azul    { background: var(--azul-light);     color: var(--azul-primario); }
  .badge-num.cinza   { background: var(--cinza-200);      color: var(--cinza-600); }
  .badge-num.vermelho{ background: var(--vermelho-light); color: var(--vermelho); }

  /* Linha de total no rodapé da tabela */
  tfoot td {
    padding: 12px 16px;
    font-weight: 700;
    background: var(--cinza-100);
    border-top: 2px solid var(--cinza-300);
    font-size: 13px;
    color: var(--cinza-700);
  }
  tfoot td.num { font-family: var(--font-mono); font-size: 14px; color: var(--azul-escuro); }

  /* Empresa sem dados */
  .empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--cinza-400);
  }
  .empty-state .empty-icon { font-size: 40px; margin-bottom: 12px; }
  .empty-state p { font-size: 14px; }

  @media (max-width: 900px) {
    .horas-total-card { flex-direction: column; align-items: flex-start; }
    .horas-total-date { text-align: left; }
    .indicadores-grid { grid-template-columns: repeat(2, 1fr); }
  }
</style>
@endpush

@section('content')

{{-- ── PAGE HEADER ──────────────────────────────────────────────── --}}
<div class="page-header">
  <div>
    <h1 class="page-title">
      <span class="page-icon">📊</span>
      Relatório Geral
    </h1>
    <p class="page-subtitle">
      Resumo de presença e horas trabalhadas por empresa
    </p>
  </div>
</div>

{{-- ── FILTROS ──────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title">🔍 Filtros</div>
    @if($dataFiltro || $empresaId || $eventoId)
      <a href="{{ route('relatorio.index') }}" class="btn btn-secondary btn-sm">
        ✕ Limpar filtros
      </a>
    @endif
  </div>

  <form method="GET" action="{{ route('relatorio.index') }}" class="filtros-form" style="padding: 0 4px 4px">

    {{-- Filtro Evento --}}
    <div class="form-group">
      <label>Evento</label>
      <select name="evento_id" class="form-control form-select" onchange="this.form.submit()" style="min-width:220px">
        <option value="">Todos os eventos</option>
        @foreach($eventosLista as $ev)
          <option value="{{ $ev->id }}" {{ (string)$eventoId === (string)$ev->id ? 'selected' : '' }}>
            {{ $ev->nome }}
            ({{ \Carbon\Carbon::parse($ev->data_inicio)->format('d/m') }}
            @if($ev->data_inicio !== $ev->data_fim)
              – {{ \Carbon\Carbon::parse($ev->data_fim)->format('d/m') }}
            @endif)
          </option>
        @endforeach
      </select>
    </div>

    {{-- Filtro Data --}}
    <div class="form-group">
      <label>Data</label>
      <select name="data" class="form-control form-select" onchange="this.form.submit()" style="min-width:200px">
        <option value="">Todas as datas</option>
        @foreach($datasDisponiveis as $d)
          <option value="{{ $d }}" {{ $dataFiltro === $d ? 'selected' : '' }}>
            {{ \Carbon\Carbon::parse($d)->format('d/m/Y') }}
            @if($d === today()->format('Y-m-d')) &nbsp;(Hoje) @endif
          </option>
        @endforeach
      </select>
    </div>

    {{-- Filtro Empresa --}}
    <div class="form-group">
      <label>Empresa</label>
      <select name="empresa_id" class="form-control form-select" onchange="this.form.submit()" style="min-width:220px">
        <option value="">Todas as empresas</option>
        @foreach($empresasLista as $emp)
          <option value="{{ $emp->id }}" {{ (string)$empresaId === (string)$emp->id ? 'selected' : '' }}>
            {{ $emp->nome }}
          </option>
        @endforeach
      </select>
    </div>

    {{-- Botão Aplicar --}}
    <div class="form-group">
      <label>&nbsp;</label>
      <button type="submit" class="btn btn-primary">Aplicar</button>
    </div>

  </form>
</div>

{{-- ── INDICADORES EM TEMPO REAL ───────────────────────────────── --}}
<div class="section-title">
  <span class="live-dot"></span>
  Indicadores em Tempo Real
  <small class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:8px">
    Hoje · Atualizado às <span id="ind-atualizado">{{ now()->format('H:i:s') }}</span>
  </small>
</div>

<div class="indicadores-grid" style="margin-bottom: 24px">

  <div class="indicador-card verde">
    <span class="indicador-icone">🟢</span>
    <div class="indicador-valor" id="ind-dentro-evento">{{ $indicadoresAoVivo['dentro_evento'] }}</div>
    <div class="indicador-label">Dentro do Evento Agora</div>
  </div>

  <div class="indicador-card azul">
    <span class="indicador-icone">✅</span>
    <div class="indicador-valor" id="ind-finalizados">{{ $indicadoresAoVivo['finalizados'] }}</div>
    <div class="indicador-label">Finalizados</div>
  </div>

  <div class="indicador-card vermelho">
    <span class="indicador-icone">⏳</span>
    <div class="indicador-valor" id="ind-nao-entraram">{{ $indicadoresAoVivo['nao_entraram'] }}</div>
    <div class="indicador-label">Não Entraram</div>
  </div>

  <div class="indicador-card cinza">
    <span class="indicador-icone">👥</span>
    <div class="indicador-valor" id="ind-total-dia">{{ $indicadoresAoVivo['total_dia'] }}</div>
    <div class="indicador-label">Total no Dia</div>
  </div>

  <div class="indicador-card roxo">
    <span class="indicador-icone">⭐</span>
    <div class="indicador-valor" id="ind-coordenadores">{{ $indicadoresAoVivo['coordenadores'] }}</div>
    <div class="indicador-label">Coordenadores</div>
  </div>

  <div class="indicador-card azul">
    <span class="indicador-icone">🏢</span>
    <div class="indicador-valor" id="ind-empresas">{{ $indicadoresAoVivo['empresas'] }}</div>
    <div class="indicador-label">Empresas no Evento</div>
  </div>

</div>

{{-- ── HORAS TRABALHADAS NO DIA FILTRADO ───────────────────────── --}}
<div class="horas-total-card">
  <div>
    <div class="horas-total-label">
      ⏱ Horas Trabalhadas
      @if($dataFiltro) · {{ \Carbon\Carbon::parse($dataFiltro)->format('d/m/Y') }}
      @else · Todas as Datas
      @endif
      @if($eventoId)
        @php $evSel = $eventosLista->firstWhere('id', $eventoId) @endphp
        @if($evSel) · {{ $evSel->nome }} @endif
      @endif
    </div>
    <div class="horas-total-valor">{{ $totalHorasGeralFormatado }}</div>
    <div class="horas-total-sub">
      soma total entre
      @if($empresaId) empresa selecionada
      @else todas as empresas
      @endif
      @if($eventoId) · evento filtrado @endif
    </div>
  </div>

  @if($dataFiltro)
  <div class="horas-total-date">
    <div class="date-label">Data filtrada</div>
    <div class="date-val">{{ \Carbon\Carbon::parse($dataFiltro)->format('d/m/Y') }}</div>
    <div class="date-dia">{{ \Carbon\Carbon::parse($dataFiltro)->locale('pt_BR')->isoFormat('dddd') }}</div>
  </div>
  @else
  <div class="horas-total-date">
    <div class="date-label">Período</div>
    <div class="date-val" style="font-size:1.1rem">Todo o Evento</div>
    <div class="date-dia">todas as datas disponíveis</div>
  </div>
  @endif
</div>

{{-- ── TABELA POR EMPRESA ───────────────────────────────────────── --}}
@php
  $totFuncionarios = collect($relatorio)->sum('total_funcionarios');
  $totNoEvento     = collect($relatorio)->sum('total_no_evento');
  $totFinalizados  = collect($relatorio)->sum('finalizados');
  $totNaoEntraram  = collect($relatorio)->sum('nao_entraram');
  $totPresenca     = $totFuncionarios > 0
                     ? round(($totNoEvento / $totFuncionarios) * 100, 1)
                     : 0;
@endphp

<div class="section-title" style="margin-top: 0">
  🏢 Resumo por Empresa
  <small class="text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;margin-left:8px">
    {{ count($relatorio) }} empresa(s) exibida(s)
  </small>
</div>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th style="min-width:200px">Empresa</th>
          <th style="text-align:center">Total<br>Funcionários</th>
          <th style="text-align:center">No&nbsp;Evento</th>
          <th style="text-align:center">Finalizados</th>
          <th style="text-align:center">Não&nbsp;Entraram</th>
          <th style="text-align:center;min-width:150px">% Presença</th>
          <th style="text-align:center">Horas Trabalhadas</th>
        </tr>
      </thead>

      <tbody>
        @forelse($relatorio as $row)
        @php
          $pct   = $row['presenca'];
          $nivel = $pct >= 80 ? 'alta' : ($pct >= 50 ? 'media' : 'baixa');
        @endphp
        <tr>
          {{-- Empresa --}}
          <td>
            <div style="font-weight:600; color:var(--cinza-800)">{{ $row['empresa']->nome }}</div>
            @if($row['empresa']->responsavel)
              <div style="font-size:11px; color:var(--cinza-500); margin-top:2px">
                {{ $row['empresa']->responsavel }}
              </div>
            @endif
          </td>

          {{-- Total funcionários --}}
          <td class="num" style="color:var(--cinza-600)">
            {{ $row['total_funcionarios'] }}
          </td>

          {{-- No evento --}}
          <td style="text-align:center">
            <span class="badge-num verde">{{ $row['total_no_evento'] }}</span>
          </td>

          {{-- Finalizados --}}
          <td style="text-align:center">
            <span class="badge-num azul">{{ $row['finalizados'] }}</span>
          </td>

          {{-- Não entraram --}}
          <td style="text-align:center">
            @if($row['nao_entraram'] === 0)
              <span style="color:var(--verde); font-size:13px; font-weight:600">✓ Todos</span>
            @else
              <span class="badge-num vermelho">{{ $row['nao_entraram'] }}</span>
            @endif
          </td>

          {{-- % Presença --}}
          <td style="text-align:center">
            <div class="presenca-bar">
              <div class="presenca-bar-track">
                <div class="presenca-bar-fill {{ $nivel }}"
                     style="width:{{ min(100, $pct) }}%"></div>
              </div>
              <span class="presenca-pct {{ $nivel }}">{{ number_format($pct, 1) }}%</span>
            </div>
          </td>

          {{-- Horas --}}
          <td class="num" style="color:var(--azul-escuro)">
            {{ $row['horas_trabalhadas'] }}
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <div class="empty-icon">📊</div>
              <p>Nenhuma empresa encontrada com os filtros aplicados.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>

      {{-- LINHA DE TOTAIS --}}
      @if(count($relatorio) > 1)
      @php
        $totNivel = $totPresenca >= 80 ? 'alta' : ($totPresenca >= 50 ? 'media' : 'baixa');
      @endphp
      <tfoot>
        <tr>
          <td>TOTAL GERAL ({{ count($relatorio) }} empresas)</td>
          <td class="num">{{ $totFuncionarios }}</td>
          <td class="num" style="color:var(--verde)">{{ $totNoEvento }}</td>
          <td class="num" style="color:var(--azul-primario)">{{ $totFinalizados }}</td>
          <td class="num" style="color:var(--vermelho)">{{ $totNaoEntraram }}</td>
          <td style="text-align:center">
            <div class="presenca-bar">
              <div class="presenca-bar-track">
                <div class="presenca-bar-fill {{ $totNivel }}"
                     style="width:{{ min(100,$totPresenca) }}%"></div>
              </div>
              <span class="presenca-pct {{ $totNivel }}">{{ number_format($totPresenca, 1) }}%</span>
            </div>
          </td>
          <td class="num">{{ $totalHorasGeralFormatado }}</td>
        </tr>
      </tfoot>
      @endif

    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
  // ── Polling de indicadores ao vivo (a cada 30s) ──────────────
  function atualizarIndicadoresVivo() {
    $.getJSON('{{ route("api.relatorio.indicadores") }}')
      .done(function(data) {
        $('#ind-dentro-evento').text(data.dentro_evento);
        $('#ind-finalizados').text(data.finalizados);
        $('#ind-nao-entraram').text(data.nao_entraram);
        $('#ind-total-dia').text(data.total_dia);
        $('#ind-coordenadores').text(data.coordenadores);
        $('#ind-empresas').text(data.empresas);
        $('#ind-atualizado').text(data.atualizado_em);
      });
  }

  setInterval(atualizarIndicadoresVivo, 30000);
</script>
@endpush
