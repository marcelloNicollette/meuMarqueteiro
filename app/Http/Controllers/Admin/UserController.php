<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\ContactArea;
use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['municipality', 'contactArea'])
            ->municipalOperators()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $municipalities = Municipality::where('subscription_active', true)->get();
        $contactAreas = ContactArea::query()
            ->with('municipality:id,name,state_code')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('admin.users.create', compact('municipalities', 'contactAreas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users',
            'password'        => 'required|string|min:8',
            'municipality_id' => 'required|exists:municipalities,id',
            'role'            => ['required', Rule::in(['mayor', 'secretary', 'advisor'])],
            'contact_area_id' => ['nullable', 'exists:contact_areas,id'],
            'phone'           => 'nullable|string|max:20',
            'can_register_demands' => ['nullable', 'boolean'],
        ]);

        $this->ensureValidContactAreaAssignment(
            (int) $data['municipality_id'],
            $data['role'],
            isset($data['contact_area_id']) ? (int) $data['contact_area_id'] : null
        );

        $user = User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'role'            => $data['role'],
            'municipality_id' => $data['municipality_id'],
            'contact_area_id' => $data['contact_area_id'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'can_register_demands' => $this->resolveCanRegisterDemands($data['role'], $request->boolean('can_register_demands')),
            'is_active'       => true,
        ]);
        $user->syncRoles([$this->ensureRoleExists($data['role'])->name]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário municipal criado com sucesso.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $municipalities = Municipality::where('subscription_active', true)->get();
        $contactAreas = ContactArea::query()
            ->with('municipality:id,name,state_code')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('admin.users.edit', compact('user', 'municipalities', 'contactAreas'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'role'       => ['required', Rule::in(['mayor', 'secretary', 'advisor'])],
            'municipality_id' => 'required|exists:municipalities,id',
            'contact_area_id' => ['nullable', 'exists:contact_areas,id'],
            'phone'      => 'nullable|string|max:20',
            'is_active'  => 'boolean',
            'password'   => 'nullable|string|min:8',
            'can_register_demands' => ['nullable', 'boolean'],
        ]);

        $this->ensureValidContactAreaAssignment(
            (int) $data['municipality_id'],
            $data['role'],
            isset($data['contact_area_id']) ? (int) $data['contact_area_id'] : null
        );

        $update = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'municipality_id' => $data['municipality_id'],
            'contact_area_id' => $data['contact_area_id'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'can_register_demands' => $this->resolveCanRegisterDemands($data['role'], $request->boolean('can_register_demands')),
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);
        $user->syncRoles([$this->ensureRoleExists($data['role'])->name]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'ativado' : 'desativado';
        return back()->with('success', "Usuário {$user->name} {$status} com sucesso.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não  pode excluir sua própria conta.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', "Usuário {$user->name} removido com sucesso.");
    }

    private function ensureRoleExists(string $role): Role
    {
        return Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    private function resolveCanRegisterDemands(string $role, bool $canRegisterDemands): bool
    {
        if (in_array($role, ['mayor', 'secretary'], true)) {
            return true;
        }

        return $canRegisterDemands;
    }

    private function ensureValidContactAreaAssignment(int $municipalityId, string $role, ?int $contactAreaId): void
    {
        if (in_array($role, ['secretary', 'advisor'], true) && !$contactAreaId) {
            throw ValidationException::withMessages([
                'contact_area_id' => 'Perfis de secretário e assessor exigem secretaria vinculada.',
            ]);
        }

        if ($contactAreaId) {
            $exists = ContactArea::query()
                ->where('id', $contactAreaId)
                ->where('municipality_id', $municipalityId)
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'contact_area_id' => 'A secretaria selecionada não  pertence ao município informado.',
                ]);
            }
        }
    }
}
