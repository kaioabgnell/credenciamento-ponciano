@extends('layouts.app')
@section('title', 'Histórico — ' . $funcionario->nome)
@section('breadcrumb')
  <a href="{{ route('ponto.index') }}">Histórico de Ponto</a>
  <span class="sep">/</span>
  <span class="current">{{ $funcionario->nome }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="d-flex align-center gap-16">
    <img src="{{ $funcionario->foto_url }}" alt="{{ $funcionario->nome }}"
         style="width:52px; height:52px; border-radius:50%; object-fit:cover; border:2px solid var(--cinza-200)">
    <div>
      <h1 class="page-title" style="margin:0">{{ $funcionario->nome }}</h1>
      <p class="page-subtitle" style="margin:0">
        {{ $funcionario->funcao_cargo }} ·
        @if($funcionario->empresa)
          <a href="{{ route('empresas.show', $funcionario->empresa) }}" style="color:var(--azul-primario)">
            {{ $funcionario->empresa->nome }}
          </a>
        @else
          <span style="color:var(--cinza-400)">Sem empresa</span>
        @endif
        · <span class="badge {{ $funcionario->ativo ? 'badge-ativo' : 'badge-inativo' }}">
            {{ $funcionario->ativo ? 'Ativo' : 'Inativo' }}
          </span>
      </p>
    </div>
  </div>
  <div class="d-flex gap-8">
    <a href="{{ route('funcionarios.show', $funcionario) }}" class="btn btn-secondary">👤 Ver Funcionário</a>
    <a href="{{ route('ponto.index') }}" class="btn btn-secondary">← Voltar</a>
  </div>
</div>

{{-- Totalizadores --}}
@php
  $totalRegistros = $pontos->total();
  $totalHoras = $funcionario->pontos()
    ->whereNotNull('horas_trabalhadas')
    ->pluck('horas_trabalhadas')
    ->reduce(function ($carry, $h) {
        [$hh, $mm, $ss] = explode(':', $h . ':00');
        return $carry + ($hh * 3600) + ($mm * 60) + $ss;
    }, 0);
  $horasFormatado = sprintf('%02d:%02d', intdiv($totalHoras, 3600), intdiv($totalHoras % 3600, 60));
@endphp

<div class="indicadores-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:20px">
  <div class="indicador-card azul">
    <span class="indicador-icone">📋</span>
    <div class="indicador-valor">{{ $totalRegistros }}</div>
    <div class="indicador-label">Total de Registros</div>
  </div>
  <div class="indicador-card verde">
    <span class="indicador-icone">⏳</span>
    <div class="indicador-valor" style="font-size:22px">{{ $horasFormatado }}</div>
    <div class="indicador-label">Horas Totais (acumulado)</div>
  </div>
  <div class="indicador-card cinza">
    <span class="indicador-icone">📅</span>
    <div class="indicador-valor">{{ $pontos->count() }}</div>
    <div class="indicador-label">Registros nesta página</div>
  </div>
</div>

{{-- Tabela --}}
<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Evento</th>
          <th>Entrada</th>
          <th>Saída</th>
          <th>Horas</th>
          <th>Status</th>
          <th style="width:90px; text-align:center">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pontos as $ponto)
        <tr id="row-ponto-{{ $ponto->id }}">
          <td class="mono td-data" style="font-weight:600">{{ $ponto->data?->format('d/m/Y') ?? '—' }}</td>
          <td>
            @if($ponto->evento)
              <span style="display:inline-flex;align-items:center;gap:4px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600;white-space:nowrap">
                {{ $ponto->evento->nome }}
              </span>
            @else
              <span style="color:var(--cinza-400); font-size:12px">—</span>
            @endif
          </td>
          <td class="mono td-entrada" style="color:var(--verde); font-weight:600">
            {{ $ponto->entrada ? substr($ponto->entrada, 0, 5) : '—' }}
          </td>
          <td class="mono td-saida" style="color:var(--vermelho); font-weight:600">
            {{ $ponto->saida ? substr($ponto->saida, 0, 5) : '—' }}
          </td>
          <td class="mono td-horas" style="font-weight:700; color:var(--azul-primario)">
            {{ $ponto->horas_trabalhadas ? substr($ponto->horas_trabalhadas, 0, 5) : '—' }}
          </td>
          <td class="td-status">{!! $ponto->status_badge !!}</td>
          <td style="text-align:center; white-space:nowrap">
            <button class="btn-icon" title="Editar"
                    onclick="abrirModalEditar({{ $ponto->id }}, '{{ $ponto->data?->format('Y-m-d') ?? '' }}', '{{ substr($ponto->entrada ?? '', 0, 5) }}', '{{ substr($ponto->saida ?? '', 0, 5) }}')"
                    style="width:30px; height:30px; margin-right:4px">
              ✏️
            </button>
            <button class="btn-icon" title="Excluir"
                    onclick="excluirPonto({{ $ponto->id }})"
                    style="width:30px; height:30px; background:var(--vermelho-light); border-color:var(--vermelho); color:var(--vermelho)">
              🗑
            </button>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center; padding:48px; color:var(--cinza-400)">
            <div style="font-size:36px; margin-bottom:8px">⏱</div>
            Nenhum registro de ponto encontrado.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    Exibindo {{ $pontos->firstItem() ?? 0 }}–{{ $pontos->lastItem() ?? 0 }} de {{ $pontos->total() }}
  </div>
  {{ $pontos->links('components.pagination') }}
