@extends('layouts.app')

@section('title', 'System Users')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">System Users</h3>
            <p class="text-muted m-0">Manage user accounts and their role access control permissions.</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i> Add System User
        </button>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">User Name</th>
                            <th class="py-3">Email Address</th>
                            <th class="py-3">Role</th>
                            <th class="py-3">Registered Date</th>
                            <th class="px-4 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 1rem;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge 
                                        @if($user->hasRole('Super Admin')) bg-danger 
                                        @elseif($user->hasRole('Principal')) bg-success 
                                        @else bg-primary 
                                        @endif" style="font-size: 0.75rem;">
                                        {{ $user->role ? $user->role->name : 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                                <td class="px-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Edit trigger -->
                                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        
                                        <!-- Delete trigger -->
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-outline-secondary btn-sm" disabled title="Self-deletion forbidden">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit User Modal -->
                            <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $user->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title fw-bold" id="editUserModalLabel{{ $user->id }}">Edit System User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('users.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Full Name</label>
                                                    <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Email Address</label>
                                                    <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">System Role</label>
                                                    <select class="form-select" name="role_id" required>
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->id }}" {{ $user->role_id === $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">New Password</label>
                                                    <input type="password" class="form-control" name="password" placeholder="Leave empty to keep current password">
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Update Account</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="addUserModalLabel">Create System User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" name="name" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" name="email" required placeholder="e.g. officer@staugustine.edu.ng">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">System Role</label>
                        <select class="form-select" name="role_id" required>
                            <option value="" disabled selected>Select system role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" class="form-control" name="password" required placeholder="Enter secure password">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Register Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
