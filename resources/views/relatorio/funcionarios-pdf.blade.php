<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Relatório por Funcionário</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 20px; }

    .header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #028fd0; padding-bottom:12px; margin-bottom:14px; }
    .header-title { font-size:18px; font-weight:800; color:#028fd0; }
    .header-sub { font-size:11px; color:#666; margin-top:2px; }
    .header-meta { text-align:right; font-size:10px; color:#888; }

    .filtros-resumo { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:8px 14px; margin-bottom:14px; display:flex; gap:20px; flex-wrap:wrap; font-size:11px; }
    .fr-label { color:#888; font-weight:600; margin-right:4px; }
    .fr-valor { color:#1a1a2e; font-weight:700; }

    .indicadores { display:flex; gap:12px; margin-bottom:14px; }
    .ind-box { flex:1; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; text-align:center; }
    .ind-box .ib-valor { font-size:18px; font-weight:800; }
    .ind-box .ib-label { font-size:9px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }

    table { width:100%; border-collapse:collapse; }
    thead tr { background:#028fd0; color:#fff; }
    thead th { padding:6px 8px; text-align:left; font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    thead th.center { text-align:center; }
    tbody tr { border-bottom:1px solid #f0f0f0; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:6px 8px; font-size:10.5px; vertical-align:middle; }
    tbody td.center { text-align:center; }

    .mono { font-family:'Courier New', monospace; }
    .azul { color:#028fd0; font-weight:700; }
    .cinza { color:#64748b; }

    .badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:9px; font-weight:700; }
    .badge-coord { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }

    .footer { margin-top:16px; padding-top:8px; border-top:1px solid #e2e8f0; font-size:9px; color:#aaa; display:flex; justify-content:space-between; }

    @media print {
      body { padding:8px; }
      .no-print { display:none !important; }
      @page { margin:.8cm; size: A4 landscape; }
    }
  </style>
</head>
<body>

<div class="no-print" style="margin-bottom:14px; display:flex; gap:10px; align-items:center">
  <button onclick="window.print()"
          style="background:#028fd0;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit">
    🖨 Imprimir / Salvar PDF
  </button>
  <button onclick="window.close()"
          style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:10px 16px;border-radius:8px;font-size:14px;cursor:pointer;font-family:inherit">
    ✕ Fechar
  </button>
  <span style="font-size:12px;color:#888">Orientação <strong>Paisagem</strong> recomendada</span>
</div>

<div class="header">
  <div>
    <div class="header-title">Relatório por Funcionário</div>
    <div class="header-sub">Credenciamento Ponciano — Levantamento de Horas e Presenças</div>
  </div>
  <div class="header-meta">
    Gerado em: {{ now()->format('d/m/Y H:i') }}<br>
    Total: {{ $funcionarios->count() }} funcionário(s)
  </div>
</div>

{{-- Filtros aplicados --}}
<div class="filtros-resumo">
  <span><span class="fr-label">Período:</span><span class="fr-valor">{{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</span></span>
  @if($eventoNome)
  <span><span class="fr-label">Evento:</span><span class="fr-valor">{{ $eventoNome }}</span></span>
  @endif
  @if($empresaNome)
  <span><span class="fr-label">Empresa:</span><span class="fr-valor">{{ $empresaNome }}</span></span>
  @endif
  @if($busca)
  <span><span class="fr-label">Busca:</span><span class="fr-valor">"{{ $busca }}"</span></span>
  @endif
</div>

<table>
  <thead>
    <tr>
      <th>Funcionário</th>
      <th>Empresa</th>
      <th>Função</th>
      <th class="center">Coord.</th>
      <th class="center">Dias</th>
      <th class="center">Entradas</th>
      <th class="center">Saídas</th>
      <th class="center">Total Horas</th>
      <th class="center">Média/Turno</th>
    </tr>
  </thead>
  <tbody>
    @forelse($funcionarios as $func)
    @php
      $secs    = (int) ($func->total_segundos ?? 0);
      $entrs   = (int) ($func->total_entradas  ?? 0);
      $mediaSc = $entrs > 0 ? intdiv($secs, $entrs) : 0;
      $hT = intdiv($secs,   3600); $mT = intdiv($secs   % 3600, 60);
      $hM = intdiv($mediaSc,3600); $mM = intdiv($mediaSc % 3600, 60);
    @endphp
    <tr>
      <td style="font-weight:700; max-width:130px">
        {{ $func->nome }}
        @if($func->cpf)
          <div class="mono cinza" style="font-size:9px; font-weight:400">
            {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $func->cpf) }}
          </div>
        @endif
      </td>
      <td style="max-width:110px">{{ $func->empresa?->nome ?? '—' }}</td>
      <td class="cinza" style="max-width:100px">{{ $func->funcao_cargo }}</td>
      <td class="center">
        @if($func->coordenador)
          <span class="badge badge-coord">⭐ Sim</span>
        @else
          <span class="cinza">—</span>
        @endif
      </td>
      <td class="center mono" style="font-weight:700">{{ $func->dias_trabalhados ?? 0 }}</td>
      <td class="center mono" style="font-weight:700; color:#16a34a">{{ $entrs }}</td>
      <td class="center mono" style="font-weight:700; color:#dc2626">{{ (int)($func->total_saidas ?? 0) }}</td>
      <td class="center mono azul" style="font-weight:800; font-size:12px">{{ $hT }}h {{ str_pad($mT,2,'0',STR_PAD_LEFT) }}m</td>
      <td class="center mono cinza">{{ $hM }}h {{ str_pad($mM,2,'0',STR_PAD_LEFT) }}m</td>
    </tr>
    @empty
    <tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa">Nenhum funcionário encontrado.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <span>Credenciamento Ponciano — Relatório por Funcionário — {{ now()->format('d/m/Y') }}</span>
  <span>{{ $funcionarios->count() }} funcionário(s) · Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</span>
</div>

<script>setTimeout(() => window.print(), 400);</script>
</body>
</html>
