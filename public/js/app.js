/**
 * CREDENCIAMENTO EVENTOS PONCIANO
 * JavaScript Global — jQuery + Interações
 */

$(function () {

  // ============================================================
  // SIDEBAR — Toggle colapso
  // ============================================================
  const sidebar = $('.sidebar');
  const SIDEBAR_KEY = 'sidebar_collapsed';

  // Restaurar estado salvo
  if (localStorage.getItem(SIDEBAR_KEY) === '1') {
    sidebar.addClass('collapsed');
  }

  $('#btn-toggle-sidebar').on('click', function () {
    if ($(window).width() > 992) {
      sidebar.toggleClass('collapsed');
      localStorage.setItem(SIDEBAR_KEY, sidebar.hasClass('collapsed') ? '1' : '0');
    } else {
      sidebar.toggleClass('mobile-open');
    }
  });

  // Fechar sidebar mobile ao clicar no overlay
  $(document).on('click', '.sidebar-overlay', function () {
    sidebar.removeClass('mobile-open');
  });

  // ============================================================
  // TOASTS
  // ============================================================
  window.showToast = function (mensagem, tipo = 'success', duracao = 3500) {
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
    const toast = $(`
      <div class="toast ${tipo}">
        <span>${icons[tipo] || '✓'}</span>
        <span>${mensagem}</span>
      </div>
    `);

    if (!$('.toast-container').length) {
      $('body').append('<div class="toast-container"></div>');
    }

    $('.toast-container').append(toast);

    setTimeout(() => {
      toast.css({ opacity: 0, transform: 'translateX(24px)', transition: 'all .3s' });
      setTimeout(() => toast.remove(), 300);
    }, duracao);
  };

  // Exibir flash messages do Laravel como toasts
  const flashSuccess = $('meta[name="flash-success"]').attr('content');
  const flashError   = $('meta[name="flash-error"]').attr('content');
  if (flashSuccess) showToast(flashSuccess, 'success');
  if (flashError)   showToast(flashError, 'error');

  // ============================================================
  // MÁSCARAS (jQuery Mask)
  // ============================================================
  $('[data-mask="cpf"]').mask('000.000.000-00', { reverse: false });
  $('[data-mask="tel"]').mask('(00) 00000-0000');
  $('[data-mask="date"]').mask('00/00/0000');
  $('[data-mask="time"]').mask('00:00');
  $('[data-mask="hora"]').mask('00:00:00');

  // ============================================================
  // UPLOAD DE FOTO — Preview
  // ============================================================
  $(document).on('change', '.input-foto', function () {
    const file   = this.files[0];
    const target = $(this).data('preview');
    if (file && file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => { $(target).attr('src', e.target.result).show(); };
      reader.readAsDataURL(file);
    }
  });

  // Drag & drop na caixa de foto
  $(document).on('dragover', '.foto-upload-box', function (e) {
    e.preventDefault();
    $(this).css('border-color', 'var(--azul-primario)');
  });

  $(document).on('dragleave', '.foto-upload-box', function () {
    $(this).css('border-color', '');
  });

  $(document).on('drop', '.foto-upload-box', function (e) {
    e.preventDefault();
    $(this).css('border-color', '');
    const file = e.originalEvent.dataTransfer.files[0];
    const input = $(this).find('.input-foto')[0];
    if (file && input) {
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      $(input).trigger('change');
    }
  });

  // ============================================================
  // MODAL DE CONFIRMAÇÃO
  // ============================================================
  window.confirmar = function ({ titulo, mensagem, icone = '⚠️', onConfirm, btnLabel = 'Confirmar', tipo = 'danger' }) {
    const modal = $(`
      <div class="modal-overlay show" id="modal-confirm">
        <div class="modal-box">
          <div class="modal-icon">${icone}</div>
          <div class="modal-title">${titulo}</div>
          <div class="modal-msg">${mensagem}</div>
          <div class="modal-actions">
            <button class="btn btn-secondary" id="modal-cancelar">Cancelar</button>
            <button class="btn btn-${tipo}" id="modal-ok">${btnLabel}</button>
          </div>
        </div>
      </div>
    `);

    $('body').append(modal);

    modal.find('#modal-ok').on('click', function () {
      modal.remove();
      if (typeof onConfirm === 'function') onConfirm();
    });

    modal.find('#modal-cancelar, .modal-overlay').on('click', function (e) {
      if (e.target === this) modal.remove();
    });
  };

  // Formulários com data-confirm
  $(document).on('submit', 'form[data-confirm]', function (e) {
    e.preventDefault();
    const form = this;
    confirmar({
      titulo:   $(form).data('confirm-titulo') || 'Confirmar ação',
      mensagem: $(form).data('confirm') || 'Deseja continuar?',
      icone:    $(form).data('confirm-icone') || '⚠️',
      btnLabel: $(form).data('confirm-btn') || 'Confirmar',
      tipo:     $(form).data('confirm-tipo') || 'danger',
      onConfirm: () => form.submit(),
    });
  });

  // ============================================================
  // RELÓGIO AO VIVO
  // ============================================================
  function atualizarRelogio () {
    const agora = new Date();
    const hora  = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const data  = agora.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
    $('.relogio-live').text(hora);
    $('.relogio-data').text(data);
  }

  if ($('.relogio-live').length) {
    atualizarRelogio();
    setInterval(atualizarRelogio, 1000);
  }

  // ============================================================
  // AUTOCOMPLETE DE FUNCIONÁRIOS (para ponto)
  // ============================================================
  let autocompleteTimer;

  $(document).on('input', '#campo-busca-funcionario', function () {
    const termo = $(this).val();
    clearTimeout(autocompleteTimer);

    if (termo.length < 2) {
      $('#autocomplete-resultado').hide().empty();
      return;
    }

    autocompleteTimer = setTimeout(() => {
      $.get('/api/funcionarios/autocomplete', { q: termo }, function (dados) {
        const lista = $('#autocomplete-resultado').empty().show();

        if (!dados.length) {
          lista.html('<div class="autocomplete-item text-muted">Nenhum resultado encontrado.</div>');
          return;
        }

        dados.forEach(f => {
          const coordBadge = f.coordenador ? ' <span class="badge badge-coordenador" style="font-size:10px">Coord.</span>' : '';
          const item = $(`
            <div class="autocomplete-item" data-id="${f.id}">
              <img class="autocomplete-avatar" src="${f.foto_url}" alt="">
              <div class="autocomplete-info">
                <div class="nome">${f.nome}${coordBadge}</div>
                <div class="sub">${f.empresa} · ${f.funcao}</div>
              </div>
            </div>
          `);

          item.on('click', function () {
            window.selecionarFuncionario(f);
            lista.hide().empty();
            $('#campo-busca-funcionario').val(f.nome);
          });

          lista.append(item);
        });
      });
    }, 280);
  });

  // Fechar autocomplete ao clicar fora
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.search-ponto-wrap').length) {
      $('#autocomplete-resultado').hide();
    }
  });

  // ============================================================
  // ATUALIZAÇÃO AUTOMÁTICA DOS INDICADORES (dashboard)
  // ============================================================
  if ($('.indicadores-grid').length && $('body').data('pagina') === 'dashboard') {
    setInterval(() => {
      const data = new URLSearchParams(window.location.search).get('data') || '';
      $.get('/api/indicadores', { data }, function (res) {
        $('.ind-presentes').text(res.presentes);
        $('.ind-finalizados').text(res.finalizados);
        $('.ind-total').text(res.total_dia);
        $('.ind-coordenadores').text(res.coordenadores);
        $('.ind-atualizado').text('Atualizado ' + res.atualizado_em);
      });
    }, 60000); // a cada 60s
  }

  // ============================================================
  // CSRFF TOKEN no header de todas as requisições AJAX
  // ============================================================
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // ============================================================
  // BUSCA COM DEBOUNCE (filtros de tabela)
  // ============================================================
  let buscaTimer;
  $(document).on('input', '#campo-busca-global', function () {
    clearTimeout(buscaTimer);
    const val = $(this).val();
    buscaTimer = setTimeout(() => {
      const url = new URL(window.location.href);
      if (val) url.searchParams.set('busca', val);
      else url.searchParams.delete('busca');
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }, 500);
  });

});
