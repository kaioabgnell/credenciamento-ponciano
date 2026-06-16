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

@php
  $telEmpDigitos = preg_replace('/\D/', '', old('telefone', $empresa->telefone ?? ''));
@endphp

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
        <input type="text" id="campo-telefone-empresa" name="telefone"
               class="form-control" data-mask="tel"
               data-prefill="{{ $telEmpDigitos }}"
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

  @php
    $eventosSelecionados = old('evento_ids', $empresa->exists ? $empresa->eventos->pluck('id')->toArray() : []);
  @endphp

  <div class="card" style="margin-top:20px">
    <div class="card-header">
      <div class="card-title">Eventos Vinculados</div>
    </div>

    @if($eventos->isEmpty())
      <div style="text-align:center; padding:24px; color:var(--cinza-400); font-size:13px">
        Nenhum evento ativo encontrado.
      </div>
    @else
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px">
        @foreach($eventos as $evento)
          @php $checked = in_array($evento->id, array_map('intval', (array) $eventosSelecionados)); @endphp
          <label style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:2px solid {{ $checked ? 'var(--azul-primario)' : 'var(--cinza-200)' }}; border-radius:8px; cursor:pointer; background:{{ $checked ? '#eff6ff' : 'var(--cinza-50, #fafafa)' }}; transition:border-color .15s, background .15s" class="evento-checkbox-card">
            <input type="checkbox" name="evento_ids[]" value="{{ $evento->id }}"
                   {{ $checked ? 'checked' : '' }}
                   style="margin-top:3px; width:16px; height:16px; accent-color:var(--azul-primario); flex-shrink:0">
            <div style="min-width:0">
              <div style="font-weight:600; font-size:14px; color:var(--cinza-800); line-height:1.3">{{ $evento->nome }}</div>
              <div style="font-size:12px; color:var(--cinza-500); margin-top:3px">{{ $evento->periodo_formatado }}</div>
              <div style="margin-top:5px">{!! $evento->status_badge !!}</div>
            </div>
          </label>
        @endforeach
      </div>
    @endif
  </div>

  <div class="d-flex gap-12" style="margin-top:20px; justify-content:flex-end">
    <a href="{{ route('empresas.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      {{ $modo === 'criar' ? '+ Cadastrar Empresa' : '✓ Salvar Alterações' }}
    </button>
  </div>

</form>

@endsection

@push('scripts')
<script>
$(function () {
  const telDigits = String($('#campo-telefone-empresa').data('prefill') || '');
  if (telDigits.length >= 10) {
    $('#campo-telefone-empresa').unmask().val(telDigits).mask('(00) 00000-0000');
  }

  $(document).on('change', '.evento-checkbox-card input[type=checkbox]', function () {
    const $card = $(this).closest('.evento-checkbox-card');
    if (this.checked) {
      $card.css({ 'border-color': 'var(--azul-primario)', 'background': '#eff6ff' });
    } else {
      $card.css({ 'border-color': 'var(--cinza-200)', 'background': 'var(--cinza-50, #fafafa)' });
    }
  });
});
</script>
@endpush
