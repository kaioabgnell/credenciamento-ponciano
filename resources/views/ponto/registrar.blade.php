@extends('layouts.app')
@section('title', 'Bater Ponto')
@section('pagina', 'ponto-registrar')
@section('breadcrumb') <span class="current">Bater Ponto</span> @endsection

@push('styles')
<style>
  /* Tag de evento */
  .evento-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--roxo-light, #ede9fe);
    color: var(--roxo);
    border: 1px solid var(--roxo);
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
  }
  .evento-tag .ev-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--roxo);
    flex-shrink: 0;
    animation: pulse-roxo 1.5s infinite;
  }
  @keyframes pulse-roxo {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: .5; transform: scale(1.4); }
  }

  /* Banner de nenhum evento ativo */
  .banner-sem-evento {
    background: var(--amarelo-light);
    border: 1px solid var(--amarelo);
    border-radius: var(--border-radius-sm);
    padding: 10px 14px;
    font-size: 12px;
    color: #92610a;
    font-weight: 500;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
</style>
@endpush

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">⏱</span> Controle de Ponto</h1>
    <p class="page-subtitle">Registro de entrada e saída em tempo real</p>
  </div>
  <a href="{{ route('ponto.index') }}" class="btn btn-secondary">📋 Histórico</a>
</div>

<div class="ponto-registrar-grid" style="display:grid; grid-template-columns:1fr 420px; gap:24px; align-items:start">

  {{-- PAINEL PRINCIPAL --}}
  <div>

    {{-- Relógio --}}
    <div class="card mb-16" style="text-align:center; padding:28px">
      <div class="relogio-live"></div>
      <div class="relogio-data"></div>
    </div>

    {{-- Banner: evento ativo da sessão --}}
    @if($eventoAtivo)
      <div style="margin-bottom:16px; display:flex; align-items:center; gap:10px">
        <span style="font-size:12px; color:var(--cinza-500); font-weight:600">EVENTO ATIVO:</span>
        <span class="evento-tag">
          <span class="ev-dot"></span>
          {{ $eventoAtivo->nome }}
        </span>
      </div>
    @else
      <div class="banner-sem-evento">
        ⚠ Nenhum evento ativo selecionado. Selecione um evento no topo da página para vincular o ponto.
      </div>
    @endif

    {{-- Busca + Botões --}}
    <div class="card">
      <div class="card-title" style="margin-bottom:18px">🔍 Registrar Ponto</div>

      <div class="search-ponto-wrap" style="position:relative; margin-bottom:20px">
        <div class="d-flex gap-10">
          <div style="position:relative; flex:1">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:var(--cinza-400); pointer-events:none">👤</span>
            <input type="text" id="campo-busca-funcionario"
                   class="form-control" style="padding-left:42px; font-size:15px; padding-top:12px; padding-bottom:12px"
                   placeholder="Digite nome ou CPF do funcionário..."
                   autocomplete="off">
          </div>
        </div>
        <div id="autocomplete-resultado" class="autocomplete-dropdown" style="display:none"></div>
      </div>

      {{-- Card do funcionário selecionado --}}
      <div id="card-funcionario" class="hidden">
        <div style="background:var(--azul-light); border:1.5px solid var(--azul-mid); border-radius:10px; padding:20px; margin-bottom:20px">
          <div class="d-flex align-center gap-16">
            <img id="func-foto" src="{{ asset('images/avatar-default.svg') }}"
                 style="width:70px; height:70px; border-radius:50%; object-fit:cover; border:3px solid var(--azul-primario)">
            <div style="flex:1">
              <div style="font-size:18px; font-weight:700; color:var(--cinza-900)" id="func-nome">—</div>
              <div style="font-size:13px; color:var(--cinza-600)" id="func-empresa">—</div>
              <div style="font-size:13px; color:var(--cinza-500)" id="func-funcao">—</div>
              <div style="margin-top:6px" id="func-coordenador"></div>
            </div>
            <div style="text-align:center">
              <div style="font-size:11px; text-transform:uppercase; color:var(--cinza-500); letter-spacing:.5px; margin-bottom:4px">Status Hoje</div>
              <div id="func-status" style="font-size:13px; font-weight:600"></div>
            </div>
          </div>
        </div>

        {{-- Número da pulseira --}}
        <div style="margin-bottom:16px">
          <label style="font-size:12px; font-weight:600; color:var(--cinza-500); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:6px">
            🪪 Número da Pulseira <span style="font-weight:400; color:var(--cinza-400)">(opcional)</span>
          </label>
          <input type="text" id="input-pulseira"
                 class="form-control"
                 maxlength="10"
                 autocomplete="off"
                 style="font-size:16px; font-weight:700; text-transform:uppercase; letter-spacing:2px; text-align:center; max-width:200px">
          <div style="font-size:11px; color:var(--cinza-400); margin-top:4px">Máx. 10 caracteres — letras e números</div>
        </div>

        <div class="d-flex gap-12" style="justify-content:center; flex-wrap:wrap">
          <button class="btn-ponto-entrada" id="btn-entrada" onclick="baterEntrada()">
            ▶ Registrar Entrada
          </button>
          <button class="btn btn-secondary" id="btn-entrada-manual-toggle" onclick="toggleEntradaManual()"
                  style="font-size:13px; padding:10px 18px">
            ⏱ Manual
          </button>
          <button class="btn-ponto-saida hidden" id="btn-saida" onclick="baterSaida()">
            ■ Registrar Saída
          </button>
        </div>

        {{-- Painel de entrada manual --}}
        <div id="painel-entrada-manual" style="display:none; margin-top:16px; background:var(--cinza-100); border:1.5px solid var(--cinza-300); border-radius:10px; padding:16px">
          <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--cinza-500); margin-bottom:10px">
            ⏱ Horário de Entrada Manual
          </div>
          <div class="d-flex gap-10 align-center">
            <input type="text" id="input-entrada-manual"
                   class="form-control" placeholder="HH:MM"
                   style="width:110px; font-size:18px; font-weight:600; text-align:center; letter-spacing:2px"
                   maxlength="5" autocomplete="off">
            <button class="btn btn-primary" style="font-size:13px; padding:10px 18px" onclick="baterEntradaManual()">
              ✓ Confirmar
            </button>
            <button class="btn btn-secondary" style="font-size:13px; padding:10px 14px" onclick="toggleEntradaManual()">
              ✕
            </button>
          </div>
          <div id="erro-entrada-manual" style="display:none; font-size:12px; color:var(--vermelho); margin-top:8px; font-weight:500"></div>
        </div>
      </div>

      {{-- Estado vazio --}}
      <div id="estado-vazio" style="text-align:center; padding:32px; color:var(--cinza-400)">
        <div style="font-size:48px; margin-bottom:12px">🔍</div>
        <div style="font-size:14px">Busque um funcionário pelo nome ou CPF para registrar o ponto.</div>
      </div>

    </div>

    {{-- SAÍDA PENDENTE (registros > 24h sem saída) --}}
    @if($semSaida->isNotEmpty())
    <div class="card mt-20" style="border-left:4px solid var(--amarelo)">
      <div class="card-header" style="flex-shrink:0">
        <div class="card-title" style="color:#92610a">
          ⏳ Saída Pendente
          <span style="background:var(--amarelo); color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; margin-left:6px">
            {{ $semSaida->count() }}
          </span>
        </div>
        <span style="font-size:11px; color:var(--cinza-500)">Registros sem saída há mais de 24h</span>
      </div>

      {{-- Busca --}}
      <div style="margin-bottom:10px; position:relative">
        <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:14px; color:var(--cinza-400); pointer-events:none">🔍</span>
        <input type="text" id="busca-sem-saida"
               class="form-control"
               placeholder="Pesquisar por nome, empresa ou data (ex: 28/05)..."
               style="padding-left:34px; font-size:12.5px; height:34px"
               autocomplete="off">
      </div>

      <div id="lista-sem-saida" style="display:flex; flex-direction:column; gap:8px; max-height:384px; overflow-y:auto">
        <div id="sem-resultado-pendente" style="display:none; text-align:center; padding:16px; color:var(--cinza-400); font-size:13px">
          Nenhum registro encontrado.
        </div>
        @foreach($semSaida as $ponto)
        <div class="sem-saida-item d-flex align-center gap-10"
             style="padding:10px 12px; background:var(--amarelo-light, #fffbeb); border-radius:8px; border:1px solid var(--amarelo, #f59e0b)"
             data-ponto-id="{{ $ponto->id }}"
             data-func-id="{{ $ponto->funcionario_id }}"
             data-nome="{{ strtolower($ponto->funcionario->nome) }}"
             data-empresa="{{ strtolower($ponto->empresa?->nome ?? 'sem empresa') }}"
             data-data="{{ $ponto->data?->format('d/m') ?? '' }}">
          <img src="{{ $ponto->funcionario->foto_url }}"
               style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid var(--amarelo); flex-shrink:0; margin-right:12px">
          <div style="flex:1; overflow:hidden">
            <div style="font-size:13px; font-weight:600; color:var(--cinza-800); white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
              {{ $ponto->funcionario->nome }}
            </div>
            <div style="font-size:11px; color:var(--cinza-500)">
              Entrada: <span class="mono" style="font-weight:600; color:#92610a">{{ $ponto->data?->format('d/m') ?? '—' }} {{ substr($ponto->entrada ?? '', 0, 5) }}</span>
              · {{ $ponto->empresa?->nome ?? 'Sem empresa vinculada' }}
            </div>
            @if($ponto->pulseira)
            <div style="font-size:11px; color:var(--cinza-600); margin-top:2px">
              Pulseira: <span class="mono" style="font-weight:700; color:var(--azul-primario)">{{ $ponto->pulseira }}</span>
            </div>
            @endif
            @if($ponto->evento)
            <span style="display:inline-flex;align-items:center;gap:4px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:600;margin-top:3px">
              {{ $ponto->evento->nome }}
            </span>
            @endif
          </div>
          <button class="btn-icon"
                  style="width:30px; height:30px; background:var(--vermelho-light); border-color:var(--vermelho); color:var(--vermelho)"
                  onclick="baterSaidaDireto({{ $ponto->id }}, '{{ addslashes($ponto->funcionario->nome) }}', {{ $ponto->funcionario_id }})"
                  title="Registrar Saída">
            ■
          </button>
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  {{-- LISTA DE PRESENTES --}}
  <div class="card ponto-presentes-card" style="max-height:calc(100vh - 140px); display:flex; flex-direction:column">
    <div class="card-header" style="flex-shrink:0">
      <div class="card-title">
        🟢 Presentes Agora
        <span style="background:var(--verde); color:#fff; padding:2px 10px; border-radius:20px; font-size:12px; margin-left:6px" id="contador-presentes">
          {{ $presentes->count() }}
        </span>
      </div>
      <span style="font-size:11px; color:var(--cinza-400)">últimas 24h</span>
    </div>

    {{-- Campo de pesquisa --}}
    <div style="padding:0 0 10px; flex-shrink:0; position:relative">
      <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:14px; color:var(--cinza-400); pointer-events:none">🔍</span>
      <input type="text" id="busca-presentes"
             class="form-control"
             placeholder="Pesquisar por nome ou empresa..."
             style="padding-left:34px; font-size:12.5px; height:34px"
             autocomplete="off">
    </div>

    <div id="lista-presentes" style="flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:8px">
      <div id="sem-resultado-busca" style="display:none; text-align:center; padding:20px; color:var(--cinza-400); font-size:13px">
        Nenhum funcionário encontrado.
      </div>
      @forelse($presentes as $ponto)
      <div class="presente-item d-flex align-center gap-10"
           style="padding:10px 12px; background:var(--cinza-100); border-radius:8px; border:1px solid var(--cinza-300)"
           data-ponto-id="{{ $ponto->id }}"
           data-func-id="{{ $ponto->funcionario_id }}"
           data-nome="{{ strtolower($ponto->funcionario->nome) }}"
           data-empresa="{{ strtolower($ponto->empresa?->nome ?? 'sem empresa') }}">
        <img src="{{ $ponto->funcionario->foto_url }}"
             style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid var(--verde); flex-shrink:0; margin-right: 12px;">
        <div style="flex:1; overflow:hidden">
          <div style="font-size:13px; font-weight:600; color:var(--cinza-800); white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
            {{ $ponto->funcionario->nome }}
          </div>
          <div style="font-size:11px; color:var(--cinza-500)">
            Entrada: <span class="mono" style="font-weight:600; color:var(--verde)">{{ substr($ponto->entrada ?? '', 0, 5) }}</span>
            · {{ $ponto->empresa?->nome ?? 'Sem empresa' }}
          </div>
          @if($ponto->pulseira)
          <div style="font-size:11px; color:var(--cinza-600); margin-top:2px">
            Pulseira: <span class="mono" style="font-weight:700; color:var(--azul-primario)">{{ $ponto->pulseira }}</span>
          </div>
          @endif
          @if($ponto->evento)
          <div style="margin-top:3px">
            <span style="display:inline-flex;align-items:center;gap:4px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:600">
              {{ $ponto->evento->nome }}
            </span>
          </div>
          @endif
        </div>
        <button class="btn-icon" style="width:30px; height:30px; background:var(--vermelho-light); border-color:var(--vermelho); color:var(--vermelho)"
                onclick="baterSaidaDireto({{ $ponto->id }}, '{{ addslashes($ponto->funcionario->nome) }}', {{ $ponto->funcionario_id }})"
                title="Registrar Saída">
          ■
        </button>
      </div>
      @empty
      <div style="text-align:center; padding:32px; color:var(--cinza-400); font-size:13px" id="nenhum-presente">
        Nenhum funcionário presente no momento.
      </div>
      @endforelse
    </div>
  </div>

