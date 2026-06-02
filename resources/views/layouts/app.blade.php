<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Flash messages para JS --}}
  @if(session('success'))
    <meta name="flash-success" content="{{ session('success') }}">
  @endif
  @if(session('error'))
    <meta name="flash-error" content="{{ session('error') }}">
  @endif

  <title>@yield('title', 'Dashboard') — Credenciamento Ponciano</title>

  {{-- Favicon --}}
  <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

  {{-- Fontes --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

  {{-- CSS Principal --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">

  <style>
    /* Seletor de evento no topbar */
    .topbar-evento {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--roxo-light, #ede9fe);
      border: 1px solid rgba(124,58,237,.3);
      border-radius: 8px;
      padding: 5px 6px 5px 12px;
      font-size: 12px;
      font-weight: 600;
      color: var(--roxo);
      max-width: 260px;
    }
    .topbar-evento .ev-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--roxo);
      flex-shrink: 0;
      animation: pulse-roxo-tb 1.5s infinite;
    }
    @keyframes pulse-roxo-tb {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: .5; transform: scale(1.4); }
    }
    .topbar-evento select {
      background: transparent;
      border: none;
      outline: none;
      font-size: 12px;
      font-weight: 600;
      color: var(--roxo);
      cursor: pointer;
      font-family: var(--font-body);
      max-width: 180px;
    }
    .topbar-evento-vazio {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--amarelo-light);
      border: 1px solid var(--amarelo);
      border-radius: 8px;
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 600;
      color: #92610a;
    }
  </style>

  {{-- Stack de CSS por página --}}
  @stack('styles')
</head>
<body data-pagina="@yield('pagina', '')">

{{-- ============================================================
     SIDEBAR
     ============================================================ --}}
<aside class="sidebar" id="sidebar">

  {{-- Header da Sidebar --}}
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="{{{ URL::to('images/favicon.png') }}}" alt="" >
    </div>
    <div class="sidebar-title">
      Credenciamento
      <span>Eventos Ponciano</span>
    </div>
  </div>

  {{-- Navegação --}}
  <nav class="sidebar-nav">

    <div class="nav-section">Principal</div>

    <a href="{{ route('dashboard') }}"
       class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <span class="nav-icon">⊞</span>
      <span class="nav-label">Dashboard</span>
    </a>

    <a href="{{ route('ponto.registrar') }}"
       class="nav-item {{ request()->routeIs('ponto.registrar') ? 'active' : '' }}">
      <span class="nav-icon">⏱</span>
      <span class="nav-label">Bater Ponto</span>
      @php $presentes = \App\Models\Ponto::hoje()->presentes()->count() @endphp
      @if($presentes > 0)
        <span class="nav-badge">{{ $presentes }}</span>
      @endif
    </a>

    <div class="nav-section">Cadastros</div>

    <a href="{{ route('empresas.index') }}"
       class="nav-item {{ request()->routeIs('empresas.*') ? 'active' : '' }}">
      <span class="nav-label">Empresas</span>
    </a>

    <a href="{{ route('funcionarios.index') }}"
       class="nav-item {{ request()->routeIs('funcionarios.*') ? 'active' : '' }}">
      <span class="nav-label">Funcionários</span>
    </a>

    <a href="{{ route('eventos.index') }}"
       class="nav-item {{ request()->routeIs('eventos.*') ? 'active' : '' }}">
      <span class="nav-label">Eventos</span>
    </a>

    <div class="nav-section">Operacional</div>

    <a href="{{ route('ponto.index') }}"
       class="nav-item {{ request()->routeIs('ponto.index') ? 'active' : '' }}">
      <span class="nav-label">Histórico de Ponto</span>
    </a>

    <a href="{{ route('relatorio.index') }}"
       class="nav-item {{ request()->routeIs('relatorio.index') ? 'active' : '' }}">
      <span class="nav-label">Relatório Geral</span>
    </a>

    <a href="{{ route('relatorio.funcionarios') }}"
       class="nav-item {{ request()->routeIs('relatorio.funcionarios*') ? 'active' : '' }}">
      <span class="nav-label">Relatório por Funcionário</span>
    </a>

    <div class="nav-section">Sistema</div>

    <a href="{{ route('usuarios.index') }}"
       class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
      <span class="nav-icon">🔑</span>
      <span class="nav-label">Usuários</span>
    </a>

    <a href="{{ route('importacoes.index') }}"
       class="nav-item {{ request()->routeIs('importacoes.*') ? 'active' : '' }}">
      <span class="nav-icon">📥</span>
      <span class="nav-label">Importações</span>
    </a>

    {{-- ── Itens visíveis APENAS no mobile (ocultos via CSS no desktop) ── --}}
    @if(auth()->check())

      {{-- Seletor de evento (mobile) --}}
      @php
        $_evsMobile  = \App\Models\Evento::emAndamento()->orderByDesc('id')->get(['id','nome']);
        $_evAtivoMob = session('evento_ativo_id');
      @endphp
      @if($_evsMobile->isNotEmpty())
        <div class="nav-section nav-mobile-only">Evento Ativo</div>
        <div class="nav-mobile-only" style="padding:4px 8px 8px">
          <select onchange="trocarEventoGlobal(this.value)"
                  style="width:100%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);
                         color:#fff;border-radius:8px;padding:9px 12px;font-size:13px;
                         font-family:var(--font-body);cursor:pointer;outline:none">
            @foreach($_evsMobile as $_ev)
              <option value="{{ $_ev->id }}"
                      {{ $_evAtivoMob == $_ev->id ? 'selected' : '' }}
                      style="background:var(--azul-escuro);color:#fff">
                🎪 {{ $_ev->nome }}
              </option>
            @endforeach
          </select>
        </div>
      @endif

      {{-- Logout (mobile) --}}
      <div class="nav-section nav-mobile-only">Conta</div>
      <form method="POST" action="{{ route('logout') }}"
            class="nav-mobile-only" style="margin:1px 8px 8px">
        @csrf
        <button type="submit"
                style="width:100%;background:rgba(229,57,53,.15);border:1px solid rgba(229,57,53,.35);
                       border-radius:8px;color:rgba(255,255,255,.85);padding:10px 18px;
                       display:flex;align-items:center;gap:12px;cursor:pointer;
                       font-family:var(--font-body);font-size:14px;font-weight:500;
                       transition:background .18s">
          <span style="font-size:18px;width:24px;text-align:center">⎋</span>
          <span>Sair do sistema</span>
        </button>
      </form>

    @endif

  </nav>

  {{-- Rodapé: usuário logado --}}
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <img class="sidebar-user-avatar"
           src="{{ auth()->user()->foto_url }}"
           alt="{{ auth()->user()->nome }}">
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ auth()->user()->nome_abreviado }}</div>
        <div class="sidebar-user-cargo">{{ auth()->user()->cargo }}</div>
      </div>
    </div>
  </div>