</div>

{{-- ===== MODAL EDITAR PONTO ===== --}}
<div class="modal-overlay" id="modal-editar-ponto">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-icon">✏️</div>
    <div style="font-size:16px; font-weight:700; color:var(--cinza-800); margin-bottom:20px">Editar Registro de Ponto</div>

    <div style="display:flex; flex-direction:column; gap:14px; text-align:left">
      <div class="form-group" style="margin:0">
        <label class="form-label">Data</label>
        <input type="date" id="edit-data" class="form-control">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">🟢 Entrada</label>
          <input type="text" id="edit-entrada" class="form-control"
                 placeholder="HH:MM" maxlength="5"
                 style="font-size:17px; font-weight:600; text-align:center; letter-spacing:2px">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">🔴 Saída <span style="font-weight:400; color:var(--cinza-400)">(opcional)</span></label>
          <input type="text" id="edit-saida" class="form-control"
                 placeholder="HH:MM" maxlength="5"
                 style="font-size:17px; font-weight:600; text-align:center; letter-spacing:2px">
        </div>
      </div>
      <div id="erro-modal-editar" style="display:none; font-size:12px; color:var(--vermelho); font-weight:500; padding:8px; background:var(--vermelho-light); border-radius:6px"></div>
    </div>

    <div class="modal-actions" style="margin-top:20px">
      <button class="btn btn-secondary" onclick="fecharModalEditar()">Cancelar</button>
      <button class="btn btn-primary" id="btn-salvar-edicao" onclick="salvarEdicaoPonto()">✓ Salvar</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
let pontoEditandoId = null;

$(document).on('input', '#edit-entrada, #edit-saida', function () {
  let v = $(this).val().replace(/\D/g, '').substring(0, 4);
  if (v.length > 2) v = v.substring(0, 2) + ':' + v.substring(2);
  $(this).val(v);
  $('#erro-modal-editar').hide();
});

window.abrirModalEditar = function (id, data, entrada, saida) {
  pontoEditandoId = id;
  $('#edit-data').val(data);
  $('#edit-entrada').val(entrada);
  $('#edit-saida').val(saida || '');
  $('#erro-modal-editar').hide();
  $('#modal-editar-ponto').addClass('show');
};

window.fecharModalEditar = function () {
  $('#modal-editar-ponto').removeClass('show');
  pontoEditandoId = null;
};

$('#modal-editar-ponto').on('click', function (e) {
  if ($(e.target).is('#modal-editar-ponto')) fecharModalEditar();
});

window.salvarEdicaoPonto = function () {
  const data    = $('#edit-data').val();
  const entrada = $('#edit-entrada').val().trim();
  const saida   = $('#edit-saida').val().trim();

  if (!data)                                  { mostrarErroModal('Informe a data.'); return; }
  if (!/^\d{2}:\d{2}$/.test(entrada))        { mostrarErroModal('Entrada inválida. Use HH:MM.'); return; }
  if (saida && !/^\d{2}:\d{2}$/.test(saida)) { mostrarErroModal('Saída inválida. Use HH:MM.'); return; }

  const btn = $('#btn-salvar-edicao').prop('disabled', true).text('Salvando...');

  $.ajax({
    url: `/api/ponto/${pontoEditandoId}`,
    method: 'PUT',
    data: { data, entrada, saida: saida || null },
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
  })
  .done(function (res) {
    const p   = res.ponto;
    const row = $(`#row-ponto-${pontoEditandoId}`);
    row.find('.td-data').text(p.data);
    row.find('.td-entrada').text(p.entrada || '—');
    row.find('.td-saida').text(p.saida || '—');
    row.find('.td-horas').text(p.horas_trabalhadas || '—');
    row.find('.td-status').html(p.status_badge);
    row.find('[title="Editar"]').attr('onclick',
      `abrirModalEditar(${p.id}, '${data}', '${p.entrada || ''}', '${p.saida || ''}')`
    );
    fecharModalEditar();
    showToast('Ponto atualizado com sucesso!', 'success');
  })
  .fail(function (xhr) {
    const erros = xhr.responseJSON?.errors;
    const msg   = erros
      ? Object.values(erros).flat().join(' ')
      : (xhr.responseJSON?.message || 'Erro ao salvar.');
    mostrarErroModal(msg);
  })
  .always(() => btn.prop('disabled', false).text('✓ Salvar'));
};

function mostrarErroModal(msg) {
  $('#erro-modal-editar').text(msg).show();
}

window.excluirPonto = function (id) {
  confirmar({
    titulo:   'Excluir Registro',
    mensagem: 'Deseja excluir este registro de ponto? Esta ação não pode ser desfeita.',
    icone:    '🗑',
    btnLabel: 'Excluir',
    tipo:     'danger',
    onConfirm: function () {
      $.ajax({
        url: `/api/ponto/${id}`,
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      })
      .done(function () {
        $(`#row-ponto-${id}`).fadeOut(300, function () { $(this).remove(); });
        showToast('Registro excluído com sucesso!', 'success');
      })
      .fail(() => showToast('Erro ao excluir o registro.', 'error'));
    }
  });
};
</script>
@endpush
