<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>SLA estourado na cobertura municipal</title>
</head>

<body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;color:#111827;padding:24px">
    <div style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
        <div style="padding:20px 24px;background:#7f1d1d;color:#ffffff">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85">Central Executiva</div>
            <h1 style="margin:8px 0 0;font-size:22px">SLA de resolução estourado</h1>
            <div style="margin-top:8px;font-size:13px;opacity:.82">
                {{ $payload['generated_at']->format('d/m/Y H:i') }}
            </div>
        </div>

        <div style="padding:24px">
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6">
                Foram detectados alertas de cobertura ainda ativos que ultrapassaram o SLA operacional de resolução.
            </p>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px">
                @foreach ([['label' => 'Alertas', 'value' => $payload['summary']['total'], 'color' => '#111827'], ['label' => 'Alta severidade', 'value' => $payload['summary']['high'], 'color' => '#b91c1c'], ['label' => 'Municípios', 'value' => $payload['summary']['municipalities'], 'color' => '#1d4ed8'], ['label' => 'Maior atraso', 'value' => number_format($payload['summary']['max_overdue_hours'], 1, ',', '.') . 'h', 'color' => '#b45309']] as $card)
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;background:#f9fafb">
                        <div style="font-size:20px;font-weight:700;color:{{ $card['color'] }}">{{ $card['value'] }}</div>
                        <div style="margin-top:4px;font-size:12px;color:#6b7280">{{ $card['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#f3f4f6">
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Município</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Frente</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Alerta</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Meta</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Abertura</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Atraso</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['breaches'] as $breach)
                        <tr>
                            <td style="padding:10px;border:1px solid #e5e7eb">{{ $breach['municipality_name'] }}</td>
                            <td style="padding:10px;border:1px solid #e5e7eb">{{ $breach['event_label'] }}</td>
                            <td style="padding:10px;border:1px solid #e5e7eb">
                                <div style="font-weight:700">{{ $breach['title'] }}</div>
                                <div style="margin-top:4px;color:#6b7280">{{ $breach['message'] }}</div>
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">{{ $breach['target_hours'] }}h</td>
                            <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">{{ number_format($breach['hours_open'], 1, ',', '.') }}h</td>
                            <td style="padding:10px;border:1px solid #e5e7eb;text-align:center;color:#b91c1c;font-weight:700">
                                +{{ number_format($breach['hours_overdue'], 1, ',', '.') }}h
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
