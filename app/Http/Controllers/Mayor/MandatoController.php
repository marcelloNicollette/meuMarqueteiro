<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\MandateAction;
use App\Models\MandateAxis;
use App\Models\MandatePromise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MandatoController extends Controller
{
    // ── Painel principal ─────────────────────────────────────────────────

    public function index()
    {
        $municipality = auth()->user()->municipality;

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)])
            ->get();

        // KPIs globais
        $allPromises    = $axes->flatMap->promises;
        $totalPromises  = $allPromises->count();
        $globalScore    = $totalPromises > 0
            ? (int) round($allPromises->avg('score'))
            : 0;
        $plenas         = $allPromises->where('status', 'fulfilled')->count();
        $pendentes      = $allPromises->where('status', 'pending')->count();

        $totalActions   = MandateAction::where('municipality_id', $municipality->id)->count();
        $concludedActions = MandateAction::where('municipality_id', $municipality->id)
            ->where('status', 'concluido')->count();

        // Ações recentes
        $recentActions = MandateAction::where('municipality_id', $municipality->id)
            ->with(['axis', 'promises'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('mayor.mandato.painel', compact(
            'axes',
            'globalScore',
            'totalPromises',
            'plenas',
            'pendentes',
            'totalActions',
            'concludedActions',
            'recentActions',
            'municipality'
        ));
    }

    // ── Drill-down de um eixo ─────────────────────────────────────────

    public function eixo($axisId)
    {
        $municipality = auth()->user()->municipality;

        $axis = MandateAxis::where('municipality_id', $municipality->id)
            ->with(['promises.actions'])
            ->findOrFail($axisId);

        return view('mayor.mandato.eixo', compact('axis', 'municipality'));
    }

    // ── CRUD de Ações ────────────────────────────────────────────────

    public function acoes(Request $request)
    {
        $municipality = auth()->user()->municipality;

        $query = MandateAction::where('municipality_id', $municipality->id)
            ->with(['axis', 'promises'])
            ->orderByDesc('created_at');

        if ($request->filled('axis')) {
            $query->where('mandate_axis_id', $request->axis);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $actions = $query->paginate(20)->withQueryString();

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('mayor.mandato.acoes', compact('actions', 'axes', 'municipality'));
    }

    public function createAcao()
    {
        $municipality = auth()->user()->municipality;

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();

        return view('mayor.mandato.acao-create', compact('axes', 'municipality'));
    }

    public function storeAcao(Request $request)
    {
        $municipality = auth()->user()->municipality;

        $data = $request->validate([
            'mandate_axis_id'       => 'required|exists:mandate_axes,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'secretaria'            => 'nullable|string|max:255',
            'status'                => 'required|in:planejado,em_andamento,concluido,suspenso',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date|after_or_equal:start_date',
            'physical_progress'     => 'nullable|integer|min:0|max:100',
            'investment'            => 'nullable|numeric|min:0',
            'funding_source'        => 'nullable|string|max:255',
            'region'                => 'nullable|string|max:255',
            'beneficiaries'         => 'nullable|integer|min:0',
            'proof_url'             => 'nullable|url|max:500',
            'is_public'             => 'boolean',
            'promises'              => 'nullable|array',
            'promises.*.id'         => 'exists:mandate_promises,id',
            'promises.*.level'      => 'integer|in:0,25,50,75,100',
            'promises.*.justification' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $municipality, $request) {
            $action = MandateAction::create(array_merge(
                $data,
                ['municipality_id' => $municipality->id, 'is_public' => $request->boolean('is_public')]
            ));

            // Vincular promessas com nível de atendimento
            if (!empty($data['promises'])) {
                foreach ($data['promises'] as $p) {
                    $action->promises()->attach($p['id'], [
                        'fulfillment_level'         => $p['level'] ?? 0,
                        'fulfillment_justification' => $p['justification'] ?? null,
                    ]);

                    // Recalcular score da promessa
                    MandatePromise::find($p['id'])?->recalculateScore();
                }
            }
        });

        return redirect()->route('mayor.mandato.painel')
            ->with('success', 'Ação cadastrada com sucesso.');
    }

    public function editAcao($id)
    {
        $municipality = auth()->user()->municipality;

        $action = MandateAction::where('municipality_id', $municipality->id)
            ->with('promises')
            ->findOrFail($id);

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->with(['promises' => fn($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();

        return view('mayor.mandato.acao-edit', compact('action', 'axes', 'municipality'));
    }

    public function updateAcao(Request $request, $id)
    {
        $municipality = auth()->user()->municipality;
        $action = MandateAction::where('municipality_id', $municipality->id)->findOrFail($id);

        $data = $request->validate([
            'mandate_axis_id'       => 'required|exists:mandate_axes,id',
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'secretaria'            => 'nullable|string|max:255',
            'status'                => 'required|in:planejado,em_andamento,concluido,suspenso',
            'start_date'            => 'nullable|date',
            'end_date'              => 'nullable|date',
            'physical_progress'     => 'nullable|integer|min:0|max:100',
            'investment'            => 'nullable|numeric|min:0',
            'funding_source'        => 'nullable|string|max:255',
            'region'                => 'nullable|string|max:255',
            'beneficiaries'         => 'nullable|integer|min:0',
            'proof_url'             => 'nullable|url|max:500',
            'is_public'             => 'boolean',
            'promises'              => 'nullable|array',
        ]);

        DB::transaction(function () use ($action, $data, $request) {
            $action->update(array_merge($data, ['is_public' => $request->boolean('is_public')]));

            // Pegar promessas antigas para recalcular depois
            $oldPromiseIds = $action->promises()->pluck('mandate_promises.id')->toArray();

            // Reatribuir promessas
            $action->promises()->detach();
            $newPromiseIds = [];

            if (!empty($data['promises'])) {
                foreach ($data['promises'] as $p) {
                    $action->promises()->attach($p['id'], [
                        'fulfillment_level'         => $p['level'] ?? 0,
                        'fulfillment_justification' => $p['justification'] ?? null,
                    ]);
                    $newPromiseIds[] = $p['id'];
                }
            }

            // Recalcular scores de todas as promessas afetadas
            foreach (array_unique(array_merge($oldPromiseIds, $newPromiseIds)) as $pid) {
                MandatePromise::find($pid)?->recalculateScore();
            }
        });

        return redirect()->route('mayor.mandato.painel')
            ->with('success', 'Ação atualizada com sucesso.');
    }

    public function destroyAcao($id)
    {
        $municipality = auth()->user()->municipality;
        $action = MandateAction::where('municipality_id', $municipality->id)->findOrFail($id);

        $promiseIds = $action->promises()->pluck('mandate_promises.id')->toArray();
        $action->delete();

        foreach ($promiseIds as $pid) {
            MandatePromise::find($pid)?->recalculateScore();
        }

        return back()->with('success', 'Ação removida.');
    }

    // ── CRUD de Eixos ────────────────────────────────────────────────

    public function eixos()
    {
        $municipality = auth()->user()->municipality;

        $axes = MandateAxis::where('municipality_id', $municipality->id)
            ->orderBy('order')
            ->withCount('promises')
            ->get();

        return view('mayor.mandato.eixos', compact('axes', 'municipality'));
    }

    public function storeEixo(Request $request)
    {
        $municipality = auth()->user()->municipality;

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:20',
        ]);

        $maxOrder = MandateAxis::where('municipality_id', $municipality->id)->max('order') ?? 0;

        MandateAxis::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'order'           => $maxOrder + 1,
        ]));

        return back()->with('success', 'Eixo criado.');
    }

    public function updateEixo(Request $request, $id)
    {
        $municipality = auth()->user()->municipality;
        $axis = MandateAxis::where('municipality_id', $municipality->id)->findOrFail($id);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'color'       => 'nullable|string|max:20',
        ]);

        $axis->update($data);
        return back()->with('success', 'Eixo atualizado.');
    }

    public function destroyEixo($id)
    {
        $municipality = auth()->user()->municipality;
        $axis = MandateAxis::where('municipality_id', $municipality->id)->findOrFail($id);
        $axis->delete();
        return back()->with('success', 'Eixo removido.');
    }

    // ── CRUD de Promessas ─────────────────────────────────────────────

    public function storePromise(Request $request)
    {
        $municipality = auth()->user()->municipality;

        $data = $request->validate([
            'mandate_axis_id' => 'required|exists:mandate_axes,id',
            'text'            => 'required|string',
        ]);

        $maxOrder = MandatePromise::where('mandate_axis_id', $data['mandate_axis_id'])->max('order') ?? 0;

        MandatePromise::create(array_merge($data, [
            'municipality_id' => $municipality->id,
            'order'           => $maxOrder + 1,
        ]));

        return back()->with('success', 'Promessa adicionada.');
    }

    public function seedDefaultAxes()
    {
        $municipality = auth()->user()->municipality;

        // Não duplicar se já existem eixos
        if (MandateAxis::where('municipality_id', $municipality->id)->exists()) {
            return back()->with('success', 'Eixos já configurados.');
        }

        $defaults = [
            ['icon' => '🏥', 'name' => 'Saúde',                            'description' => 'UBS/UPA · Saúde da família · Saúde mental · Vigilância'],
            ['icon' => '🎓', 'name' => 'Educação',                          'description' => 'Creches · Ensino fundamental · Valorização docente · Infraestrutura'],
            ['icon' => '🚌', 'name' => 'Mobilidade e Infraestrutura',       'description' => 'Transporte público · Pavimentação · Ciclovias · Iluminação LED'],
            ['icon' => '🌿', 'name' => 'Meio Ambiente e Saneamento',        'description' => 'Coleta seletiva · Áreas verdes · Saneamento · Energia limpa'],
            ['icon' => '💼', 'name' => 'Desenvolvimento Econômico',         'description' => 'Emprego · MEI · Qualificação · Turismo'],
            ['icon' => '🤝', 'name' => 'Assistência Social e Direitos',     'description' => 'CRAS/CREAS · Criança e adolescente · Vulnerabilidade · Direitos'],
            ['icon' => '🛡️', 'name' => 'Segurança Pública',                'description' => 'Guarda municipal · Câmeras · Prevenção'],
            ['icon' => '💻', 'name' => 'Gestão, Tecnologia e Transparência', 'description' => 'Governo digital · Dados abertos · Participação popular'],
            ['icon' => '🎭', 'name' => 'Cultura, Esporte e Lazer',          'description' => 'Centros culturais · Esporte amador · Praças e parques'],
        ];

        foreach ($defaults as $i => $axis) {
            MandateAxis::create(array_merge($axis, [
                'municipality_id' => $municipality->id,
                'order'           => $i + 1,
            ]));
        }

        return back()->with('success', '9 eixos padrão importados com sucesso. Agora cadastre as promessas de cada eixo.');
    }

    public function destroyPromise($id)
    {
        $municipality = auth()->user()->municipality;
        $promise = MandatePromise::where('municipality_id', $municipality->id)->findOrFail($id);
        $promise->delete();
        return back()->with('success', 'Promessa removida.');
    }
}
