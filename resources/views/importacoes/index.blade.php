@extends('layouts.app')
@section('title', 'Importações')
@section('breadcrumb') <span class="current">Importações</span> @endsection

@section('content')

<div class="page-header">
  <div>
    <h1 class="page-title"><span class="page-icon">📥</span> Importações</h1>
    <p class="page-subtitle">Histórico de importações de empresas e funcionários via planilha</p>
  </div>
  <button class="btn btn-primary" onclick="abrirModalUpload()">
    ⬆ Importar Planilha
  </button>
</div>

{{-- ── TABELA DE HISTÓRICO ─────────────────────────────────── --}}
<div class="card" style="padding:0">
  <div class="table-container" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Data / Hora</th>
          <th>Arquivo</th>
          <th>Empresa</th>
          <th style="text-align:center">Ação</th>
          <th style="text-align:center">Importados</th>
          <th style="text-align:center">Erros</th>
          <th style="text-align:center">Total</th>
          <th>Importado por</th>
          <th style="text-align:center">Detalhes</th>
        </tr>
      </thead>
      <tbody>
        @forelse($importacoes as $imp)
        <tr>
          <td class="mono" style="font-size:12px; white-space:nowrap">
            {{ $imp->created_at->format('d/m/Y H:i') }}
          </td>
          <td style="font-size:12.5px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
            {{ $imp->arquivo_nome }}
          </td>
          <td style="font-size:13px">
            @if($imp->empresa)
              <a href="{{ route('empresas.show', $imp->empresa) }}"
                 style="color:var(--azul-primario); font-weight:600">
                {{ $imp->empresa->nome }}
              </a>
            @else
              <span style="color:var(--cinza-400)">{{ $imp->empresa_nome ?? '—' }}</span>
            @endif
          </td>
          <td style="text-align:center">
            @if($imp->empresa_acao === 'nova')
              <span style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">Nova</span>
            @elseif($imp->empresa_acao === 'existente')
              <span style="background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">Existente</span>
            @else
              <span style="color:var(--cinza-400)">—</span>
            @endif
          </td>
          <td style="text-align:center">
            <span style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
              {{ $imp->importados }}
            </span>
          </td>
          <td style="text-align:center">
            @if($imp->com_erros > 0)
              <span style="background:var(--vermelho-light);color:var(--vermelho);border:1px solid var(--vermelho);padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">
                {{ $imp->com_erros }}
              </span>
            @else
              <span style="color:var(--cinza-400); font-size:13px">—</span>
            @endif
          </td>
          <td style="text-align:center">
            <span class="mono" style="font-weight:700; font-size:13px; color:var(--cinza-700)">
              {{ $imp->total_funcionarios }}
            </span>
          </td>
          <td style="font-size:12.5px; color:var(--cinza-600)">
            {{ $imp->usuario?->nome_abreviado ?? '—' }}
          </td>
          <td style="text-align:center">
            @if($imp->com_erros > 0)
              <button class="btn-icon" title="Ver erros"
                      onclick="verErros({{ $imp->id }})"
                      style="width:30px; height:30px; background:var(--vermelho-light); border-color:var(--vermelho); color:var(--vermelho)">
                ⚠
              </button>
            @else
              <span style="color:var(--verde); font-size:16px">✓</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center; padding:56px; color:var(--cinza-400)">
            <div style="font-size:40px; margin-bottom:12px">📥</div>
            <div style="font-size:15px; font-weight:600; margin-bottom:4px">Nenhuma importação realizada</div>
            <div style="font-size:13px">Clique em "Importar Planilha" para começar.</div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="pagination-wrap">
  <div class="pagination-info">
    @if($importacoes->total() > 0)
      Exibindo {{ $importacoes->firstItem() }}–{{ $importacoes->lastItem() }} de {{ $importacoes->total() }}
    @endif
  </div>
  {{ $importacoes->links('components.pagination') }}
</div>

