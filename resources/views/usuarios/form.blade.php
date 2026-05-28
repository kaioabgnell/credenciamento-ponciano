@extends('layouts.app')
@section('title', $modo === 'criar' ? 'Novo Usuário' : 'Editar Usuário')
@section('breadcrumb')
  <a href="{{ route('usuarios.index') }}">Usuários</a>
  <span class="sep">/</span>
  <span class="current">{{ $modo === 'criar' ? 'Novo' : $usuario->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <h1 class="page-title"><span class="page-icon">🔑</span>
    {{ $modo === 'criar' ? 'Novo Usuário' : 'Editar: '.$usuario->nome }}
  </h1>
  <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

@if($errors->any())
<div class="alert alert-error">⚠ Corrija os erros antes de salvar.</div>
@endif

<form method="POST" enctype="multipart/form-data"
      action="{{ $modo === 'criar' ? route('usuarios.store') : route('usuarios.update', $usuario) }}">
  @csrf
  @if($modo === 'editar') @method('PUT') @endif

  <div style="display:grid; grid-template-columns:220px 1fr; gap:20px; align-items:start">

    <div class="card">
      <div class="card-title" style="font-size:13px; margin-bottom:14px">Foto</div>
      <div class="foto-upload-box" onclick="$('#input-foto').click()">
        <img id="foto-preview"
             src="{{ $modo==='editar' && $usuario->foto ? $usuario->foto_url : asset('images/avatar-default.svg') }}"
             class="foto-preview">
        <div class="foto-upload-text"><strong>Clique ou arraste</strong><br>JPG, PNG — máx. 2MB</div>
        <input type="file" id="input-foto" name="foto" class="input-foto" accept="image/*"
               data-preview="#foto-preview" style="display:none">
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Dados do Usuário</div></div>
      <div class="form-grid-2">

        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Nome Completo <span class="obrigatorio">*</span></label>
          <input type="text" name="nome" class="form-control" value="{{ old('nome',$usuario->nome) }}" autofocus>
          @error('nome') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">CPF <span class="obrigatorio">*</span></label>
          <input type="text" name="cpf" class="form-control" data-mask="cpf"
                 value="{{ old('cpf',$usuario->cpf) }}" placeholder="000.000.000-00">
          @error('cpf') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">E-mail <span class="obrigatorio">*</span></label>
          <input type="email" name="email" class="form-control"
                 value="{{ old('email',$usuario->email) }}" placeholder="email@ponciano.com.br">
          @error('email') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Data de Nascimento <span class="obrigatorio">*</span></label>
          <input type="date" name="data_nascimento" class="form-control"
                 value="{{ old('data_nascimento', $usuario->data_nascimento?->format('Y-m-d')) }}">
          @error('data_nascimento') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Cargo <span class="obrigatorio">*</span></label>
          <input type="text" name="cargo" class="form-control"
                 value="{{ old('cargo',$usuario->cargo) }}" placeholder="Ex: Coordenador, Operador">
          @error('cargo') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Telefone 1 <span class="obrigatorio">*</span></label>
          <input type="text" name="telefone1" class="form-control" data-mask="tel"
                 value="{{ old('telefone1',$usuario->telefone1) }}" placeholder="(00) 00000-0000">
          @error('telefone1') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Telefone 2</label>
          <input type="text" name="telefone2" class="form-control" data-mask="tel"
                 value="{{ old('telefone2',$usuario->telefone2) }}" placeholder="(00) 00000-0000">
        </div>

        <div class="form-group">
          <label class="form-label">Senha {{ $modo === 'editar' ? '(deixe em branco para não alterar)' : '' }} <span class="obrigatorio">{{ $modo==='criar' ? '*' : '' }}</span></label>
          <input type="password" name="senha" class="form-control" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
          @error('senha') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Confirmar Senha</label>
          <input type="password" name="senha_confirmation" class="form-control" placeholder="Repita a senha">
        </div>

      </div>
    </div>

  </div>

  <div class="d-flex gap-12 mt-16" style="justify-content:flex-end">
    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      {{ $modo === 'criar' ? '+ Criar Usuário' : '✓ Salvar Alterações' }}
    </button>
  </div>

</form>

@endsection
