<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('user.view');

        $users = User::with('roles')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role, fn($q, $r) => $q->whereHas('roles', fn($rq) => $rq->where('name', $r)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Map roles for frontend
        $users->through(fn($u) => array_merge($u->toArray(), [
            'role' => $u->roles->first()?->name,
        ]));

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role']),
            'roles'   => Role::pluck('name'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('user.create');

        return Inertia::render('Users/Create', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'nip'        => $request->nip,
            'department' => $request->department,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): Response
    {
        $this->authorize('user.edit');

        $user->load('roles');

        return Inertia::render('Users/Edit', [
            'userRecord' => array_merge($user->toArray(), ['role' => $user->roles->first()?->name]),
            'roles'      => Role::pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only(['name', 'email', 'nip', 'department']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('user.delete');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil dihapus.");
    }
}