</div>

{{-- ── MODAL DE SAÍDA ─────────────────────────────────────────────── --}}
<div id="modal-saida-wrap"
     style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.5);
            align-items:center; justify-content:center; padding:16px">
  <div style="background:#fff; border-radius:14px; padding:28px; width:420px; max-width:100%;
              box-shadow:0 24px 64px rgba(0,0,0,.28)">

    <div style="font-size:17px; font-weight:700; color:var(--cinza-900); margin-bottom:4px">
      ■ Registrar Saída
    </div>
    <div style="font-size:13px; color:var(--cinza-500); margin-bottom:20px" id="modal-saida-nome-label"></div>

    {{-- Tabs: Agora / Manual --}}
    <div style="display:flex; gap:8px; margin-bottom:20px">
      <button id="tab-saida-agora" type="button" onclick="toggleSaidaTipoModal('agora')"
              style="flex:1; padding:10px; border-radius:8px; font-size:13px; font-weight:600;
                     cursor:pointer; background:var(--azul-primario); color:#fff;
                     border:2px solid var(--azul-primario); font-family:var(--font-body)">
        ⚡ Agora
      </button>
      <button id="tab-saida-manual" type="button" onclick="toggleSaidaTipoModal('manual')"
              style="flex:1; padding:10px; border-radius:8px; font-size:13px; font-weight:600;
                     cursor:pointer; background:#fff; color:var(--cinza-600);
                     border:2px solid var(--cinza-300); font-family:var(--font-body)">
        ⏱ Manual
      </button>
    </div>

    {{-- Campos manuais --}}
    <div id="campos-saida-manual" style="display:none; margin-bottom:20px">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--cinza-600); display:block; margin-bottom:6px">
            Data de Saída
          </label>
          <input type="text" id="input-saida-data" class="form-control"
                 placeholder="dd/mm/aaaa" maxlength="10" autocomplete="off"
                 style="font-size:15px; font-weight:600; text-align:center; letter-spacing:1px">
        </div>
        <div>
          <label style="font-size:12px; font-weight:600; color:var(--cinza-600); display:block; margin-bottom:6px">
            Horário de Saída
          </label>
          <input type="text" id="input-saida-hora" class="form-control"
                 placeholder="HH:MM" maxlength="5" autocomplete="off"
                 style="font-size:15px; font-weight:600; text-align:center; letter-spacing:2px">
        </div>
      </div>
      <div id="erro-saida-manual"
           style="display:none; font-size:12px; color:var(--vermelho); margin-top:8px; font-weight:500">
      </div>
    </div>
    <hr style="margin-bottom: 20px">

    <div style="display:flex; gap:10px; justify-content:flex-end">
      <button type="button" onclick="fecharModalSaida()" class="btn btn-secondary">
        Cancelar
      </button>
      <button type="button" id="btn-confirmar-saida" onclick="confirmarSaidaModal()"
              style="background:var(--vermelho); color:#fff; border-color:var(--vermelho);
                     padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600;
                     cursor:pointer; font-family:var(--font-body); border-width:1.5px; border-style:solid">
        ■ Confirmar Saída
      </button>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
