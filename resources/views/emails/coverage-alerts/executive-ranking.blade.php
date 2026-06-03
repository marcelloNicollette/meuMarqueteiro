<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Ranking executivo de cobertura</title>
</head>

<body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;color:#111827;padding:24px">
    <div
        style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
        <div
            style="padding:20px 24px;background:{{ data_get($payload, 'identity.accent_color', '#111827') }};color:#ffffff">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85">
                {{ data_get($payload, 'identity.department', 'Central Executiva') }}</div>
            <h1 style="margin:8px 0 0;font-size:22px">Ranking executivo periódico</h1>
            <div style="margin-top:6px;font-size:13px;font-weight:700">
                {{ data_get($payload, 'identity.institution_name', 'Meu Assistente') }}</div>
            <div style="margin-top:8px;font-size:13px;opacity:.82">
                {{ $payload['generated_at']->format('d/m/Y H:i') }} · {{ strtoupper($payload['period'] ?? 'manual') }}
            </div>
        </div>

        <div style="padding:24px">
            <p style="margin:0 0 16px;font-size:14px;line-height:1.6">
                Segue a leitura gerencial da cobertura municipal com score executivo, tendência recente e anexo em PDF
                para compartilhamento executivo.
            </p>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:20px">
                @foreach ([['label' => 'Score executivo médio', 'value' => ($payload['summary']['average_executive_score'] ?? 0) . '%', 'color' => '#1d4ed8'], ['label' => 'Alertas ativos', 'value' => $payload['summary']['active_alerts'] ?? 0, 'color' => '#b45309'], ['label' => 'Breaches SLA', 'value' => $payload['summary']['sla_breaches_total'] ?? 0, 'color' => '#b91c1c'], ['label' => 'Municípios no ranking', 'value' => $payload['ranking_summary']['tracked'] ?? 0, 'color' => '#111827']] as $card)
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;background:#f9fafb">
                        <div style="font-size:20px;font-weight:700;color:{{ $card['color'] }}">{{ $card['value'] }}
                        </div>
                        <div style="margin-top:4px;font-size:12px;color:#6b7280">{{ $card['label'] }}</div>
                    </div>
                @endforeach
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:#f3f4f6">
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Pos.</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:left">Município</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Score</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">Delta</th>
                        <th style="padding:10px;border:1px solid #e5e7eb;text-align:center">SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (collect($payload['ranking'] ?? [])->take(8) as $row)
                        <tr>
                            <td style="padding:10px;border:1px solid #e5e7eb">{{ $row['position'] }}</td>
                            <td style="padding:10px;border:1px solid #e5e7eb">{{ $row['municipality_name'] }}</td>
                            <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">
                                {{ $row['executive_score'] }}</td>
                            <td
                                style="padding:10px;border:1px solid #e5e7eb;text-align:center;color:{{ ($row['executive_score_delta'] ?? 0) >= 0 ? '#166534' : '#b91c1c' }}">
                                {{ ($row['executive_score_delta'] ?? 0) > 0 ? '+' : '' }}{{ $row['executive_score_delta'] ?? 0 }}
                            </td>
                            <td style="padding:10px;border:1px solid #e5e7eb;text-align:center">
                                {{ $row['sla_breaches_total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:20px">
                @foreach (collect($payload['signatures'] ?? [])->take(2) as $signature)
                    <div style="padding:14px 16px;border:1px solid #e5e7eb;border-radius:12px;background:#f9fafb">
                        <div style="font-size:13px;font-weight:700">
                            {{ $signature['name'] ?: 'Assinatura não definida' }}</div>
                        <div style="margin-top:4px;font-size:12px;color:#6b7280">
                            {{ $signature['role'] ?: 'Cargo não definido' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</body>

</html>
