@extends('layouts.app')
@section('title', 'Usuários')
@section('breadcrumb') <span class="current">Usuários</span> @endsection

@section('content')

<div class="page-header">
  <h1 class="page-title"><span class="page-icon">🔑</span> Usuários do Sistema</h1>
  <a href="{{ route('usuarios.create') }}" class="btn btn-primary">+ Novo Usuário</a>
</div>

<div class="filtros-bar">
  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" id="campo-busca-global" name="busca" class="form-control"
           placeholder="Buscar por nome, e-mail ou cargo..." value="{{ $busca }}">
  </div>
</div>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr><th>Usuário</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Cargo</th><th>Status</th><th style="text-align:right">Ações</th></tr>
      </thead>
      <tbody>
        @forelse($usuarios as $u)
        <tr>
          <td>
            <div class="d-flex align-center gap-10">
              <img src="{{ $u->foto_url }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--cinza-300)">
              <div class="td-nome">{{ $u->nome }}</div>
            </div>
          </td>
          <td>{{ $u->email }}</td>
          <td class="mono" style="font-size:12.5px">{{ $u->cpf }}</td>
          <td>{{ $u->telefone1 }}</td>
          <td>{{ $u->cargo }}</td>
          <td><span class="badge {{ $u->ativo ? 'badge-ativo' : 'badge-inativo' }}">{{ $u->ativo ? 'Ativo' : 'Inativo' }}</span></td>
          <td style="text-align:right">
            <div class="d-flex gap-8" style="justify-content:flex-end">
              <a href="{{ route('usuarios.edit', $u) }}" class="btn-icon">✏️</a>
              @if($u->id !== auth()->id())
              <form method="POST" action="{{ route('usuarios.toggle-ativo', $u) }}"
                    data-confirm="Alterar status deste usuário?" data-confirm-icone="⚠️">
                @csrf @method('PATCH')
                <button type="submit" class="btn-icon">{{ $u->ativo ? '🔴' : '🟢' }}</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; padding:48px; color:var(--cinza-400)">Nenhum usuário encontrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
{{ $usuarios->links('components.pagination') }}

@endsection