let funcionarioSelecionado = null;

// ── Estado do modal de saída ─────────────────────────────────────
let _saidaPontoId = null;
let _saidaFuncId  = null;
let _saidaTipo    = 'agora';

// ── Selecionar funcionário via autocomplete ───────────────────────
window.selecionarFuncionario = function (f) {
  funcionarioSelecionado = f;
  $('#func-foto').attr('src', f.foto_url);
  $('#func-nome').text(f.nome);
  $('#func-empresa').text(f.empresa);
  $('#func-funcao').text(f.funcao);
  $('#func-coordenador').html(f.coordenador ? '<span class="badge badge-coordenador">⭐ Coordenador</span>' : '');
  atualizarStatusFunc(f.id);
  $('#card-funcionario').removeClass('hidden');
  $('#estado-vazio').addClass('hidden');
};

function atualizarStatusFunc(id) {
  const $item   = $('.presente-item[data-func-id="' + id + '"]');
  const presente = $item.length > 0;
  if (presente) {
    const pontoId = $item.data('ponto-id');
    $('#func-status').html('<span class="badge badge-presente">● Presente</span>');
    $('#btn-entrada').addClass('hidden');
    $('#btn-entrada-manual-toggle').addClass('hidden');
    $('#btn-saida').removeClass('hidden').data('ponto-id', pontoId);
  } else {
    $('#func-status').html('<span class="badge badge-ausente">— Ausente / Sem entrada</span>');
    $('#btn-entrada').removeClass('hidden');
    $('#btn-entrada-manual-toggle').removeClass('hidden');
    $('#btn-saida').addClass('hidden');
  }
}

