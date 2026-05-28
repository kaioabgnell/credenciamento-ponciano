@extends('layouts.app')

@section('title', 'Dashboard')
@section('pagina', 'dashboard')
@section('breadcrumb') <span class="current">Dashboard</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title">
      <span class="page-icon">⊞</span>
      Painel de Controle
    </h1>
    <p class="page-subtitle">
      Monitoramento em tempo real do evento
      @if($eventoAtivo)
        <span style="display:inline-flex;align-items:center;gap:5px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600;margin-left:8px">
          {{ $eventoAtivo->nome }}
        </span>
      @endif
      <span class="ind-atualizado" style="margin-left:8px; font-size:11px; color:var(--cinza-400)"></span>
    </p>
  </div>

  <div class="d-flex gap-12 align-center">
    {{-- Filtro de data --}}
    <form method="GET" id="form-filtro-data" style="display:flex; gap:8px; align-items:center">
      <select name="data" class="form-control form-select" style="width:auto"
              onchange="document.getElementById('form-filtro-data').submit()">
        @forelse($datas_disponiveis as $d)
          <option value="{{ $d }}" {{ $data === $d ? 'selected' : '' }}>
            {{ \Carbon\Carbon::parse($d)->format('d/m/Y') }}
            {{ $d === today()->format('Y-m-d') ? '(Hoje)' : '' }}
          </option>
        @empty
          <option value="{{ today()->format('Y-m-d') }}">{{ today()->format('d/m/Y') }} (Hoje)</option>
        @endforelse
      </select>
    </form>

    <a href="{{ route('ponto.registrar') }}" class="btn btn-primary">
      ⏱ Bater Ponto
    </a>
  </div>
</div>

{{-- ---- INDICADORES ---- --}}
<div class="indicadores-grid">

  <div class="indicador-card verde">
    <span class="indicador-icone">🟢</span>
    <div class="indicador-valor ind-presentes">{{ $indicadores['presentes'] }}</div>
    <div class="indicador-label">Presentes Agora</div>
  </div>

  <div class="indicador-card azul">
    <span class="indicador-icone">✅</span>
    <div class="indicador-valor ind-finalizados">{{ $indicadores['finalizados'] }}</div>
    <div class="indicador-label">Finalizados</div>
  </div>

  <div class="indicador-card cinza">
    <span class="indicador-icone">👥</span>
    <div class="indicador-valor ind-total">{{ $indicadores['total_dia'] }}</div>
    <div class="indicador-label">Total no Dia</div>
  </div>

  <div class="indicador-card roxo">
    <span class="indicador-icone">⭐</span>
    <div class="indicador-valor ind-coordenadores">{{ $indicadores['coordenadores'] }}</div>
    <div class="indicador-label">Coordenadores</div>
  </div>

  <div class="indicador-card azul">
    <span class="indicador-icone">🏢</span>
    <div class="indicador-valor">{{ $indicadores['empresas'] }}</div>
    <div class="indicador-label">Empresas Cadastradas</div>
  </div>

  <div class="indicador-card cinza">
    <span class="indicador-icone">👤</span>
    <div class="indicador-valor">{{ $indicadores['funcionarios'] }}</div>
    <div class="indicador-label">Funcionários Cadastrados</div>
  </div>

</div>

{{-- ---- GRID INFERIOR ---- --}}
<div class="dashboard-grid-2">

  {{-- Presentes agora --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="icon">🟢</span>
        Presentes Agora
      </div>
      <a href="{{ route('ponto.index', ['data' => $data, 'status' => 'presente']) }}" class="btn btn-sm btn-secondary">Ver todos</a>
    </div>

    @if($presentes->isEmpty())
      <div style="text-align:center; padding:32px; color:var(--cinza-400);">
        <div style="font-size:32px; margin-bottom:8px;">👋</div>
        Nenhum funcionário presente no momento.
      </div>
    @else
      <div class="table-container" style="border:none; max-height:280px; overflow-y:auto;">
        <table>
          <thead>
            <tr>
              <th>Funcionário</th>
              <th>Empresa</th>
              <th>Entrada</th>
            </tr>
          </thead>
          <tbody>
            @foreach($presentes as $p)
            <tr>
              <td>
                <div class="d-flex align-center gap-8">
                  <img class="td-avatar" src="{{ $p->funcionario->foto_url }}" alt="">
                  <span class="td-nome">{{ $p->funcionario->nome }}</span>
                  @if($p->funcionario->coordenador)
                    <span class="badge badge-coordenador" style="font-size:10px">Coord.</span>
                  @endif
                </div>
              </td>
              <td class="text-muted">{{ $p->empresa->nome }}</td>
              <td class="mono" style="color:var(--verde); font-weight:600">{{ substr($p->entrada, 0, 5) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Por empresa --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <span class="icon">🏢</span>
        Funcionários por Empresa
      </div>
      <span class="text-muted" style="font-size:12px">{{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</span>
    </div>

    @if($por_empresa->isEmpty())
      <div style="text-align:center; padding:32px; color:var(--cinza-400);">
        <div style="font-size:32px; margin-bottom:8px;">📊</div>
        Sem dados para exibir.
      </div>
    @else
      <div style="display:flex; flex-direction:column; gap:10px">
        @php $max = $por_empresa->max('total_ponto') ?: 1 @endphp
        @foreach($por_empresa as $emp)
        <div>
          <div class="d-flex justify-between" style="margin-bottom:4px; font-size:13px">
            <span style="font-weight:600; color:var(--cinza-800)">{{ $emp->nome }}</span>
            <span class="mono" style="color:var(--azul-primario); font-weight:700">{{ $emp->total_ponto }}</span>
          </div>
          <div style="background:var(--cinza-200); border-radius:4px; height:6px; overflow:hidden;">
            <div style="height:100%; background:var(--azul-primario); border-radius:4px; width:{{ ($emp->total_ponto / $max) * 100 }}%; transition:width .5s ease;"></div>
          </div>
        </div>
        @endforeach
      </div>
    @endif
  </div>

</div>

@endsection
