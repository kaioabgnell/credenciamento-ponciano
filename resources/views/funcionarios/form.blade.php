@extends('layouts.app')
@section('title', $modo === 'criar' ? 'Novo Funcionário' : 'Editar Funcionário')
@section('breadcrumb')
  <a href="{{ route('funcionarios.index') }}">Funcionários</a>
  <span class="sep">/</span>
  <span class="current">{{ $modo === 'criar' ? 'Novo' : $funcionario->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <h1 class="page-title">
    <span class="page-icon">👤</span>
    {{ $modo === 'criar' ? 'Novo Funcionário' : 'Editar: ' . $funcionario->nome }}
  </h1>
  <a href="{{ route('funcionarios.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

@if($errors->any())
<div class="alert alert-error">⚠ Corrija os erros antes de salvar.</div>
@endif

@php
  // Extrai apenas dígitos para que o jQuery Mask aplique a formatação sem conflito
  $cpfDigitos = preg_replace('/\D/', '', old('cpf', $funcionario->cpf ?? ''));
  $telDigitos = preg_replace('/\D/', '', old('telefone', $funcionario->telefone ?? ''));
@endphp

<form method="POST" enctype="multipart/form-data"
      action="{{ $modo === 'criar' ? route('funcionarios.store') : route('funcionarios.update', $funcionario) }}">
  @csrf
  @if($modo === 'editar') @method('PUT') @endif

  <div style="display:grid; grid-template-columns:220px 1fr; gap:20px; align-items:start">

    {{-- Foto --}}
    <div class="card" style="padding:20px">
      <div class="card-title" style="font-size:13px; margin-bottom:14px">Foto do Funcionário</div>
      <label class="foto-upload-box" for="input-foto" style="display:block">
        <img id="foto-preview"
             src="{{ $modo === 'editar' && $funcionario->foto ? $funcionario->foto_url : asset('images/avatar-default.svg') }}"
             class="foto-preview">
        <div class="foto-upload-text">
          <strong>Clique ou arraste</strong><br>
          JPG, PNG — máx. 2MB
        </div>
        <input type="file" id="input-foto" name="foto" class="input-foto" accept="image/*"
               data-preview="#foto-preview" style="position:absolute; opacity:0; width:0; height:0; overflow:hidden">
      </label>
    </div>

    {{-- Dados --}}
    <div class="card">
      <div class="card-header"><div class="card-title">Dados do Funcionário</div></div>

      <div class="form-grid-2">
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Nome Completo <span class="obrigatorio">*</span></label>
          <input type="text" name="nome" class="form-control {{ $errors->has('nome') ? 'is-invalid' : '' }}"
                 value="{{ old('nome', $funcionario->nome) }}" placeholder="Nome completo do funcionário" autofocus>
          @error('nome') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Empresa <span class="obrigatorio">*</span></label>
          <select name="empresa_id" id="select-empresa" class="form-control form-select {{ $errors->has('empresa_id') ? 'is-invalid' : '' }}">
            <option value="">Selecione a empresa...</option>
            @foreach($empresas as $emp)
              <option value="{{ $emp->id }}"
                {{ old('empresa_id', $funcionario->empresa_id ?? request('empresa_id')) == $emp->id ? 'selected' : '' }}>
                {{ $emp->nome }}
              </option>
            @endforeach
          </select>
          @error('empresa_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">CPF</label>
          {{-- value vazio: jQuery Mask apaga qualquer pré-preenchimento durante init;
               o valor real é inserido via JS depois que a máscara está pronta --}}
          <input type="text" name="cpf" id="campo-cpf" class="form-control" data-mask="cpf"
                 data-prefill="{{ $cpfDigitos }}"
                 placeholder="000.000.000-00">
          <div class="form-hint" id="cpf-hint"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Função / Cargo <span class="obrigatorio">*</span></label>
          <input type="text" name="funcao_cargo" class="form-control {{ $errors->has('funcao_cargo') ? 'is-invalid' : '' }}"
                 value="{{ old('funcao_cargo', $funcionario->funcao_cargo) }}" placeholder="Ex: Montador, Produtor de Palco">
          @error('funcao_cargo') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Telefone</label>
          <input type="text" id="campo-telefone" name="telefone" class="form-control" data-mask="tel"
                 data-prefill="{{ $telDigitos }}"
                 placeholder="(00) 00000-0000">
        </div>

        <div class="form-group">
          <label class="form-label">Área de Acesso <span class="obrigatorio">*</span></label>
          <select name="area_acesso" class="form-control form-select {{ $errors->has('area_acesso') ? 'is-invalid' : '' }}">
            @foreach(['TODOS', 'PALCO', 'BACKSTAGE', 'VIP', 'CAMARINS', 'PRODUÇÃO', 'TÉCNICA', 'CREDENCIAMENTO', 'OPERAÇÕES'] as $area)
              <option value="{{ $area }}" {{ old('area_acesso', $funcionario->area_acesso) === $area ? 'selected' : '' }}>{{ $area }}</option>
            @endforeach
          </select>
          @error('area_acesso') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Coordenador?</label>
          <label class="toggle-switch">
            <input type="checkbox" name="coordenador" value="1"
                   {{ old('coordenador', $funcionario->coordenador) ? 'checked' : '' }}>
            <div class="toggle-track"><div class="toggle-thumb"></div></div>
            <span style="font-size:13px; color:var(--cinza-600)">Este funcionário é coordenador</span>
          </label>
        </div>
      </div>
    </div>

  </div>

  <div class="d-flex gap-12" style="margin-top:20px; justify-content:flex-end">
    <a href="{{ route('funcionarios.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      {{ $modo === 'criar' ? '+ Cadastrar Funcionário' : '✓ Salvar Alterações' }}
    </button>
  </div>

</form>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
  /* Integração Select2 com o design do sistema */
  .select2-container--default .select2-selection--single {
    height: 40px;
    border: 1.5px solid var(--cinza-300);
    border-radius: var(--border-radius-sm);
    background: #fff;
    display: flex;
    align-items: center;
    font-family: var(--font-body);
    font-size: 14px;
    color: var(--cinza-800);
    transition: border-color .2s;
  }
  .select2-container--default .select2-selection--single:hover {
    border-color: var(--azul-primario);
  }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--azul-primario);
    box-shadow: 0 0 0 3px rgba(2,143,208,.12);
    outline: none;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--cinza-800);
    font-size: 14px;
    line-height: 38px;
    padding-left: 12px;
    padding-right: 36px;
  }
  .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: var(--cinza-400);
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px;
    right: 8px;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: var(--cinza-500) transparent transparent;
  }
  .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent var(--azul-primario);
  }
  .select2-dropdown {
    border: 1.5px solid var(--azul-primario);
    border-radius: var(--border-radius-sm);
    box-shadow: var(--shadow-md);
    font-family: var(--font-body);
    font-size: 14px;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1.5px solid var(--cinza-300);
    border-radius: var(--border-radius-sm);
    padding: 7px 10px;
    font-size: 13px;
    font-family: var(--font-body);
    color: var(--cinza-800);
    outline: none;
    transition: border-color .2s;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: var(--azul-primario);
  }
  .select2-container--default .select2-results__option {
    padding: 9px 12px;
    font-size: 13.5px;
    color: var(--cinza-700);
  }
  .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: var(--azul-light);
    color: var(--azul-escuro);
  }
  .select2-container--default .select2-results__option[aria-selected=true] {
    background: var(--cinza-100);
    font-weight: 600;
    color: var(--azul-primario);
  }
  .select2-container--default .select2-results__option--selected {
    background: var(--azul-light);
  }
  .select2-search--dropdown { padding: 8px; }
  /* Erro de validação */
  .is-invalid + .select2-container--default .select2-selection--single {
    border-color: var(--vermelho);
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// ── Pré-preenche campos com máscara ──────────────────────────────
// jQuery Mask 1.14 apaga o value durante a inicialização em app.js.
// Solução: unmask → seta dígitos → re-aplica mask (que formata no momento do .mask()).
$(function () {
  const cpfDigits = String($('#campo-cpf').data('prefill') || '');
  if (cpfDigits.length === 11) {
    $('#campo-cpf').unmask().val(cpfDigits).mask('000.000.000-00', { reverse: false });
  }

  const telDigits = String($('#campo-telefone').data('prefill') || '');
  if (telDigits.length >= 10) {
    $('#campo-telefone').unmask().val(telDigits).mask('(00) 00000-0000');
  }
});

// Inicializar Select2 na empresa
$('#select-empresa').select2({
  placeholder: 'Selecione a empresa...',
  allowClear: true,
  language: {
    noResults:    () => 'Nenhuma empresa encontrada',
    searching:    () => 'Buscando...',
    removeAllItems: () => 'Limpar seleção',
  },
  width: '100%',
});

// Verificar CPF duplicado
let cpfTimer;
$('#campo-cpf').on('input', function () {
  clearTimeout(cpfTimer);
  const cpf = $(this).val().replace(/\D/g, '');
  const id  = '{{ $funcionario->id ?? "" }}';
  if (cpf.length < 11) { $('#cpf-hint').text('').css('color', ''); return; }
  cpfTimer = setTimeout(() => {
    $.get('/api/funcionarios/verificar-cpf', { cpf, id }, function (res) {
      if (res.existe) {
        $('#cpf-hint').text('⚠ CPF já cadastrado no sistema.').css('color', 'var(--vermelho)');
        $('#campo-cpf').addClass('is-invalid');
      } else {
        $('#cpf-hint').text('✓ CPF disponível.').css('color', 'var(--verde)');
        $('#campo-cpf').removeClass('is-invalid');
      }
    });
  }, 400);
});
</script>
@endpush