// ── Entrada manual toggle ─────────────────────────────────────────
window.toggleEntradaManual = function () {
  const painel = $('#painel-entrada-manual');
  const aberto = painel.is(':visible');
  painel.slideToggle(180);
  $('#erro-entrada-manual').hide();
  if (!aberto) {
    const agora = new Date();
    const hh = String(agora.getHours()).padStart(2, '0');
    const mm = String(agora.getMinutes()).padStart(2, '0');
    $('#input-entrada-manual').val(hh + ':' + mm).focus().select();
  }
};

// Máscara HH:MM no campo de entrada manual
$(document).on('input', '#input-entrada-manual', function () {
  let v = $(this).val().replace(/\D/g, '').substring(0, 4);
  if (v.length > 2) v = v.substring(0, 2) + ':' + v.substring(2);
  $(this).val(v);
  $('#erro-entrada-manual').hide();
});
$(document).on('keydown', '#input-entrada-manual', function (e) {
  if (e.key === 'Enter') baterEntradaManual();
});

// Limita pulseira a alfanumérico e força maiúsculo em tempo real
$(document).on('input', '#input-pulseira', function () {
  const v = $(this).val().replace(/[^A-Za-z0-9]/g, '').toUpperCase().substring(0, 10);
  $(this).val(v);
});

