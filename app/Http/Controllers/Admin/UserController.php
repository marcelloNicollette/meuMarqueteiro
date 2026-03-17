<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('municipality')
            ->where('role', 'mayor')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $municipalities = Municipality::where('subscription_active', true)->get();
        return view('admin.users.create', compact('municipalities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users',
            'password'        => 'required|string|min:8',
            'municipality_id' => 'required|exists:municipalities,id',
            'phone'           => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'role'            => 'mayor',
            'municipality_id' => $data['municipality_id'],
            'phone'           => $data['phone'] ?? null,
            'is_active'       => true,
        ]);
        $user->assignRole('mayor');

        return redirect()->route('admin.users.index')
            ->with('success', 'Prefeito criado com sucesso.');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $municipalities = Municipality::where('subscription_active', true)->get();
        return view('admin.users.edit', compact('user', 'municipalities'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:20',
            'is_active'  => 'boolean',
            'password'   => 'nullable|string|min:8',
        ]);

        $update = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'ativado' : 'desativado';
        return back()->with('success', "Prefeito {$user->name} {$status} com sucesso.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir sua própria conta.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', "Prefeito {$user->name} removido com sucesso.");
    }
}
