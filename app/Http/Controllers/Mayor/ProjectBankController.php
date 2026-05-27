<?php

namespace App\Http\Controllers\Mayor;

use App\Http\Controllers\Controller;
use App\Models\ProjectThesis;
use App\Models\ProjectThesisNotification;
use App\Models\ProjectThesisShare;
use App\Models\ProjectThesisUserState;
use App\Models\User;
use App\Services\Projects\ProjectBankLibraryService;
use App\Services\Projects\ProjectBankNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectBankController extends Controller
{
    public function __construct(
        private readonly ProjectBankLibraryService $library,
        private readonly ProjectBankNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();

        if (ProjectThesis::query()->where('municipality_id', $user->municipality_id)->count() === 0) {
            $this->library->ensureLibraryForMunicipality($user->municipality, force: false);
        }

        $theses = ProjectThesis::query()
            ->where('municipality_id', $user->municipality_id)
            ->with([
                'userStates' => fn ($builder) => $builder->where('user_id', $user->id),
                'sourceProjects:id,source_thesis_id,status',
            ])
            ->orderByRaw("
                CASE urgency
                    WHEN 'alta' THEN 1
                    WHEN 'media' THEN 2
                    ELSE 3
                END
            ")
            ->orderByDesc('updated_at')
            ->get();

        $savedTheses = $theses->filter(function (ProjectThesis $thesis) {
            return (bool) optional($thesis->userStates->first())->is_saved;
        })->values();
        $categories = ProjectThesis::query()
            ->where('municipality_id', $user->municipality_id)
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $notifications = ProjectThesisNotification::query()
            ->where('user_id', $user->id)
            ->with([
                'thesis:id,title,category,urgency,resource_deadline',
                'share.sharedBy:id,name',
            ])
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('mayor.project-bank.index', [
            'theses' => $theses,
            'savedTheses' => $savedTheses,
            'categories' => $categories,
            'notifications' => $notifications,
            'activeFilters' => [
                'category' => (string) $request->string('category'),
                'urgency' => (string) $request->string('urgency'),
                'size' => (string) $request->string('size'),
                'complexity' => (string) $request->string('complexity'),
                'search' => (string) $request->string('search'),
                'scope' => (string) $request->string('scope', 'all'),
            ],
            'savedCount' => ProjectThesisUserState::query()
                ->where('user_id', $user->id)
                ->where('is_saved', true)
                ->count(),
            'unreadNotificationsCount' => ProjectThesisNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'urgentWindowCount' => ProjectThesis::query()
                ->where('municipality_id', $user->municipality_id)
                ->where('urgency', 'alta')
                ->whereNotNull('resource_deadline')
                ->whereBetween('resource_deadline', [now()->toDateString(), now()->addDays(60)->toDateString()])
                ->count(),
            'canGenerateProject' => $user->isMayor(),
        ]);
    }

    public function show(ProjectThesis $thesis): View
    {
        $user = Auth::user();
        $this->authorizeThesis($thesis, $user);

        $thesis->load([
            'sourceProjects.owner:id,name',
            'userStates' => fn ($builder) => $builder->where('user_id', $user->id),
            'shares.sharedBy:id,name',
            'shares.sharedWith:id,name',
        ]);

        $eligibleUsers = User::query()
            ->where('municipality_id', $user->municipality_id)
            ->where('is_active', true)
            ->whereIn('role', ['mayor', 'secretary', 'advisor'])
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $share = ProjectThesisShare::query()
            ->where('project_thesis_id', $thesis->id)
            ->where('shared_with_user_id', $user->id)
            ->with('sharedBy:id,name')
            ->latest('id')
            ->first();

        $incomingNotification = ProjectThesisNotification::query()
            ->where('project_thesis_id', $thesis->id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest('id')
            ->first();

        if ($share && !$share->viewed_at) {
            $share->update(['viewed_at' => now()]);
        }

        ProjectThesisNotification::query()
            ->where('project_thesis_id', $thesis->id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $daysUntilDeadline = $thesis->resource_deadline
            ? now()->startOfDay()->diffInDays($thesis->resource_deadline->copy()->startOfDay(), false)
            : null;

        return view('mayor.project-bank.show', [
            'thesis' => $thesis,
            'userState' => $thesis->userStates->first(),
            'eligibleUsers' => $eligibleUsers,
            'trackingStatus' => $thesis->trackingStatus(),
            'incomingNotification' => $incomingNotification,
            'receivedShare' => $share,
            'daysUntilDeadline' => $daysUntilDeadline,
            'canGenerateProject' => $user->isMayor(),
        ]);
    }

    public function save(ProjectThesis $thesis): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeThesis($thesis, $user);

        $state = ProjectThesisUserState::query()->firstOrNew([
            'project_thesis_id' => $thesis->id,
            'user_id' => $user->id,
        ]);

        $state->is_saved = !$state->is_saved;
        $state->last_action_at = now();
        $state->save();

        return back()->with(
            'success',
            $state->is_saved
                ? 'Tese salva para consultar depois.'
                : 'Tese removida da sua lista de salvas.'
        );
    }

    public function share(Request $request, ProjectThesis $thesis): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeThesis($thesis, $user);

        $data = $request->validate([
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['integer'],
        ]);

        $recipients = User::query()
            ->where('municipality_id', $user->municipality_id)
            ->where('is_active', true)
            ->whereIn('role', ['mayor', 'secretary', 'advisor'])
            ->whereIn('id', $data['recipients'])
            ->where('id', '!=', $user->id)
            ->get();

        foreach ($recipients as $recipient) {
            $share = ProjectThesisShare::query()->create([
                'project_thesis_id' => $thesis->id,
                'shared_by_user_id' => $user->id,
                'shared_with_user_id' => $recipient->id,
            ]);

            $this->notifications->dispatchShareNotification($share);
        }

        $this->touchUserState($thesis, $user);

        return back()->with('success', 'Tese compartilhada com a equipe selecionada.');
    }

    public function generateProject(ProjectThesis $thesis): RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeThesis($thesis, $user);

        abort_unless($user->isMayor(), 403);

        $this->touchUserState($thesis, $user);

        return redirect()->route('mayor.projects.create', [
            'source_thesis' => $thesis->id,
        ]);
    }

    private function authorizeThesis(ProjectThesis $thesis, User $user): void
    {
        if ((int) $thesis->municipality_id !== (int) $user->municipality_id) {
            abort(403);
        }
    }

    private function touchUserState(ProjectThesis $thesis, User $user): void
    {
        ProjectThesisUserState::query()->updateOrCreate(
            [
                'project_thesis_id' => $thesis->id,
                'user_id' => $user->id,
            ],
            [
                'last_action_at' => now(),
            ]
        );
    }
}
