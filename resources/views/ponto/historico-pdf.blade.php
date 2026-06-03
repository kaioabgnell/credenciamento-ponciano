<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Histórico de Ponto — {{ $funcionario->nome }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 11px;
      color: #1a1a2e;
      background: #fff;
      padding: 20px;
    }

    /* ── Cabeçalho ── */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #028fd0;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .header-title {
      font-size: 18px;
      font-weight: 800;
      color: #028fd0;
    }
    .header-sub {
      font-size: 11px;
      color: #666;
      margin-top: 2px;
    }
    .header-meta {
      text-align: right;
      font-size: 10px;
      color: #888;
    }

    /* ── Card funcionário ── */
    .func-card {
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 16px;
      display: flex;
      gap: 20px;
      align-items: flex-start;
    }
    .func-card .fc-field { margin-bottom: 4px; }
    .func-card .fc-label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #888; font-weight: 600; }
    .func-card .fc-valor { font-size: 12px; font-weight: 600; color: #1a1a2e; }

    /* ── Indicadores ── */
    .indicadores {
      display: flex;
      gap: 12px;
      margin-bottom: 16px;
    }
    .ind-box {
      flex: 1;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 10px 14px;
      text-align: center;
    }
    .ind-box .ib-valor { font-size: 20px; font-weight: 800; }
    .ind-box .ib-label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }

    /* ── Tabela ── */
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #028fd0; color: #fff; }
    thead th {
      padding: 7px 8px;
      text-align: left;
      font-size: 9.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .4px;
      white-space: nowrap;
    }
    tbody tr { border-bottom: 1px solid #f0f0f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td {
      padding: 6px 8px;
      font-size: 10.5px;
      vertical-align: middle;
    }

    .badge {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 20px;
      font-size: 9px;
      font-weight: 700;
    }
    .badge-presente  { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .badge-finalizado { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
    .badge-ausente   { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

    .mono { font-family: 'Courier New', monospace; }
    .verde  { color: #16a34a; font-weight: 700; }
    .vermelho { color: #dc2626; font-weight: 700; }
    .azul { color: #028fd0; font-weight: 700; }

    /* ── Rodapé ── */
    .footer {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #e2e8f0;
      font-size: 9px;
      color: #aaa;
      display: flex;
      justify-content: space-between;
    }

    @media print {
      body { padding: 10px; }
      .no-print { display: none !important; }
      @page { margin: 1cm; size: A4 landscape; }
    }
  </style>
</head>
<body>

{{-- ── Botão imprimir (some no print) ── --}}
<div class="no-print" style="margin-bottom:16px; display:flex; gap:10px; align-items:center">
  <button onclick="window.print()"
          style="background:#028fd0; color:#fff; border:none; padding:10px 20px; border-radius:8px;
                 font-size:14px; font-weight:600; cursor:pointer; font-family:inherit">
    🖨 Imprimir / Salvar PDF
  </button>
  <button onclick="window.close()"
          style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:10px 16px;
                 border-radius:8px; font-size:14px; cursor:pointer; font-family:inherit">
    ✕ Fechar
  </button>
  <span style="font-size:12px; color:#888">
    Para salvar como PDF: clique em Imprimir → selecione <strong>Salvar como PDF</strong> → Orientação <strong>Paisagem</strong>
  </span>
</div>

{{-- ── Cabeçalho ── --}}
<div class="header">
  <div>
    <div class="header-title">Histórico de Ponto</div>
    <div class="header-sub">Credenciamento Ponciano — Relatório Individual</div>
  </div>
  <div class="header-meta">
    Gerado em: {{ now()->format('d/m/Y H:i') }}<br>
    Total de registros: {{ $pontos->count() }}
  </div>
</div>

{{-- ── Dados do funcionário ── --}}
<div class="func-card">
  <div style="flex:2">
    <div class="fc-field">
      <div class="fc-label">Funcionário</div>
      <div class="fc-valor" style="font-size:15px">{{ $funcionario->nome }}</div>
    </div>
  </div>
  <div style="flex:1.5">
    <div class="fc-field">
      <div class="fc-label">Empresa</div>
      <div class="fc-valor">{{ $funcionario->empresa?->nome ?? 'Sem empresa' }}</div>
    </div>
  </div>
  <div style="flex:1.5">
    <div class="fc-label">Função / Cargo</div>
    <div class="fc-valor">{{ $funcionario->funcao_cargo }}</div>
  </div>
  <div style="flex:1">
    <div class="fc-label">CPF</div>
    <div class="fc-valor mono">{{ $funcionario->cpf_formatado }}</div>
  </div>
  <div style="flex:1">
    <div class="fc-label">Coordenador</div>
    <div class="fc-valor">{{ $funcionario->coordenador ? '⭐ Sim' : 'Não' }}</div>
  </div>
  <div style="flex:1">
    <div class="fc-label">Status</div>
    <div class="fc-valor">{{ $funcionario->ativo ? '🟢 Ativo' : '🔴 Inativo' }}</div>
  </div>
</div>

{{-- ── Indicadores ── --}}
@php
  $totalSecs = $pontos->whereNotNull('horas_trabalhadas')->reduce(function ($carry, $p) {
      [$h, $m, $s] = array_pad(explode(':', $p->horas_trabalhadas), 3, 0);
      return $carry + ($h * 3600) + ($m * 60) + $s;
  }, 0);
  $totalH = intdiv($totalSecs, 3600);
  $totalM = intdiv($totalSecs % 3600, 60);
  $finalizados = $pontos->where('status', 'finalizado')->count();
  $presentes   = $pontos->where('status', 'presente')->count();
  $diasComPulseira = $pontos->whereNotNull('pulseira')->count();
@endphp

<div class="indicadores">
  <div class="ind-box">
    <div class="ib-valor azul">{{ $pontos->count() }}</div>
    <div class="ib-label">Total Registros</div>
  </div>
  <div class="ind-box">
    <div class="ib-valor" style="color:#16a34a">{{ sprintf('%dh %02dm', $totalH, $totalM) }}</div>
    <div class="ib-label">Horas Totais</div>
  </div>
  <div class="ind-box">
    <div class="ib-valor" style="color:#1e40af">{{ $finalizados }}</div>
    <div class="ib-label">Finalizados</div>
  </div>
  <div class="ind-box">
    <div class="ib-valor" style="color:#d97706">{{ $presentes }}</div>
    <div class="ib-label">Em Aberto</div>
  </div>
  <div class="ind-box">
    <div class="ib-valor" style="color:#7c3aed">{{ $diasComPulseira }}</div>
    <div class="ib-label">Com Pulseira</div>
  </div>
</div>

{{-- ── Tabela ── --}}
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
    </tr>
  </thead>
  <tbody>
    @forelse($pontos as $ponto)
    <tr>
      <td style="font-weight:600; max-width:120px">{{ $funcionario->nome }}</td>
      <td style="max-width:100px">{{ $funcionario->empresa?->nome ?? '—' }}</td>
      <td style="max-width:90px; color:#475569">{{ $funcionario->funcao_cargo }}</td>
      <td style="text-align:center">{{ $funcionario->coordenador ? '⭐' : '—' }}</td>
      <td class="mono" style="white-space:nowrap; font-weight:600">
        {{ $ponto->data?->format('d/m/Y') ?? '—' }}
      </td>
      <td class="mono azul" style="font-weight:700">
        {{ $ponto->pulseira ?? '—' }}
      </td>
      <td class="mono verde">{{ $ponto->entrada ? substr($ponto->entrada, 0, 5) : '—' }}</td>
      <td class="mono vermelho">{{ $ponto->saida ? substr($ponto->saida, 0, 5) : '—' }}</td>
      <td class="mono azul">
        {{ $ponto->horas_trabalhadas ? substr($ponto->horas_trabalhadas, 0, 5) : '—' }}
      </td>
      <td>
        @if($ponto->status === 'presente')
          <span class="badge badge-presente">● Presente</span>
        @elseif($ponto->status === 'finalizado')
          <span class="badge badge-finalizado">✓ Finalizado</span>
        @else
          <span class="badge badge-ausente">— Ausente</span>
        @endif
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="10" style="text-align:center; padding:24px; color:#aaa">
        Nenhum registro de ponto encontrado.
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

{{-- ── Rodapé ── --}}
<div class="footer">
  <span>Credenciamento Ponciano — Relatório gerado em {{ now()->format('d/m/Y \à\s H:i') }}</span>
  <span>{{ $funcionario->nome }} · {{ $pontos->count() }} registro(s)</span>
</div>

<script>
  // Abre o diálogo de impressão automaticamente ao carregar
  window.addEventListener('load', function () {
    // Pequeno delay para garantir que o layout esteja renderizado
    setTimeout(() => window.print(), 400);
  });
</script>

</body>
</html>
