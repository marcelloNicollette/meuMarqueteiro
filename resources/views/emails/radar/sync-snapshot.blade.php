@php
    $summary = $snapshot['summary'];
    $periodLabel = $snapshot['period'] === 'weekly' ? 'Semanal' : 'Diário';
    $windowLabel = $snapshot['started_at']->format('d/m/Y H:i') . ' até ' . $snapshot['ended_at']->format('d/m/Y H:i');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Snapshot {{ $periodLabel }} do Radar</title>
</head>

<body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;color:#111827;padding:24px">
    <div style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
        <div style="padding:20px 24px;background:#0f172a;color:#ffffff">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85">Radar de Recursos</div>
            <h1 style="margin:8px 0 0;font-size:22px">Snapshot {{ $periodLabel }} operacional</h1>
            <div style="margin-top:8px;font-size:13px;opacity:.82">{{ $windowLabel }}</div>
        </div>

        <div style="padding:24px">
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6">
                Segue o resumo operacional do sync do Radar com foco em falhas, stale e reenfileiramentos.
                Os anexos incluem o histórico auditável e o resumo por município em <strong>CSV</strong> e <strong>XLSX</strong>.
            </p>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px">
                @foreach ([['label' => 'Execuções', 'value' => $summary['total'], 'color' => '#111827'], ['label' => 'Falhas', 'value' => $summary['failed'], 'color' => '#b91c1c'], ['label' => 'Stale', 'value' => $summary['stale'], 'color' => '#b45309'], ['label' => 'Retries', 'value' => $summary['retried'], 'color' => '#3730a3']] as $card)
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;background:#f9fafb">
                        <div style="font-size:20px;font-weight:700;color:{{ $card['color'] }}">{{ $card['value'] }}</div>
                        <div style="margin-top:4px;font-size:12px;color:#6b7280">{{ $card['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="margin-bottom:20px">
                <h2 style="margin:0 0 10px;font-size:16px;color:#111827">Leitura rápida</h2>
                <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.7;color:#374151">
                    <li>{{ $summary['success'] }} execuções concluíram com sucesso.</li>
                    <li>{{ $summary['queued'] }} ficaram na fila e {{ $summary['running'] }} permaneceram em execução no fechamento da janela.</li>
                    <li>{{ count($snapshot['municipality_summary_rows']) }} município(s) tiveram movimentação operacional no período.</li>
                </ul>
            </div>

            <div>
                <h2 style="margin:0 0 10px;font-size:16px;color:#111827">Municípios com maior volume no período</h2>
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="background:#f3f4f6">
                            <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Município</th>
                            <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Execuções</th>
                            <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Falhas</th>
                            <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Stale</th>
                            <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Último motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (collect($snapshot['municipality_summary_rows'])->take(8) as $row)
                            <tr>
                                <td style="padding:10px;border:1px solid #e5e7eb">{{ $row[0] }}</td>
                                <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">{{ $row[1] }}</td>
                                <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">{{ $row[3] }}</td>
                                <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">{{ $row[6] }}</td>
                                <td style="padding:10px;border:1px solid #e5e7eb">{{ $row[10] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:12px;border:1px solid #e5e7eb;text-align:center;color:#6b7280">
                                    Nenhuma execução do Radar foi encontrada para a janela informada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
