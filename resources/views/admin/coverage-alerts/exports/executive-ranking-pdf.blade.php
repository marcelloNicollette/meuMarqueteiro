<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Ranking executivo de cobertura</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            margin: 0;
            background: #f8fafc;
        }

        h1,
        h2,
        h3 {
            margin: 0;
        }

        .muted {
            color: #6b7280;
        }

        .header {
            background: {{ data_get($payload, 'identity.accent_color', '#111827') }};
            color: #ffffff;
            padding: 28px 30px 24px;
        }

        .header-table {
            width: 100%;
        }

        .header-logo {
            width: 78px;
            text-align: right;
        }

        .container {
            padding: 24px 28px 28px;
        }

        .subtitle {
            color: rgba(255, 255, 255, .84);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .tagline {
            color: rgba(255, 255, 255, .82);
            font-size: 11px;
            margin-top: 8px;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 18px;
        }

        .summary-grid td {
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            vertical-align: top;
        }

        .card-label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
        }

        .card-value {
            font-size: 20px;
            font-weight: bold;
            margin-top: 6px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        table.report th,
        table.report td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
        }

        table.report th {
            background: #f3f4f6;
            text-align: left;
        }

        .section {
            margin-top: 22px;
        }

        .highlight {
            border-left: 4px solid {{ data_get($payload, 'identity.secondary_color', '#1d4ed8') }};
            background: #ffffff;
            padding: 10px 12px;
            margin-top: 12px;
        }

        .signature-grid {
            width: 100%;
            margin-top: 24px;
        }

        .signature-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 18px;
        }

        .signature-line {
            border-top: 1px solid #d1d5db;
            margin-top: 36px;
            padding-top: 8px;
        }

        .footer-note {
            font-size: 10px;
            color: #6b7280;
            margin-top: 16px;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="subtitle">
                        {{ data_get($payload, 'identity.department', 'Central Executiva de Cobertura') }}</div>
                    <h1>{{ data_get($payload, 'identity.institution_name', 'Meu Marqueteiro') }}</h1>
                    <div style="margin-top:8px;font-size:14px;font-weight:bold">Ranking executivo periódico</div>
                    <div class="tagline">{{ data_get($payload, 'identity.tagline', '') }}</div>
                    <div style="margin-top:10px;font-size:11px;opacity:.86">
                        Gerado em {{ $payload['generated_at']->format('d/m/Y H:i') }} · período
                        {{ strtoupper($payload['period'] ?? 'manual') }}
                    </div>
                </td>
                <td class="header-logo">
                    @if (data_get($payload, 'identity.logo_absolute_path'))
                        <img src="{{ data_get($payload, 'identity.logo_absolute_path') }}" alt="Logo institucional"
                            style="max-width:72px;max-height:72px;">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="container">
        <table class="summary-grid" cellspacing="10">
            <tr>
                <td>
                    <div class="card-label">Score Executivo Médio</div>
                    <div class="card-value">{{ $payload['summary']['average_executive_score'] ?? 0 }}%</div>
                </td>
                <td>
                    <div class="card-label">Alertas Ativos</div>
                    <div class="card-value">{{ $payload['summary']['active_alerts'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="card-label">Breaches SLA</div>
                    <div class="card-value">{{ $payload['summary']['sla_breaches_total'] ?? 0 }}</div>
                </td>
                <td>
                    <div class="card-label">Municípios no Ranking</div>
                    <div class="card-value">{{ $payload['ranking_summary']['tracked'] ?? 0 }}</div>
                </td>
            </tr>
        </table>

        <div class="section">
            <h2>Resumo temporal</h2>
            <div class="highlight">
                Delta do score executivo médio:
                {{ (($payload['temporal_comparison']['deltas']['average_executive_score'] ?? 0) > 0 ? '+' : '') . ($payload['temporal_comparison']['deltas']['average_executive_score'] ?? 0) }}
                · Delta dos alertas ativos:
                {{ (($payload['temporal_comparison']['deltas']['active_alerts'] ?? 0) > 0 ? '+' : '') . ($payload['temporal_comparison']['deltas']['active_alerts'] ?? 0) }}
                @if (!empty(data_get($payload, 'approval.approved_by_name')))
                    <br>
                    Aprovação registrada por {{ data_get($payload, 'approval.approved_by_name') }} ·
                    {{ data_get($payload, 'approval.approved_by_role', 'Admin') }}
                    em
                    {{ \Illuminate\Support\Carbon::parse(data_get($payload, 'approval.approved_at'))->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>

        <div class="section">
            <h2>Ranking executivo</h2>
            <table class="report">
                <thead>
                    <tr>
                        <th>Pos.</th>
                        <th>Município</th>
                        <th>Score executivo</th>
                        <th>Delta</th>
                        <th>Posição anterior</th>
                        <th>Config.</th>
                        <th>Reinc.</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['ranking'] as $row)
                        <tr>
                            <td>{{ $row['position'] }}</td>
                            <td>{{ $row['municipality_name'] }}</td>
                            <td>{{ $row['executive_score'] }}</td>
                            <td>{{ (($row['executive_score_delta'] ?? 0) > 0 ? '+' : '') . ($row['executive_score_delta'] ?? 0) }}
                            </td>
                            <td>{{ $row['previous_position'] ?? '—' }}</td>
                            <td>{{ $row['score'] }}%</td>
                            <td>{{ $row['recurrence_30d'] }}</td>
                            <td>{{ $row['sla_breaches_total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Curva de melhora/piora</h2>
            <table class="report">
                <thead>
                    <tr>
                        <th>Município</th>
                        <th>Primeiro score</th>
                        <th>Último score</th>
                        <th>Delta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payload['improvement_curve'] as $entry)
                        <tr>
                            <td>{{ $entry['municipality_name'] }}</td>
                            <td>{{ $entry['first_score'] }}</td>
                            <td>{{ $entry['last_score'] }}</td>
                            <td>{{ ($entry['delta'] > 0 ? '+' : '') . $entry['delta'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <table class="signature-grid">
            <tr>
                @foreach (collect($payload['signatures'] ?? [])->take(2) as $signature)
                    <td>
                        <div class="signature-line">
                            <div style="font-weight:bold">{{ $signature['name'] ?: 'Assinatura não definida' }}</div>
                            <div class="muted">{{ $signature['role'] ?: 'Cargo não definido' }}</div>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>

        <div class="footer-note">
            Documento executivo gerado pela central institucional de cobertura para distribuição gerencial e trilha de
            governança.
        </div>
    </div>
</body>

</html>