// ── Bater entrada AUTOMÁTICA ──────────────────────────────────────
window.baterEntrada = function () {
  if (!funcionarioSelecionado) return;
  const pulseira = $('#input-pulseira').val().trim() || null;
  const btn = $('#btn-entrada').prop('disabled', true).text('Registrando...');
  $.post('/api/ponto/entrada', { funcionario_id: funcionarioSelecionado.id, pulseira })
    .done(function (res) {
      showToast(res.mensagem, 'success');
      adicionarNaListaPresentes(funcionarioSelecionado, res.horario, res.evento_nome, res.pulseira);
      $('#func-status').html('<span class="badge badge-presente">● Presente</span>');
      $('#btn-entrada').addClass('hidden');
      $('#btn-entrada-manual-toggle').addClass('hidden');
      $('#btn-saida').removeClass('hidden').data('ponto-id', res.ponto_id);
      $('#painel-entrada-manual').hide();
      $('#input-pulseira').val('');
      const atual = parseInt($('#contador-presentes').text()) || 0;
      $('#contador-presentes').text(atual + 1);
      $('#nenhum-presente').remove();
    })
    .fail(xhr => showToast(xhr.responseJSON?.erro || 'Erro ao registrar entrada.', 'error'))
    .always(() => btn.prop('disabled', false).text('▶ Registrar Entrada'));
};

