<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Histórico de Ponto — {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 20px; }

    .header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #028fd0; padding-bottom:12px; margin-bottom:14px; }
    .header-title { font-size:18px; font-weight:800; color:#028fd0; }
    .header-sub { font-size:11px; color:#666; margin-top:2px; }
    .header-meta { text-align:right; font-size:10px; color:#888; }

    .filtros-resumo { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:8px 14px; margin-bottom:14px; display:flex; gap:20px; flex-wrap:wrap; font-size:11px; }
    .filtros-resumo .fr-item { display:flex; gap:6px; align-items:center; }
    .filtros-resumo .fr-label { color:#888; font-weight:600; }
    .filtros-resumo .fr-valor { color:#1a1a2e; font-weight:700; }

    .indicadores { display:flex; gap:12px; margin-bottom:14px; }
    .ind-box { flex:1; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; text-align:center; }
    .ind-box .ib-valor { font-size:18px; font-weight:800; }
    .ind-box .ib-label { font-size:9px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }

    table { width:100%; border-collapse:collapse; }
    thead tr { background:#028fd0; color:#fff; }
    thead th { padding:6px 7px; text-align:left; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f0; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:5px 7px; font-size:10px; vertical-align:middle; }

    .badge { display:inline-block; padding:1px 7px; border-radius:20px; font-size:9px; font-weight:700; }
    .badge-presente  { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
    .badge-finalizado { background:#dbeafe; color:#1e40af; border:1px solid #93c5fd; }
    .badge-ausente   { background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; }

    .mono { font-family:'Courier New', monospace; }
    .verde { color:#16a34a; font-weight:700; }
    .vermelho { color:#dc2626; font-weight:700; }
    .azul { color:#028fd0; font-weight:700; }

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
    <div class="header-title">Histórico de Ponto</div>
    <div class="header-sub">Credenciamento Ponciano — Listagem de Registros</div>
  </div>
  <div class="header-meta">
    Gerado em: {{ now()->format('d/m/Y H:i') }}<br>
    Total: {{ $pontos->count() }} registro(s)
  </div>
</div>

{{-- Filtros aplicados --}}
<div class="filtros-resumo">
  <div class="fr-item"><span class="fr-label">Data:</span><span class="fr-valor">{{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</span></div>
  @if($eventoNome)
  <div class="fr-item"><span class="fr-label">Evento:</span><span class="fr-valor">{{ $eventoNome }}</span></div>
  @endif
  @if($empresaNome)
  <div class="fr-item"><span class="fr-label">Empresa:</span><span class="fr-valor">{{ $empresaNome }}</span></div>
  @endif
  @if($status)
  <div class="fr-item"><span class="fr-label">Status:</span><span class="fr-valor">{{ ucfirst($status) }}</span></div>
  @endif
  @if($busca)
  <div class="fr-item"><span class="fr-label">Busca:</span><span class="fr-valor">"{{ $busca }}"</span></div>
  @endif
</div>

{{-- Indicadores --}}
@php
  $presentes   = $pontos->where('status', 'presente')->count();
  $finalizados = $pontos->where('status', 'finalizado')->count();
  $totalSecs   = $pontos->whereNotNull('horas_trabalhadas')->reduce(function($c, $p) {
      [$h,$m,$s] = array_pad(explode(':', $p->horas_trabalhadas), 3, 0);
      return $c + ($h*3600)+($m*60)+$s;
  }, 0);
  $tH = intdiv($totalSecs,3600); $tM = intdiv($totalSecs%3600,60);
@endphp
<div class="indicadores">
  <div class="ind-box"><div class="ib-valor azul">{{ $pontos->count() }}</div><div class="ib-label">Total</div></div>
  <div class="ind-box"><div class="ib-valor" style="color:#16a34a">{{ $presentes }}</div><div class="ib-label">Presentes</div></div>
  <div class="ind-box"><div class="ib-valor" style="color:#1e40af">{{ $finalizados }}</div><div class="ib-label">Finalizados</div></div>
  <div class="ind-box"><div class="ib-valor" style="color:#028fd0">{{ sprintf('%dh %02dm',$tH,$tM) }}</div><div class="ib-label">Total Horas</div></div>
</div>

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
      <td style="font-weight:600; max-width:130px">{{ $ponto->funcionario->nome }}</td>
      <td style="max-width:110px">{{ $ponto->empresa?->nome ?? '—' }}</td>
      <td style="color:#475569; max-width:90px">{{ $ponto->funcionario->funcao_cargo }}</td>
      <td style="text-align:center">{{ $ponto->funcionario->coordenador ? '⭐' : '—' }}</td>
      <td class="mono" style="white-space:nowrap; font-weight:600">{{ $ponto->data?->format('d/m/Y') ?? '—' }}</td>
      <td class="mono azul" style="font-weight:700">{{ $ponto->pulseira ?? '—' }}</td>
      <td class="mono verde">{{ $ponto->entrada ? substr($ponto->entrada,0,5) : '—' }}</td>
      <td class="mono vermelho">{{ $ponto->saida ? substr($ponto->saida,0,5) : '—' }}</td>
      <td class="mono azul">{{ $ponto->horas_trabalhadas ? substr($ponto->horas_trabalhadas,0,5) : '—' }}</td>
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
    <tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa">Nenhum registro encontrado.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  <span>Credenciamento Ponciano — {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</span>
  <span>{{ $pontos->count() }} registro(s) exportado(s)</span>
</div>

<script>setTimeout(() => window.print(), 400);</script>
</body>
</html>
