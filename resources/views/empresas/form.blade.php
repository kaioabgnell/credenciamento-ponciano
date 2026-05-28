@extends('layouts.app')
@section('title', $modo === 'criar' ? 'Nova Empresa' : 'Editar Empresa')
@section('breadcrumb')
  <a href="{{ route('empresas.index') }}">Empresas</a>
  <span class="sep">/</span>
  <span class="current">{{ $modo === 'criar' ? 'Nova Empresa' : $empresa->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <h1 class="page-title">
    <span class="page-icon">🏢</span>
    {{ $modo === 'criar' ? 'Nova Empresa' : 'Editar: ' . $empresa->nome }}
  </h1>
  <a href="{{ route('empresas.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

@if($errors->any())
<div class="alert alert-error">
  ⚠ Corrija os erros abaixo antes de salvar.
</div>
@endif

<form method="POST"
      action="{{ $modo === 'criar' ? route('empresas.store') : route('empresas.update', $empresa) }}">
  @csrf
  @if($modo === 'editar') @method('PUT') @endif

  <div class="card">
    <div class="card-header">
      <div class="card-title">Dados da Empresa</div>
    </div>

    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label" for="nome">Nome da Empresa <span class="obrigatorio">*</span></label>
        <input type="text" id="nome" name="nome"
               class="form-control {{ $errors->has('nome') ? 'is-invalid' : '' }}"
               value="{{ old('nome', $empresa->nome) }}"
               placeholder="Ex: Ponciano Soluções" autofocus>
        @error('nome') <div class="form-error">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="responsavel">Responsável</label>
        <input type="text" id="responsavel" name="responsavel"
               class="form-control"
               value="{{ old('responsavel', $empresa->responsavel) }}"
               placeholder="Nome do responsável">
      </div>

      <div class="form-group">
        <label class="form-label" for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone"
               class="form-control" data-mask="tel"
               value="{{ old('telefone', $empresa->telefone) }}"
               placeholder="(00) 00000-0000">
      </div>

      <div class="form-group">
        <label class="form-label" for="email">E-mail</label>
        <input type="email" id="email" name="email"
               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
               value="{{ old('email', $empresa->email) }}"
               placeholder="contato@empresa.com.br">
        @error('email') <div class="form-error">{{ $message }}</div> @enderror
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="observacoes">Observações</label>
      <textarea id="observacoes" name="observacoes"
                class="form-control" rows="3"
                placeholder="Informações adicionais...">{{ old('observacoes', $empresa->observacoes) }}</textarea>
    </div>
  </div>

  <div class="d-flex gap-12" style="margin-top:20px; justify-content:flex-end">
    <a href="{{ route('empresas.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      {{ $modo === 'criar' ? '+ Cadastrar Empresa' : '✓ Salvar Alterações' }}
    </button>
  </div>

</form>

@endsection
