@extends('layouts.app')
@section('title', 'Empresas')
@section('breadcrumb') <span class="current">Empresas</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">🏢</span> Empresas</h1>
    <p class="page-subtitle">{{ $total }} empresa(s) cadastrada(s)</p>
  </div>
  <a href="{{ route('empresas.create') }}" class="btn btn-primary">+ Nova Empresa</a>
</div>

{{-- Filtro alfabeto --}}
<div class="filtro-alfabeto">
  <a href="{{ route('empresas.index', array_merge(request()->except('letra','page'))) }}"
     class="{{ !$letra ? 'active' : '' }}">Todos</a>
  @foreach(range('A','Z') as $l)
    <a href="{{ route('empresas.index', array_merge(request()->except('page'), ['letra' => $l])) }}"
       class="{{ $letra === $l ? 'active' : '' }}">{{ $l }}</a>
  @endforeach
</div>

{{-- Filtros --}}
<form method="GET" class="filtros-bar">
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" name="busca" id="campo-busca-global"
           class="form-control" placeholder="Buscar empresa, responsável ou e-mail..."
           value="{{ $busca }}">
  </div>
  <select name="status" class="form-control form-select" style="width:140px" onchange="this.form.submit()">
    <option value="ativo"   {{ $status==='ativo'   ? 'selected' : '' }}>Ativas</option>
    <option value="inativo" {{ $status==='inativo' ? 'selected' : '' }}>Inativas</option>
    <option value="todas"   {{ $status==='todas'   ? 'selected' : '' }}>Todas</option>
  </select>
  @if($busca || $letra)
    <a href="{{ route('empresas.index') }}" class="btn btn-secondary">Limpar</a>
  @endif
</form>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Empresa</th>
          <th>Responsável</th>
          <th>Telefone</th>
          <th>E-mail</th>
          <th>Funcionários</th>
          <th>Status</th>
          <th style="text-align:right">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($empresas as $empresa)
        <tr>
          <td class="text-muted mono" style="font-size:12px">{{ $empresa->id }}</td>
          <td>
            <a href="{{ route('empresas.show', $empresa) }}"
               style="font-weight:600; color:var(--cinza-800)">{{ $empresa->nome }}</a>
          </td>
          <td>{{ $empresa->responsavel ?: '—' }}</td>
          <td class="mono">{{ $empresa->telefone ?: '—' }}</td>
          <td>{{ $empresa->email ?: '—' }}</td>
          <td>
            <span style="background:var(--azul-light); color:var(--azul-primario); font-weight:700;
                          padding:2px 10px; border-radius:20px; font-size:12px; font-family:var(--font-mono)">
              {{ $empresa->funcionarios_ativos_count }}
            </span>
          </td>
          <td>
            <span class="badge {{ $empresa->ativo ? 'badge-ativo' : 'badge-inativo' }}">
              {{ $empresa->ativo ? 'Ativa' : 'Inativa' }}
            </span>
          </td>
          <td style="text-align:right">
            <div class="d-flex gap-8" style="justify-content:flex-end">
              <a href="{{ route('empresas.show', $empresa) }}" class="btn-icon" title="Ver">👁</a>
              <a href="{{ route('empresas.edit', $empresa) }}" class="btn-icon" title="Editar">✏️</a>
              <form method="POST" action="{{ route('empresas.toggle-ativo', $empresa) }}"
                    data-confirm="{{ $empresa->ativo ? 'Inativar esta empresa?' : 'Ativar esta empresa?' }}"
                    data-confirm-icone="{{ $empresa->ativo ? '⚠️' : '✅' }}"
                    data-confirm-tipo="{{ $empresa->ativo ? 'danger' : 'success' }}"
                    data-confirm-btn="{{ $empresa->ativo ? 'Inativar' : 'Ativar' }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn-icon" title="{{ $empresa->ativo ? 'Inativar' : 'Ativar' }}">
                  {{ $empresa->ativo ? '🔴' : '🟢' }}
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center; padding:48px; color:var(--cinza-400)">
            <div style="font-size:36px; margin-bottom:8px">🏢</div>
            Nenhuma empresa encontrada.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    Exibindo {{ $empresas->firstItem() }} – {{ $empresas->lastItem() }} de {{ $empresas->total() }} empresas
  </div>
  {{ $empresas->links('components.pagination') }}
</div>

@endsection