// ── Bater entrada MANUAL ──────────────────────────────────────────
window.baterEntradaManual = function () {
  if (!funcionarioSelecionado) return;
  const horario = $('#input-entrada-manual').val().trim();
  const partes  = horario.split(':');
  if (!/^\d{2}:\d{2}$/.test(horario)) {
    mostrarErroEntrada('Informe um horário válido no formato HH:MM.');
    return;
  }
  const hh = parseInt(partes[0]), mm = parseInt(partes[1]);
  if (hh > 23 || mm > 59) {
    mostrarErroEntrada('Horário inválido. Horas: 00–23, Minutos: 00–59.');
    return;
  }
  const agora = new Date(), entrada = new Date();
  entrada.setHours(hh, mm, 0, 0);
  if (entrada > agora) {
    mostrarErroEntrada('O horário de entrada não pode ser no futuro.');
    return;
  }
  const pulseira = $('#input-pulseira').val().trim() || null;
  const btn = $('[onclick="baterEntradaManual()"]').prop('disabled', true).text('Registrando...');
  $.post('/api/ponto/entrada', { funcionario_id: funcionarioSelecionado.id, entrada_manual: horario, pulseira })
    .done(function (res) {
      showToast(res.mensagem + ' às ' + res.horario, 'success');
      adicionarNaListaPresentes(funcionarioSelecionado, res.horario, res.evento_nome, res.pulseira);
      $('#func-status').html('<span class="badge badge-presente">● Presente</span>');
      $('#btn-entrada').addClass('hidden');
      $('#btn-entrada-manual-toggle').addClass('hidden');
      $('#btn-saida').removeClass('hidden').data('ponto-id', res.ponto_id);
      $('#painel-entrada-manual').hide();
      $('#input-pulseira').val('');
      const atual = parseInt($('#contador-presentes').text()) || 0;
      $('#contador-presentes').text(atual + 1);
      $('#nenhum-presente').remove();
    })
    .fail(xhr => mostrarErroEntrada(xhr.responseJSON?.erro || 'Erro ao registrar entrada.'))
    .always(() => btn.prop('disabled', false).text('✓ Confirmar'));
};

function mostrarErroEntrada(msg) {
  $('#erro-entrada-manual').text(msg).show();
}

// ── Modal de Saída ────────────────────────────────────────────────

function abrirModalSaida(nome, pontoId, funcId) {
  _saidaPontoId = pontoId;
  _saidaFuncId  = funcId || null;
  _saidaTipo    = 'agora';

  $('#modal-saida-nome-label').text(nome);
  $('#campos-saida-manual').hide();
  $('#erro-saida-manual').hide();
  toggleSaidaTipoModal('agora');

  // Pré-preenche data/hora atual nos campos manuais
  const agora = new Date();
  const dd    = String(agora.getDate()).padStart(2, '0');
  const mm    = String(agora.getMonth() + 1).padStart(2, '0');
  const yyyy  = agora.getFullYear();
  const hh    = String(agora.getHours()).padStart(2, '0');
  const mi    = String(agora.getMinutes()).padStart(2, '0');
  $('#input-saida-data').val(dd + '/' + mm + '/' + yyyy);
  $('#input-saida-hora').val(hh + ':' + mi);

  $('#modal-saida-wrap').css('display', 'flex');
}

window.fecharModalSaida = function () {
  $('#modal-saida-wrap').hide();
  _saidaPontoId = null;
  _saidaFuncId  = null;
};

// Fecha modal ao clicar no overlay
$('#modal-saida-wrap').on('click', function (e) {
  if (e.target === this) fecharModalSaida();
});