{{-- Dados de erros para JS --}}
<script>
const _errosPorId = {
  @foreach($importacoes as $imp)
    @if($imp->detalhes_erros)
      {{ $imp->id }}: @json($imp->detalhes_erros),
    @endif
  @endforeach
};
</script>

@endsection

{{-- ════════════════════════════════════════════════════════════
     MODAL UPLOAD
     ════════════════════════════════════════════════════════════ --}}
@push('styles')
<style>
  .imp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(0,0,0,.5);
    align-items: center;
    justify-content: center;
    padding: 16px;
  }
  .imp-modal-box {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    width: 520px;
    max-width: 100%;
    box-shadow: 0 24px 64px rgba(0,0,0,.28);
  }
  .imp-step { display: none; }
  .imp-step.ativo { display: block; }

  .upload-area {
    display: block;
    border: 2.5px dashed var(--cinza-300);
    border-radius: 10px;
    padding: 40px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    width: 100%;
    box-sizing: border-box;
  }
  .upload-area:hover, .upload-area.drag-over {
    border-color: var(--azul-primario);
    background: var(--azul-light);
  }
  .upload-area input[type=file] { display: none; }

  .erros-lista {
    max-height: 280px;
    overflow-y: auto;
    background: var(--vermelho-light);
    border: 1px solid var(--vermelho);
    border-radius: 8px;
    padding: 12px;
    font-size: 12px;
    font-family: var(--font-mono);
    color: var(--vermelho);
  }
  .erros-lista li { margin-bottom: 4px; }

  .decisao-btn {
    width: 100%;
    padding: 14px 16px;
    border-radius: 10px;
    border: 2px solid var(--cinza-300);
    background: #fff;
    font-family: var(--font-body);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
    transition: border-color .18s, background .18s;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .decisao-btn:hover { border-color: var(--azul-primario); background: var(--azul-light); }
  .decisao-btn.selecionado { border-color: var(--azul-primario); background: var(--azul-light); }
</style>
@endpush

@push('scripts')

{{-- ── Modal de Upload ──────────────────────────────────────────── --}}
<div id="modal-importacao" class="imp-modal-overlay">
  <div class="imp-modal-box">

    {{-- STEP 1: Upload --}}
    <div class="imp-step ativo" id="imp-step-1">
      <div style="font-size:17px; font-weight:700; color:var(--cinza-900); margin-bottom:4px">📥 Importar Planilha</div>
      <div style="font-size:13px; color:var(--cinza-500); margin-bottom:20px">
        Use a planilha modelo para garantir a importação correta.
        <a href="https://docs.google.com/spreadsheets/d/1E_Q5fzfTfZ6uDL-Jr2plEC2wWZV0xcIj/edit" target="_blank"
           style="color:var(--azul-primario); font-weight:600">
          Baixar modelo ↗
        </a>
      </div>

      <label for="input-arquivo-imp" class="upload-area" id="upload-area">
        <input type="file" id="input-arquivo-imp" accept=".xlsx,.xls" style="display:none">
        <div id="upload-area-texto">
          <div style="font-size:36px; margin-bottom:8px">📊</div>
          <div style="font-size:14px; font-weight:600; color:var(--cinza-700)">Clique ou arraste o arquivo aqui</div>
          <div style="font-size:12px; color:var(--cinza-400); margin-top:4px">Aceita .xlsx e .xls • Máx 20 MB</div>
        </div>
        <div id="nome-arquivo-selecionado"
             style="display:none; font-size:14px; font-weight:600; color:var(--azul-primario); align-items:center; gap:8px; justify-content:center">
          <span style="font-size:20px">📄</span>
          <span id="nome-arquivo-texto"></span>
        </div>
      </label>

      <div id="imp-erro-geral"
           style="display:none; margin-top:12px; padding:10px 14px; background:var(--vermelho-light); border:1px solid var(--vermelho); border-radius:8px; font-size:13px; color:var(--vermelho); font-weight:500">
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px">
        <button type="button" onclick="fecharModalImportacao()" class="btn btn-secondary">Cancelar</button>
        <button type="button" id="btn-analisar" class="btn btn-primary">Analisar Planilha →</button>
      </div>
    </div>

    {{-- STEP 2: Empresa duplicada --}}
    <div class="imp-step" id="imp-step-2">
      <div style="font-size:17px; font-weight:700; color:var(--cinza-900); margin-bottom:4px">⚠ Empresa já cadastrada</div>
      <div id="info-empresa-existente"
           style="font-size:13px; color:var(--cinza-600); margin-bottom:20px; padding:10px 14px; background:var(--amarelo-light); border:1px solid var(--amarelo); border-radius:8px">
      </div>

      <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px">
        <button type="button" class="decisao-btn" id="btn-decisao-usar_existente"
                onclick="selecionarDecisao('usar_existente')">
          <span style="font-size:24px">🔗</span>
          <div>
            <div style="font-size:14px; font-weight:700">Usar empresa existente</div>
            <div style="font-size:12px; color:var(--cinza-500); font-weight:400">Os novos funcionários serão vinculados à empresa já cadastrada.</div>
          </div>
        </button>
        <button type="button" class="decisao-btn" id="btn-decisao-criar_nova"
                onclick="selecionarDecisao('criar_nova')">
          <span style="font-size:24px">➕</span>
          <div>
            <div style="font-size:14px; font-weight:700">Criar nova empresa com o mesmo nome</div>
            <div style="font-size:12px; color:var(--cinza-500); font-weight:400">Um novo registro de empresa será criado e vinculado aos funcionários.</div>
          </div>
        </button>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end">
        <button type="button" onclick="irParaStep(1)" class="btn btn-secondary">← Voltar</button>
        <button type="button" id="btn-confirmar-decisao" class="btn btn-primary" disabled>Continuar →</button>
      </div>
    </div>

    {{-- STEP 3: Confirmação --}}
    <div class="imp-step" id="imp-step-3">
      <div style="font-size:17px; font-weight:700; color:var(--cinza-900); margin-bottom:20px">✅ Confirmar Importação</div>

      <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:24px">
        <div style="display:flex; justify-content:space-between; padding:10px 14px; background:var(--cinza-100); border-radius:8px; font-size:13px">
          <span style="color:var(--cinza-500); font-weight:600">Empresa</span>
          <span style="font-weight:700; color:var(--cinza-800)" id="preview-empresa-nome">—</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:10px 14px; background:var(--cinza-100); border-radius:8px; font-size:13px">
          <span style="color:var(--cinza-500); font-weight:600">Funcionários a importar</span>
          <span style="font-weight:700; color:var(--azul-primario); font-size:16px" id="preview-total-func">0</span>
        </div>
      </div>

      <div id="imp-erro-step3"
           style="display:none; margin-bottom:12px; padding:10px 14px; background:var(--vermelho-light); border:1px solid var(--vermelho); border-radius:8px; font-size:13px; color:var(--vermelho); font-weight:500">
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end">
        <button type="button" onclick="fecharModalImportacao()" class="btn btn-secondary">Cancelar</button>
        <button type="button" id="btn-processar" class="btn btn-primary">Confirmar e Importar ✓</button>
      </div>
    </div>

    {{-- STEP 4: Resultado --}}
    <div class="imp-step" id="imp-step-4">
      <div style="font-size:17px; font-weight:700; color:var(--cinza-900); margin-bottom:20px">📊 Resultado da Importação</div>

      <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px">
        <div style="display:flex; justify-content:space-between; padding:10px 14px; background:#d1fae5; border:1px solid #6ee7b7; border-radius:8px; font-size:13px">
          <span style="color:#065f46; font-weight:600">✅ Funcionários importados</span>
          <span style="font-weight:800; color:#065f46; font-size:18px" id="res-importados">0</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:10px 14px; background:var(--cinza-100); border-radius:8px; font-size:13px">
          <span style="color:var(--cinza-500); font-weight:600">Empresa</span>
          <span style="font-weight:700; color:var(--cinza-800)" id="res-empresa">—</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:10px 14px; background:var(--cinza-100); border-radius:8px; font-size:13px">
          <span style="color:var(--cinza-500); font-weight:600">⚠ Registros com erro</span>
          <span style="font-weight:700; color:var(--vermelho)" id="res-erros">0</span>
        </div>
      </div>

      <div id="res-bloco-erros" style="display:none; margin-bottom:20px">
        <div style="font-size:12px; font-weight:600; color:var(--vermelho); margin-bottom:6px">Detalhes dos erros:</div>
        <ul class="erros-lista" id="res-lista-erros"></ul>
      </div>

      <div style="display:flex; justify-content:flex-end">
        <button type="button" id="btn-fechar-resultado" class="btn btn-primary">Fechar e Atualizar</button>
      </div>
    </div>

  </div>
</div>

{{-- ── Modal de erros de importações passadas ──────────────────── --}}
<div id="modal-erros" class="imp-modal-overlay">
  <div class="imp-modal-box" style="max-width:500px">
    <div style="font-size:16px; font-weight:700; color:var(--cinza-900); margin-bottom:16px">⚠ Erros da Importação</div>
    <ul class="erros-lista" id="modal-erros-lista"></ul>
    <div style="display:flex; justify-content:flex-end; margin-top:16px">
      <button type="button" onclick="$('#modal-erros').hide()" class="btn btn-secondary">Fechar</button>
    </div>
  </div>
</div>

<script>
// ── Estado ────────────────────────────────────────────────────────
let _arquivoSelecionado = null;
let _parsedData         = null;
let _decisao            = null;
let _empresaExistenteId = null;

// ── Modal ─────────────────────────────────────────────────────────
window.abrirModalUpload = function () {
  resetarModal();
  $('#modal-importacao').css('display', 'flex');
};

window.fecharModalImportacao = function () {
  $('#modal-importacao').hide();
  resetarModal();
};

function resetarModal() {
  _arquivoSelecionado = null;
  _parsedData         = null;
  _decisao            = null;
  _empresaExistenteId = null;
  $('#input-arquivo-imp').val('');
  $('#nome-arquivo-texto').text('');
  $('#nome-arquivo-selecionado').hide();
  $('#upload-area-texto').show();
  $('#upload-area').removeClass('drag-over');
  $('.decisao-btn').removeClass('selecionado');
  $('#btn-confirmar-decisao').prop('disabled', true);
  irParaStep(1);
  $('.imp-modal-overlay[id=modal-importacao] #imp-erro-geral').hide();
}

$('#modal-importacao').on('click', function (e) {
  if (e.target === this) fecharModalImportacao();
});

function irParaStep(n) {
  $('.imp-step').removeClass('ativo');
  $('#imp-step-' + n).addClass('ativo');
}

// ── Upload de arquivo ─────────────────────────────────────────────
// O <label for="input-arquivo-imp"> abre o seletor nativamente (sem JS).
// Aqui apenas tratamos drag & drop e a exibição do nome.

$('#upload-area').on('dragover dragenter', function (e) {
  e.preventDefault();
  e.stopPropagation();
  $(this).addClass('drag-over');
});

$('#upload-area').on('dragleave', function (e) {
  e.preventDefault();
  $(this).removeClass('drag-over');
});

$('#upload-area').on('drop', function (e) {
  e.preventDefault();
  e.stopPropagation();
  $(this).removeClass('drag-over');
  const files = e.originalEvent.dataTransfer.files;
  if (files && files.length) selecionarArquivo(files[0]);
});

$('#input-arquivo-imp').on('change', function () {
  if (this.files && this.files.length) selecionarArquivo(this.files[0]);
});

function selecionarArquivo(file) {
  const ext = file.name.split('.').pop().toLowerCase();
  if (!['xlsx', 'xls'].includes(ext)) {
    mostrarErroGeral('Apenas arquivos .xlsx e .xls são aceitos.');
    return;
  }
  _arquivoSelecionado = file;
  $('#upload-area-texto').hide();
  $('#nome-arquivo-texto').text(file.name);
  $('#nome-arquivo-selecionado').css('display', 'flex');
  $('#imp-erro-geral').hide();
}

// ── Analisar planilha ─────────────────────────────────────────────
$('#btn-analisar').on('click', function () {
  if (!_arquivoSelecionado) {
    mostrarErroGeral('Selecione um arquivo antes de continuar.');
    return;
  }

  const formData = new FormData();
  formData.append('arquivo', _arquivoSelecionado);
  formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

  const $btn = $(this).prop('disabled', true).text('Analisando...');

  $.ajax({
    url: '{{ route("importacoes.upload") }}',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
  })
  .done(function (res) {
    _parsedData = res;
    $('#preview-empresa-nome').text(res.empresa_nome);
    $('#preview-total-func').text(res.total_funcionarios);

    if (res.empresa_duplicada) {
      $('#info-empresa-existente').text(
        'Já existe uma empresa cadastrada com o nome "' + res.empresa_existente.nome + '".'
      );
      _empresaExistenteId = res.empresa_existente.id;
      irParaStep(2);
    } else {
      irParaStep(3);
    }
  })
  .fail(function (xhr) {
    mostrarErroGeral(xhr.responseJSON?.erro || xhr.responseJSON?.errors?.arquivo?.[0] || 'Erro ao analisar o arquivo.');
  })
  .always(() => $btn.prop('disabled', false).text('Analisar Planilha'));
});

// ── Decisão sobre empresa duplicada ───────────────────────────────
window.selecionarDecisao = function (decisao) {
  _decisao = decisao;
  $('.decisao-btn').removeClass('selecionado');
  $('#btn-decisao-' + decisao).addClass('selecionado');
  $('#btn-confirmar-decisao').prop('disabled', false);
};

$('#btn-confirmar-decisao').on('click', function () {
  if (!_decisao) return;
  irParaStep(3);
});

// ── Processar importação ──────────────────────────────────────────
$('#btn-processar').on('click', function () {
  const postData = {
    _token:  $('meta[name="csrf-token"]').attr('content'),
    decisao: _parsedData?.empresa_duplicada ? _decisao : 'criar_nova',
  };

  if (postData.decisao === 'usar_existente') {
    postData.empresa_existente_id = _empresaExistenteId;
  }

  const $btn = $(this).prop('disabled', true).text('Importando...');

  $.post('{{ route("importacoes.processar") }}', postData)
    .done(function (res) {
      // Exibe resultados
      $('#res-importados').text(res.importados);
      $('#res-erros').text(res.com_erros);
      $('#res-empresa').text(res.empresa.nome);

      if (res.erros && res.erros.length > 0) {
        const lista = res.erros.map(e => `<li>${e}</li>`).join('');
        $('#res-lista-erros').html(lista);
        $('#res-bloco-erros').show();
      } else {
        $('#res-bloco-erros').hide();
      }

      irParaStep(4);
    })
    .fail(function (xhr) {
      $('#imp-erro-step3').text(xhr.responseJSON?.erro || 'Erro ao processar a importação.').show();
      $btn.prop('disabled', false).text('Confirmar e Importar ✓');
    })
    .always(function () {});
});

$('#btn-fechar-resultado').on('click', function () {
  fecharModalImportacao();
  window.location.reload();
});

// ── Ver erros de importação passada ───────────────────────────────
window.verErros = function (id) {
  const erros = _errosPorId[id] || [];
  if (!erros.length) return;
  const lista = erros.map(e => `<li>${e}</li>`).join('');
  $('#modal-erros-lista').html(lista);
  $('#modal-erros').css('display', 'flex');
};

$('#modal-erros').on('click', function (e) {
  if (e.target === this) $(this).hide();
});

function mostrarErroGeral(msg) {
  $('#imp-erro-geral').text(msg).show();
}
</script>
@endpush
