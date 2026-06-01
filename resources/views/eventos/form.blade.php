@extends('layouts.app')
@section('title', $modo === 'criar' ? 'Novo Evento' : 'Editar Evento')
@section('breadcrumb')
  <a href="{{ route('eventos.index') }}">Eventos</a>
  <span class="sep">/</span>
  <span class="current">{{ $modo === 'criar' ? 'Novo Evento' : $evento->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <h1 class="page-title">
    {{ $modo === 'criar' ? 'Novo Evento' : 'Editar: ' . $evento->nome }}
  </h1>
  <a href="{{ route('eventos.index') }}" class="btn btn-secondary">← Voltar</a>
</div>

@if($errors->any())
<div class="alert alert-error" style="background:var(--vermelho-light); border:1px solid var(--vermelho); color:var(--vermelho); padding:12px 16px; border-radius:var(--border-radius-sm); margin-bottom:20px; font-size:13px; font-weight:500">
  ⚠ Corrija os erros abaixo antes de salvar.
</div>
@endif

@php
  $telOrgDigitos = preg_replace('/\D/', '', old('telefone_organizador', $evento->telefone_organizador ?? ''));
@endphp

<form method="POST"
      action="{{ $modo === 'criar' ? route('eventos.store') : route('eventos.update', $evento) }}">
  @csrf
  @if($modo === 'editar') @method('PUT') @endif

  <div class="card">
    <div class="card-header">
      <div class="card-title">Dados do Evento</div>
    </div>

    {{-- Nome do Evento --}}
    <div class="form-group">
      <label class="form-label" for="nome">Nome do Evento <span style="color:var(--vermelho)">*</span></label>
      <input type="text" id="nome" name="nome"
             class="form-control {{ $errors->has('nome') ? 'is-invalid' : '' }}"
             value="{{ old('nome', $evento->nome) }}"
             placeholder="Ex: TOTUS TUUS 2026" autofocus>
      @error('nome')
        <div style="font-size:12px; color:var(--vermelho); margin-top:4px; font-weight:500">{{ $message }}</div>
      @enderror
    </div>

    {{-- Datas --}}
    <div class="form-grid-2">
      <div class="form-group">
        <label class="form-label" for="data_inicio">Data de Início <span style="color:var(--vermelho)">*</span></label>
        <input type="date" id="data_inicio" name="data_inicio"
               class="form-control {{ $errors->has('data_inicio') ? 'is-invalid' : '' }}"
               value="{{ old('data_inicio', $evento->data_inicio?->format('Y-m-d')) }}">
        @error('data_inicio')
          <div style="font-size:12px; color:var(--vermelho); margin-top:4px; font-weight:500">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="data_fim">Data de Término <span style="color:var(--vermelho)">*</span></label>
        <input type="date" id="data_fim" name="data_fim"
               class="form-control {{ $errors->has('data_fim') ? 'is-invalid' : '' }}"
               value="{{ old('data_fim', $evento->data_fim?->format('Y-m-d')) }}">
        @error('data_fim')
          <div style="font-size:12px; color:var(--vermelho); margin-top:4px; font-weight:500">{{ $message }}</div>
        @enderror
      </div>
    </div>

    {{-- Organizador --}}
    <div class="card-header" style="margin-top:8px; margin-bottom:0">
      <div class="card-title" style="font-size:14px">Organizador</div>
    </div>

    <div class="form-grid-2" style="margin-top:16px">
      <div class="form-group">
        <label class="form-label" for="nome_organizador">Nome do Organizador</label>
        <input type="text" id="nome_organizador" name="nome_organizador"
               class="form-control"
               value="{{ old('nome_organizador', $evento->nome_organizador) }}"
               placeholder="Nome completo do responsável">
      </div>

      <div class="form-group">
        <label class="form-label" for="telefone_organizador">Telefone do Organizador</label>
        <input type="text" id="campo-tel-organizador" name="telefone_organizador"
               class="form-control" data-mask="tel"
               data-prefill="{{ $telOrgDigitos }}"
               placeholder="(00) 00000-0000">
      </div>
    </div>

  </div>

  {{-- Preview de duração --}}
  <div id="preview-duracao" style="display:none; margin-top:12px; padding:12px 16px; background:var(--azul-light); border:1px solid var(--azul-mid); border-radius:var(--border-radius-sm); font-size:13px; color:var(--azul-escuro)">
    <span id="preview-texto"></span>
  </div>

  <div class="d-flex gap-12" style="margin-top:20px; justify-content:flex-end">
    <a href="{{ route('eventos.index') }}" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      {{ $modo === 'criar' ? '+ Cadastrar Evento' : '✓ Salvar Alterações' }}
    </button>
  </div>

</form>

@endsection

@push('scripts')
<script>
// Pré-preenche telefone após inicialização do jQuery Mask
$(function () {
  const telDigits = String($('#campo-tel-organizador').data('prefill') || '');
  if (telDigits.length >= 10) {
    $('#campo-tel-organizador').unmask().val(telDigits).mask('(00) 00000-0000');
  }
});

// Preview de duração ao selecionar as datas
function atualizarPreview() {
  const ini = document.getElementById('data_inicio').value;
  const fim = document.getElementById('data_fim').value;
  const preview = document.getElementById('preview-duracao');
  const texto   = document.getElementById('preview-texto');

  if (!ini || !fim) { preview.style.display = 'none'; return; }

  const dIni = new Date(ini + 'T00:00:00');
  const dFim = new Date(fim + 'T00:00:00');

  if (dFim < dIni) {
    texto.textContent = '⚠ A data de término deve ser igual ou após a data de início.';
    preview.style.background = 'var(--vermelho-light)';
    preview.style.borderColor = 'var(--vermelho)';
    preview.style.color = 'var(--vermelho)';
  } else {
    const dias = Math.round((dFim - dIni) / 86400000) + 1;
    const fmtIni = dIni.toLocaleDateString('pt-BR');
    const fmtFim = dFim.toLocaleDateString('pt-BR');
    texto.textContent = `📅 Evento de ${fmtIni} a ${fmtFim} — ${dias} dia${dias > 1 ? 's' : ''} de duração.`;
    preview.style.background = 'var(--azul-light)';
    preview.style.borderColor = 'var(--azul-mid)';
    preview.style.color = 'var(--azul-escuro)';
  }
  preview.style.display = 'block';
}

document.getElementById('data_inicio').addEventListener('change', atualizarPreview);
document.getElementById('data_fim').addEventListener('change', atualizarPreview);

// Trigger na carga (modo editar)
atualizarPreview();
</script>
@endpush