window.toggleSaidaTipoModal = function (tipo) {
  _saidaTipo = tipo;
  if (tipo === 'manual') {
    $('#campos-saida-manual').show();
    $('#tab-saida-manual').css({ background: 'var(--azul-primario)', color: '#fff', borderColor: 'var(--azul-primario)' });
    $('#tab-saida-agora').css({ background: '#fff', color: 'var(--cinza-600)', borderColor: 'var(--cinza-300)' });
    $('#input-saida-hora').focus().select();
  } else {
    $('#campos-saida-manual').hide();
    $('#tab-saida-agora').css({ background: 'var(--azul-primario)', color: '#fff', borderColor: 'var(--azul-primario)' });
    $('#tab-saida-manual').css({ background: '#fff', color: 'var(--cinza-600)', borderColor: 'var(--cinza-300)' });
  }
};

// Máscara data e hora nos campos manuais do modal
$(document).on('input', '#input-saida-data', function () {
  let v = $(this).val().replace(/\D/g, '').substring(0, 8);
  if (v.length > 4) v = v.substring(0, 2) + '/' + v.substring(2, 4) + '/' + v.substring(4);
  else if (v.length > 2) v = v.substring(0, 2) + '/' + v.substring(2);
  $(this).val(v);
  $('#erro-saida-manual').hide();
});
$(document).on('input', '#input-saida-hora', function () {
  let v = $(this).val().replace(/\D/g, '').substring(0, 4);
  if (v.length > 2) v = v.substring(0, 2) + ':' + v.substring(2);
  $(this).val(v);
  $('#erro-saida-manual').hide();
});
$(document).on('keydown', '#input-saida-hora', function (e) {
  if (e.key === 'Enter') confirmarSaidaModal();
});

window.confirmarSaidaModal = function () {
  if (!_saidaPontoId) return;

  const postData = { ponto_id: _saidaPontoId };

  if (_saidaTipo === 'manual') {
    const dataVal = $('#input-saida-data').val().trim();
    const horaVal = $('#input-saida-hora').val().trim();
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(dataVal)) {
      $('#erro-saida-manual').text('Informe a data no formato dd/mm/aaaa.').show();
      return;
    }
    if (!/^\d{2}:\d{2}$/.test(horaVal)) {
      $('#erro-saida-manual').text('Informe o horário no formato HH:MM.').show();
      return;
    }
    const [, , ] = dataVal.split('/');
    const [hhN, miN] = horaVal.split(':').map(Number);
    if (hhN > 23 || miN > 59) {
      $('#erro-saida-manual').text('Horário inválido.').show();
      return;
    }
    postData.saida_manual = horaVal;
    postData.data_saida   = dataVal;
  }

  const $btn = $('#btn-confirmar-saida').prop('disabled', true).text('Registrando...');

  $.post('/api/ponto/saida', postData)
    .done(function (res) {
      showToast(res.mensagem + ' — Horas: ' + res.horas, 'success');

      const pontoId = _saidaPontoId;
      const funcId  = _saidaFuncId;
      fecharModalSaida();

      // Remove da lista Presentes Agora (pelo ponto-id ou pelo func-id)
      const $presente = $('.presente-item[data-ponto-id="' + pontoId + '"]');
      if ($presente.length) {
        $presente.remove();
        const cnt = parseInt($('#contador-presentes').text()) || 1;
        $('#contador-presentes').text(Math.max(0, cnt - 1));
      } else if (funcId) {
        removerDaListaPresentes(funcId);
      }

      // Remove da lista Saída Pendente
      $('.sem-saida-item[data-ponto-id="' + pontoId + '"]').remove();

      // Reseta painel do funcionário selecionado se for o mesmo
      if (funcionarioSelecionado && funcId && funcionarioSelecionado.id == funcId) {
        $('#card-funcionario').addClass('hidden');
        $('#estado-vazio').removeClass('hidden');
        $('#campo-busca-funcionario').val('');
        funcionarioSelecionado = null;
      }
    })
    .fail(function (xhr) {
      $('#erro-saida-manual').text(xhr.responseJSON?.erro || 'Erro ao registrar saída.').show();
      if (_saidaTipo === 'agora') {
        showToast(xhr.responseJSON?.erro || 'Erro ao registrar saída.', 'error');
      }
    })
    .always(() => $btn.prop('disabled', false).text('■ Confirmar Saída'));
};