</aside>

{{-- Overlay mobile --}}
<div class="sidebar-overlay" id="sidebar-overlay"></div>

{{-- ============================================================
     WRAPPER PRINCIPAL
     ============================================================ --}}
<div class="main-wrapper">

  {{-- TOPBAR --}}
  <header class="topbar">
    <button class="topbar-toggle" id="btn-toggle-sidebar" title="Menu">
      ☰
    </button>

    <nav class="topbar-breadcrumb">
      <a href="{{ route('dashboard') }}">Início</a>
      @hasSection('breadcrumb')
        <span class="sep">/</span>
        @yield('breadcrumb')
      @endif
    </nav>

    <div class="topbar-spacer"></div>

    <div class="topbar-actions">

      {{-- Seletor de evento global --}}
      @if(auth()->check())
        @php
          $_eventosTopbar = \App\Models\Evento::emAndamento()->orderByDesc('id')->get(['id', 'nome']);
          $_eventoAtivoId = session('evento_ativo_id');
        @endphp
        @if($_eventosTopbar->isNotEmpty())
          <div class="topbar-evento" title="Evento ativo — todos os dados do sistema usarão este evento">
            <span class="ev-dot"></span>
            <select id="select-evento-global" onchange="trocarEventoGlobal(this.value)">
              @foreach($_eventosTopbar as $_ev)
                <option value="{{ $_ev->id }}" {{ $_eventoAtivoId == $_ev->id ? 'selected' : '' }}>
                  {{ $_ev->nome }}
                </option>
              @endforeach
            </select>
          </div>
        @else
          <div class="topbar-evento-vazio" title="Nenhum evento ativo para hoje">
            ⚠ Sem evento ativo
          </div>
        @endif
      @endif

      {{-- Presentes ao vivo --}}
      <div class="topbar-presentes" title="Funcionários presentes agora">
        <span class="dot"></span>
        <span class="ind-presentes">{{ \App\Models\Ponto::hoje()->presentes()->count() }}</span>
        presentes
      </div>

      {{-- Logout — oculto no mobile (fica na sidebar) --}}
      <form action="{{ route('logout') }}" method="POST" style="margin:0" class="topbar-logout-btn">
        @csrf
        <button type="submit" class="topbar-btn" title="Sair">
          ⎋ Sair
        </button>
      </form>

    </div>
  </header>

  {{-- CONTEÚDO --}}
  <main class="main-content">
    @yield('content')
  </main>

</div>

{{-- ============================================================
     SCRIPTS
     ============================================================ --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>

<script>
// ── Troca o evento ativo da sessão e recarrega a página ──────────
function trocarEventoGlobal(eventoId) {
  if (!eventoId) return;
  $.post('{{ route("api.evento.sessao") }}', {
    evento_id: eventoId,
    _token:    $('meta[name="csrf-token"]').attr('content'),
  })
  .done(function () {
    window.location.reload();
  })
  .fail(function () {
    // Reverte o select ao valor anterior em caso de erro
    $('#select-evento-global').val('{{ session("evento_ativo_id") }}');
  });
}
</script>

@stack('scripts')

</body>
</html>
