@extends('layouts.app')
@section('title', 'Eventos')
@section('breadcrumb') <span class="current">Eventos</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title">Eventos</h1>
    <p class="page-subtitle">Gerencie os eventos do sistema de credenciamento</p>
  </div>
  <a href="{{ route('eventos.create') }}" class="btn btn-primary">+ Novo Evento</a>
</div>

{{-- Filtros --}}
<form method="GET" class="filtros-bar" style="margin-bottom:20px">
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" name="busca" class="form-control"
           placeholder="Buscar por nome ou organizador..."
           value="{{ $busca }}">
  </div>

  <select name="status" class="form-control form-select" style="width:160px" onchange="this.form.submit()">
    <option value="todos"   {{ $status === 'todos'   ? 'selected' : '' }}>Todos</option>
    <option value="ativo"   {{ $status === 'ativo'   ? 'selected' : '' }}>Ativos</option>
    <option value="inativo" {{ $status === 'inativo' ? 'selected' : '' }}>Inativos</option>
  </select>

  <button type="submit" class="btn btn-secondary">Filtrar</button>
</form>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Evento</th>
          <th>Organizador</th>
          <th>Período</th>
          <th style="text-align:center">Duração</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Situação</th>
          <th style="text-align:center;width:120px">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($eventos as $evento)
        <tr>
          <td>
            <div style="font-weight:600; color:var(--cinza-800)">{{ $evento->nome }}</div>
          </td>
          <td>
            <div style="font-size:13px">{{ $evento->nome_organizador ?: '—' }}</div>
            @if($evento->telefone_organizador)
              <div style="font-size:11px; color:var(--cinza-500)">{{ $evento->telefone_organizador }}</div>
            @endif
          </td>
          <td class="mono" style="font-size:12.5px; white-space:nowrap">
            {{ $evento->periodo_formatado }}
          </td>
          <td style="text-align:center">
            <span style="font-size:12px; color:var(--cinza-600); font-weight:600">
              {{ $evento->duracao_dias }} dia{{ $evento->duracao_dias > 1 ? 's' : '' }}
            </span>
          </td>
          <td style="text-align:center">{!! $evento->status_badge !!}</td>
          <td style="text-align:center">
            @if($evento->ativo)
              <span class="badge" style="background:var(--verde-light);color:var(--verde)">Ativo</span>
            @else
              <span class="badge badge-ausente">Inativo</span>
            @endif
          </td>
          <td style="text-align:center; white-space:nowrap">
            <a href="{{ route('eventos.edit', $evento) }}"
               class="btn-icon" title="Editar" style="width:30px;height:30px;margin-right:4px">
              ✏️
            </a>
            <form method="POST" action="{{ route('eventos.toggle-ativo', $evento) }}"
                  style="display:inline">
              @csrf @method('PATCH')
              <button type="submit" class="btn-icon" title="{{ $evento->ativo ? 'Inativar' : 'Ativar' }}"
                      style="width:30px;height:30px;
                             {{ $evento->ativo ? 'background:var(--vermelho-light);border-color:var(--vermelho);color:var(--vermelho)' : 'background:var(--verde-light);border-color:var(--verde);color:var(--verde)' }}">
                {{ $evento->ativo ? '✕' : '✓' }}
              </button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center; padding:48px; color:var(--cinza-400)">
            <div style="font-size:36px; margin-bottom:8px"></div>
            Nenhum evento encontrado.
            <a href="{{ route('eventos.create') }}" style="display:block; margin-top:8px; font-weight:600">
              + Cadastrar primeiro evento
            </a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    Exibindo {{ $eventos->firstItem() ?? 0 }}–{{ $eventos->lastItem() ?? 0 }} de {{ $eventos->total() }}
  </div>
  {{ $eventos->links('components.pagination') }}
</div>

@endsection