// ── Bater saída (funcionário selecionado no painel) ───────────────
window.baterSaida = function () {
  const pontoId = $('#btn-saida').data('ponto-id');
  if (!pontoId || !funcionarioSelecionado) return;
  abrirModalSaida(funcionarioSelecionado.nome, pontoId, funcionarioSelecionado.id);
};

// ── Bater saída direto da lista ───────────────────────────────────
window.baterSaidaDireto = function (pontoId, nome, funcId) {
  abrirModalSaida(nome, pontoId, funcId || null);
};

// ── Adicionar na lista de presentes ──────────────────────────────
function adicionarNaListaPresentes(f, hora, eventoNome, pulseira) {
  const pulseiraTag = pulseira
    ? `<div style="font-size:11px; color:var(--cinza-600); margin-top:2px">
         Pulseira: <span class="mono" style="font-weight:700; color:var(--azul-primario)">${pulseira}</span>
       </div>`
    : '';
  const eventoTag = eventoNome
    ? `<div style="margin-top:3px">
         <span style="display:inline-flex;align-items:center;gap:4px;background:var(--roxo-light,#ede9fe);color:var(--roxo);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:600">
           ${eventoNome}
         </span>
       </div>`
    : '';
  const html = `
    <div class="presente-item d-flex align-center gap-10"
         style="padding:10px 12px; background:var(--cinza-100); border-radius:8px; border:1px solid var(--cinza-300); animation:toast-in .3s ease"
         data-ponto-id="" data-func-id="${f.id}"
         data-nome="${f.nome.toLowerCase()}" data-empresa="${f.empresa.toLowerCase()}">
      <img src="${f.foto_url}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--verde);flex-shrink:0;margin-right:12px">
      <div style="flex:1; overflow:hidden">
        <div style="font-size:13px; font-weight:600; color:var(--cinza-800)">${f.nome}</div>
        <div style="font-size:11px; color:var(--cinza-500)">Entrada: <span class="mono" style="font-weight:600;color:var(--verde)">${hora}</span> · ${f.empresa}</div>
        ${pulseiraTag}
        ${eventoTag}
      </div>
      <button class="btn-icon" style="width:30px;height:30px;background:var(--vermelho-light);border-color:var(--vermelho);color:var(--vermelho)"
              onclick="baterSaidaDireto('', '${f.nome}', ${f.id})" title="Registrar Saída">■</button>
    </div>`;
  $('#lista-presentes').prepend(html);
  filtrarPresentes($('#busca-presentes').val());
}

// ── Filtro: Presentes Agora ────────────────────────────────────────
function filtrarPresentes(termo) {
  const q = (termo || '').trim().toLowerCase();
  let visiveis = 0;
  $('.presente-item').each(function () {
    const bate = !q || ($(this).data('nome') || '').includes(q) || ($(this).data('empresa') || '').includes(q);
    $(this).toggle(bate);
    if (bate) visiveis++;
  });
  const total = $('.presente-item').length;
  $('#sem-resultado-busca').toggle(total > 0 && visiveis === 0);
}
$(document).on('input', '#busca-presentes', function () {
  filtrarPresentes($(this).val());
});

// ── Filtro: Saída Pendente ─────────────────────────────────────────
function filtrarSemSaida(termo) {
  const q = (termo || '').trim().toLowerCase();
  let visiveis = 0;
  $('.sem-saida-item').each(function () {
    const bate = !q
      || ($(this).data('nome')    || '').includes(q)
      || ($(this).data('empresa') || '').includes(q)
      || ($(this).data('data')    || '').includes(q);
    $(this).toggle(bate);
    if (bate) visiveis++;
  });
  const total = $('.sem-saida-item').length;
  $('#sem-resultado-pendente').toggle(total > 0 && visiveis === 0);
}
$(document).on('input', '#busca-sem-saida', function () {
  filtrarSemSaida($(this).val());
});

// ── Remover da lista Presentes ────────────────────────────────────
function removerDaListaPresentes(funcId) {
  const item = $('.presente-item[data-func-id="' + funcId + '"]');
  item.css({ opacity: 0, transition: 'opacity .3s' });
  setTimeout(() => {
    item.remove();
    const atual = parseInt($('#contador-presentes').text()) || 1;
    $('#contador-presentes').text(Math.max(0, atual - 1));
  }, 300);
}
</script>
@endpush
