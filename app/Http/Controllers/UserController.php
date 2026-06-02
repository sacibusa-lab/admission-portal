<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Helpers\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of system users.
     */
    public function index()
    {
        $users = User::with('role')->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Store a newly created user in the database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        AuditLogger::log('create_user', ['new_user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('users.index')->with('success', 'System user registered successfully.');
    }

    /**
     * Update the specified user in the database.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::defaults()],
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_id = $request->role_id;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        AuditLogger::log('update_user', ['updated_user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('users.index')->with('success', 'System user updated successfully.');
    }

    /**
     * Remove the specified user from the database.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Self-deletion is forbidden.');
        }

        AuditLogger::log('delete_user', ['deleted_user_id' => $user->id, 'email' => $user->email]);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'System user removed successfully.');
    }
}
