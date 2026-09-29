<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/users/Index', [
            'users' => User::query()
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->whereLike('name', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhereLike('email', '%'.addcslashes($search, '%_\\').'%')))
                ->orderBy('name')
                ->paginate(50)->withQueryString()
                ->through(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role->value, 'created_at' => $u->created_at?->toIso8601String()]),
            'roles' => UserRole::options(),
            'filters' => ['search' => $search],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot change your own role.');

        $validated = $request->validate(['role' => ['required', Rule::enum(UserRole::class)]]);
        $user->forceFill(['role' => $validated['role']])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} is now {$user->role->label()}."]);

        return back();
    }
}
