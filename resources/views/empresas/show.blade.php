@extends('layouts.app')
@section('title', $empresa->nome)
@section('breadcrumb')
  <a href="{{ route('empresas.index') }}">Empresas</a>
  <span class="sep">/</span>
  <span class="current">{{ $empresa->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">🏢</span> {{ $empresa->nome }}</h1>
    <p class="page-subtitle">
      <span class="badge {{ $empresa->ativo ? 'badge-ativo' : 'badge-inativo' }}">
        {{ $empresa->ativo ? 'Ativa' : 'Inativa' }}
      </span>
      &nbsp;{{ $empresa->total_funcionarios }} funcionário(s) ativo(s)
    </p>
  </div>
  <div class="d-flex gap-8">
    <a href="{{ route('empresas.edit', $empresa) }}" class="btn btn-secondary">✏️ Editar</a>
    <form method="POST" action="{{ route('empresas.toggle-ativo', $empresa) }}"
          data-confirm="{{ $empresa->ativo ? 'Deseja inativar esta empresa?' : 'Deseja ativar esta empresa?' }}"
          data-confirm-icone="{{ $empresa->ativo ? '⚠️' : '✅' }}">
      @csrf @method('PATCH')
      <button type="submit" class="btn {{ $empresa->ativo ? 'btn-danger' : 'btn-success' }}">
        {{ $empresa->ativo ? '🔴 Inativar' : '🟢 Ativar' }}
      </button>
    </form>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px">

  {{-- Dados --}}
  <div class="card">
    <div class="card-header"><div class="card-title">Dados da Empresa</div></div>
    <div style="display:flex; flex-direction:column; gap:14px">
      @foreach([
        ['Responsável', $empresa->responsavel ?: '—', '👤'],
        ['Telefone', $empresa->telefone ?: '—', '📞'],
        ['E-mail', $empresa->email ?: '—', '✉️'],
        ['Cadastrado em', $empresa->created_at->format('d/m/Y'), '📅'],
      ] as [$label, $valor, $icon])
      <div style="display:flex; gap:12px; align-items:flex-start">
        <span style="font-size:18px; width:28px; flex-shrink:0; margin-top:2px">{{ $icon }}</span>
        <div>
          <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); font-weight:600">{{ $label }}</div>
          <div style="font-size:14px; font-weight:500; color:var(--cinza-800); margin-top:2px">{{ $valor }}</div>
        </div>
      </div>
      @endforeach
      @if($empresa->observacoes)
      <div>
        <div style="font-size:11px; text-transform:uppercase; color:var(--cinza-500); font-weight:600; margin-bottom:6px">Observações</div>
        <div style="font-size:13.5px; color:var(--cinza-700); background:var(--cinza-100); padding:10px; border-radius:6px">{{ $empresa->observacoes }}</div>
      </div>
      @endif
    </div>
  </div>

  {{-- Histórico --}}
  <div class="card">
    <div class="card-header">
      <div class="card-title">Histórico de Alterações</div>
    </div>
    @if($empresa->historico->isEmpty())
      <div style="text-align:center; padding:24px; color:var(--cinza-400); font-size:13px">
        Nenhuma alteração registrada.
      </div>
    @else
      <div style="display:flex; flex-direction:column; gap:10px; max-height:260px; overflow-y:auto">
        @foreach($empresa->historico as $h)
        <div style="border-left:3px solid var(--azul-primario); padding:8px 12px; background:var(--cinza-100); border-radius:0 6px 6px 0">
          <div style="font-size:12px; font-weight:600; color:var(--cinza-700)">
            {{ \App\Models\HistoricoEmpresa::labelCampo($h->campo_alterado) }}
          </div>
          <div style="font-size:12px; color:var(--cinza-500); margin-top:2px">
            <span style="background:#fee2e2; padding:1px 6px; border-radius:4px">{{ $h->valor_anterior ?: '(vazio)' }}</span>
            →
            <span style="background:#dcfce7; padding:1px 6px; border-radius:4px">{{ $h->valor_novo ?: '(vazio)' }}</span>
          </div>
          <div style="font-size:11px; color:var(--cinza-400); margin-top:4px">
            {{ $h->usuario->nome ?? 'Sistema' }} · {{ $h->created_at->format('d/m/Y H:i') }}
          </div>
        </div>
        @endforeach
      </div>
    @endif
  </div>

</div>

{{-- Funcionários da empresa --}}
<div class="card mt-24">
  <div class="card-header">
    <div class="card-title">Funcionários ({{ $empresa->funcionariosAtivos->count() }})</div>
    <a href="{{ route('funcionarios.create') }}?empresa_id={{ $empresa->id }}" class="btn btn-sm btn-primary">+ Adicionar</a>
  </div>
  @if($empresa->funcionariosAtivos->isEmpty())
    <div style="text-align:center; padding:32px; color:var(--cinza-400)">Nenhum funcionário cadastrado.</div>
  @else
    <div class="table-container" style="border:none">
      <table>
        <thead><tr><th>Funcionário</th><th>Função</th><th>Área</th><th>Coordenador</th><th>Ponto Hoje</th></tr></thead>
        <tbody>
          @foreach($empresa->funcionariosAtivos as $f)
          <tr>
            <td>
              <div class="d-flex align-center gap-8">
                <img class="td-avatar" src="{{ $f->foto_url }}" alt="">
                <a href="{{ route('funcionarios.show', $f) }}" style="font-weight:600; color:var(--cinza-800)">{{ $f->nome }}</a>
              </div>
            </td>
            <td>{{ $f->funcao_cargo }}</td>
            <td>{{ $f->area_acesso }}</td>
            <td>{{ $f->coordenador ? '<span class="badge badge-coordenador">Sim</span>' : '—' }}</td>
            <td>{!! $f->pontoHoje ? $f->pontoHoje->status_badge : '<span class="badge badge-ausente">— Ausente</span>' !!}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
