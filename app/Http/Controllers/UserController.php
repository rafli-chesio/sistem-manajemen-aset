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

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $users = User::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('nip', 'like', "%{$s}%"))
            ->when($request->role, fn($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role']),
            'roles'   => User::$roles,
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return Inertia::render('Users/Create', [
            'roles' => User::$roles,
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
            'role'       => $request->role ?? User::ROLE_VIEWER,
        ]);

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return Inertia::render('Users/Edit', [
            'userRecord' => $user,
            'roles'      => User::$roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only(['name', 'email', 'nip', 'department', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete(); // soft delete

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil dihapus.");
    }
}
