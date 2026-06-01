@extends('layouts.app')
@section('title', 'Histórico de Ponto')
@section('breadcrumb') <span class="current">Histórico de Ponto</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">📋</span> Histórico de Ponto</h1>
    <p class="page-subtitle">
      Registros de entrada e saída por data
      @if($evento_id && $eventosLista->firstWhere('id', $evento_id))
        <span style="display:inline-flex;align-items:center;gap:5px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600;margin-left:8px">
          {{ $eventosLista->firstWhere('id', $evento_id)->nome }}
        </span>
      @endif
    </p>
  </div>
  <a href="{{ route('ponto.registrar') }}" class="btn btn-primary">⏱ Bater Ponto</a>
</div>

{{-- Resumo do dia (filtrado pelo evento ativo) --}}
<div class="indicadores-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:20px">
  <div class="indicador-card verde">
    <span class="indicador-icone">🟢</span>
    <div class="indicador-valor">{{ $resumo['presentes'] }}</div>
    <div class="indicador-label">Presentes</div>
  </div>
  <div class="indicador-card azul">
    <span class="indicador-icone">✅</span>
    <div class="indicador-valor">{{ $resumo['finalizados'] }}</div>
    <div class="indicador-label">Finalizados</div>
  </div>
  <div class="indicador-card cinza">
    <span class="indicador-icone">👥</span>
    <div class="indicador-valor">{{ $resumo['total'] }}</div>
    <div class="indicador-label">Total do Dia</div>
  </div>
</div>

{{-- Filtros --}}
<form method="GET" class="filtros-bar">
  <input type="date" name="data" class="form-control" style="width:170px"
         value="{{ $data }}" onchange="this.form.submit()">

  <div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" name="busca" class="form-control" id="campo-busca-global"
           placeholder="Buscar funcionário ou pulseira..." value="{{ $busca }}">
  </div>

  <select name="evento_id" class="form-control form-select" style="width:190px" onchange="this.form.submit()">
    <option value="">Todos os eventos</option>
    @foreach($eventosLista as $ev)
      <option value="{{ $ev->id }}" {{ (string)$evento_id === (string)$ev->id ? 'selected' : '' }}>
        {{ $ev->nome }}
      </option>
    @endforeach
  </select>

  <select name="empresa_id" class="form-control form-select" style="width:180px" onchange="this.form.submit()">
    <option value="">Todas as empresas</option>
    @foreach($empresas as $emp)
      <option value="{{ $emp->id }}" {{ $empresa_id == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}</option>
    @endforeach
  </select>

  <select name="status" class="form-control form-select" style="width:140px" onchange="this.form.submit()">
    <option value="">Todos os status</option>
    <option value="presente"   {{ $status === 'presente' ? 'selected' : '' }}>Presentes</option>
    <option value="finalizado" {{ $status === 'finalizado' ? 'selected' : '' }}>Finalizados</option>
    <option value="ausente"    {{ $status === 'ausente' ? 'selected' : '' }}>Ausentes</option>
  </select>
</form>

<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Funcionário</th>
          <th>Empresa</th>
          <th>Função</th>
          <th>Coord.</th>
          <th>Data</th>
          <th>Pulseira</th>
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
          <td>
            <div class="d-flex align-center gap-8">
              <img class="td-avatar" src="{{ $ponto->funcionario->foto_url }}" alt="">
              <a href="{{ route('funcionarios.show', $ponto->funcionario) }}"
                 style="font-weight:600; color:var(--cinza-800)">{{ $ponto->funcionario->nome }}</a>
            </div>
          </td>
          <td style="font-size:13px">{{ $ponto->empresa?->nome ?? 'Sem empresa' }}</td>
          <td style="font-size:13px; color:var(--cinza-600)">{{ $ponto->funcionario->funcao_cargo }}</td>
          <td>
            @if($ponto->funcionario->coordenador)
              <span style="background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap">⭐ Sim</span>
            @else
              <span style="background:#fff7ed; color:#c2410c; border:1px solid #fdba74; padding:2px 10px; border-radius:20px; font-size:11.5px; font-weight:600; white-space:nowrap">Não</span>
            @endif
          </td>
          <td class="mono td-data" style="font-size:12.5px">{{ $ponto->data?->format('d/m/Y') ?? '—' }}</td>
          <td class="mono" style="font-size:12.5px; font-weight:700; color:var(--azul-primario); letter-spacing:1px">
            {{ $ponto->pulseira ?? '—' }}
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
          <td colspan="11" style="text-align:center; padding:48px; color:var(--cinza-400)">
            <div style="font-size:36px; margin-bottom:8px">⏱</div>
            Nenhum registro encontrado para o filtro selecionado.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    Exibindo {{ $pontos->firstItem() }}–{{ $pontos->lastItem() }} de {{ $pontos->total() }}
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

// Máscara HH:MM
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

  if (!data)                              { mostrarErroModal('Informe a data.'); return; }
  if (!/^\d{2}:\d{2}$/.test(entrada))    { mostrarErroModal('Entrada inválida. Use HH:MM.'); return; }
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
