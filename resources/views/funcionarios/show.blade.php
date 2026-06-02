@extends('layouts.app')
@section('title', $funcionario->nome)
@section('breadcrumb')
  <a href="{{ route('funcionarios.index') }}">Funcionários</a>
  <span class="sep">/</span>
  <span class="current">{{ $funcionario->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">👤</span> {{ $funcionario->nome }}</h1>
    <p class="page-subtitle">
      <span class="badge {{ $funcionario->ativo ? 'badge-ativo' : 'badge-inativo' }}">
        {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
      </span>
      @if($funcionario->coordenador)
        &nbsp;<span class="badge badge-coordenador">⭐ Coordenador</span>
      @endif
      &nbsp;{{ $funcionario->empresa->nome ?? '—' }}
    </p>
  </div>
  <div class="d-flex gap-8">
    <a href="{{ route('funcionarios.edit', $funcionario) }}" class="btn btn-secondary">✏️ Editar</a>
    <form method="POST" action="{{ route('funcionarios.toggle-ativo', $funcionario) }}"
          data-confirm="{{ $funcionario->ativo ? 'Deseja inativar este funcionário?' : 'Deseja ativar este funcionário?' }}"
          data-confirm-icone="{{ $funcionario->ativo ? '⚠️' : '✅' }}">
      @csrf @method('PATCH')
      <button type="submit" class="btn {{ $funcionario->ativo ? 'btn-danger' : 'btn-success' }}">
        {{ $funcionario->ativo ? '🔴 Inativar' : '🟢 Ativar' }}
      </button>
    </form>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px">

  {{-- Dados pessoais --}}
  <div class="card">
    <div class="card-header"><div class="card-title">Dados do Funcionário</div></div>
    <div style="display:flex; gap:20px; align-items:flex-start">

      {{-- Foto --}}
      <img src="{{ $funcionario->foto_url }}" alt="{{ $funcionario->nome }}"
           style="width:80px; height:80px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2px solid var(--cinza-200)">

      {{-- Informações --}}
      <div style="display:flex; flex-direction:column; gap:12px; flex:1">
        @php
          $infoItens = [
            ['CPF',              $funcionario->cpf_formatado, '🪪'],
            ['Telefone',         $funcionario->telefone ?: '—', '📞'],
            ['Empresa',          $funcionario->empresa?->nome ?? '—', '🏢'],
            ['Função',           $funcionario->funcao_cargo, '💼'],
            ['Área',             $funcionario->area_acesso, '🚪'],
            ['Cadastrado',       $funcionario->created_at->format('d/m/Y'), '📅'],
          ];
          if ($funcionario->data_nascimento) {
              $infoItens[] = [
                'Data de Nascimento',
                \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y'),
                '🎂'
              ];
          }
        @endphp
        @foreach($infoItens as [$label, $valor, $icon])
        <div style="display:flex; gap:10px; align-items:flex-start">
          <span style="font-size:16px; width:24px; flex-shrink:0; margin-top:2px">{{ $icon }}</span>
          <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">{{ $label }}</div>
            <div style="font-size:14px; font-weight:500; color:var(--cinza-800); margin-top:1px">{{ $valor }}</div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Status ponto hoje --}}
  <div class="card">
    <div class="card-header"><div class="card-title">Ponto de Hoje</div></div>
    @php $pontoHoje = $funcionario->pontos->first(fn($p) => $p->data->isToday()); @endphp
    @if($pontoHoje)
      <div style="display:flex; flex-direction:column; gap:12px">
        <div style="display:flex; gap:10px; align-items:flex-start">
          <span style="font-size:16px; width:24px">⏱</span>
          <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">Status</div>
            <div style="margin-top:2px">{!! $pontoHoje->status_badge !!}</div>
          </div>
        </div>
        <div style="display:flex; gap:10px; align-items:flex-start">
          <span style="font-size:16px; width:24px">🟢</span>
          <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">Entrada</div>
            <div style="font-size:14px; font-weight:500; color:var(--cinza-800); margin-top:1px">
              {{ $pontoHoje->entrada ? substr($pontoHoje->entrada, 0, 5) : '—' }}
            </div>
          </div>
        </div>
        <div style="display:flex; gap:10px; align-items:flex-start">
          <span style="font-size:16px; width:24px">🔴</span>
          <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">Saída</div>
            <div style="font-size:14px; font-weight:500; color:var(--cinza-800); margin-top:1px">
              {{ $pontoHoje->saida ? substr($pontoHoje->saida, 0, 5) : '—' }}
            </div>
          </div>
        </div>
        @if($pontoHoje->horas_trabalhadas)
        <div style="display:flex; gap:10px; align-items:flex-start">
          <span style="font-size:16px; width:24px">⏳</span>
          <div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">Horas Trabalhadas</div>
            <div style="font-size:14px; font-weight:500; color:var(--cinza-800); margin-top:1px">{{ $pontoHoje->horas_trabalhadas }}</div>
          </div>
        </div>
        @endif
      </div>
    @else
      <div style="text-align:center; padding:32px; color:var(--cinza-400); font-size:13px">
        <div style="font-size:32px; margin-bottom:8px">⏱</div>
        Sem registro de ponto hoje.
      </div>
    @endif
  </div>

</div>

{{-- Histórico de Pontos --}}
<div class="card mt-24">
  <div class="card-header">
    <div class="card-title">Histórico de Pontos (últimos {{ $funcionario->pontos->count() }})</div>
    <a href="{{ route('ponto.historico', $funcionario) }}" class="btn btn-sm btn-secondary">Ver tudo</a>
  </div>
  @if($funcionario->pontos->isEmpty())
    <div style="text-align:center; padding:32px; color:var(--cinza-400)">Nenhum registro de ponto encontrado.</div>
  @else
    <div class="table-container" style="border:none">
      <table>
        <thead>
          <tr>
            <th>Data</th>
            <th>Entrada</th>
            <th>Saída</th>
            <th>Horas</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($funcionario->pontos as $ponto)
          <tr>
            <td style="font-weight:500">{{ $ponto->data->format('d/m/Y') }}</td>
            <td>{{ $ponto->entrada ? substr($ponto->entrada, 0, 5) : '—' }}</td>
            <td>{{ $ponto->saida  ? substr($ponto->saida,  0, 5) : '—' }}</td>
            <td>{{ $ponto->horas_trabalhadas ?: '—' }}</td>
            <td>{!! $ponto->status_badge !!}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
