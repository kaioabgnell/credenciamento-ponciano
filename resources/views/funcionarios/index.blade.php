@extends('layouts.app')
@section('title', 'Funcionários')
@section('breadcrumb') <span class="current">Funcionários</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">👥</span> Funcionários</h1>
    <p class="page-subtitle">{{ $funcionarios->total() }} funcionário(s) encontrado(s)</p>
  </div>
  <a href="{{ route('funcionarios.create') }}" class="btn btn-primary">+ Novo Funcionário</a>
</div>

<form method="GET" class="filtros-bar">
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" name="busca" id="campo-busca-global" class="form-control"
           placeholder="Buscar por nome, CPF ou função..."
           value="{{ $busca }}">
  </div>

  <select name="empresa_id" class="form-control form-select" style="width:180px" onchange="this.form.submit()">
    <option value="">Todas as empresas</option>
    @foreach($empresas as $emp)
      <option value="{{ $emp->id }}" {{ $empresa_id == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}</option>
    @endforeach
  </select>

  <select name="area" class="form-control form-select" style="width:150px" onchange="this.form.submit()">
    <option value="">Todas as áreas</option>
    @foreach($areas as $a)
      <option value="{{ $a }}" {{ $area === $a ? 'selected' : '' }}>{{ $a }}</option>
    @endforeach
  </select>

  <select name="coordenador" class="form-control form-select" style="width:150px" onchange="this.form.submit()">
    <option value="">Todos</option>
    <option value="1" {{ $coordenador === '1' ? 'selected' : '' }}>Coordenadores</option>
  </select>

  @if($busca || $empresa_id || $area || $coordenador)
    <a href="{{ route('funcionarios.index') }}" class="btn btn-secondary">Limpar</a>
  @endif
</form>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Funcionário</th>
          <th>Empresa</th>
          <th>Função / Cargo</th>
          <th>Área</th>
          <th>CPF</th>
          <th>Coord.</th>
          <th>Ponto Hoje</th>
          <th style="text-align:right">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($funcionarios as $func)
        <tr>
          <td>
            <div class="d-flex align-center gap-8">
              <img class="td-avatar" src="{{ $func->foto_url }}" alt="">
              <div>
                <div class="td-nome">{{ $func->nome }}</div>
                @if($func->telefone)
                  <div style="font-size:11px; color:var(--cinza-500)">{{ $func->telefone }}</div>
                @endif
              </div>
            </div>
          </td>
          <td style="font-size:13px">
            @if($func->empresa)
              <a href="{{ route('empresas.show', $func->empresa) }}"
                 style="color:var(--azul-primario); font-weight:500">
                {{ $func->empresa->nome }}
              </a>
            @else
              <span style="color:var(--cinza-400)">Sem empresa</span>
            @endif
          </td>
          <td>{{ $func->funcao_cargo }}</td>
          <td>
            <span style="background:var(--cinza-200); color:var(--cinza-700); padding:2px 8px; border-radius:20px; font-size:11.5px; font-weight:600">
              {{ $func->area_acesso }}
            </span>
          </td>
          <td class="mono" style="font-size:12.5px">{{ $func->cpf_formatado }}</td>
          <td>
            @if($func->coordenador)
              <span class="badge badge-coordenador">⭐ Sim</span>
            @else
              <span style="color:var(--cinza-400)">—</span>
            @endif
          </td>
          <td>{!! $func->pontoHoje ? $func->pontoHoje->status_badge : '<span class="badge badge-ausente">— Ausente</span>' !!}</td>
          <td style="text-align:right">
            <div class="d-flex gap-8" style="justify-content:flex-end">
              <a href="{{ route('funcionarios.show', $func) }}" class="btn-icon" title="Ver">👁</a>
              <a href="{{ route('funcionarios.edit', $func) }}" class="btn-icon" title="Editar">✏️</a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align:center; padding:48px; color:var(--cinza-400)">
            <div style="font-size:36px; margin-bottom:8px">👥</div>
            Nenhum funcionário encontrado.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    Exibindo {{ $funcionarios->firstItem() }}–{{ $funcionarios->lastItem() }} de {{ $funcionarios->total() }}
  </div>
  {{ $funcionarios->links('components.pagination') }}
</div>

@endsection
