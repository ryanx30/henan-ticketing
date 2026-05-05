<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserApiController extends BaseApiController
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', 'all');
        $status = (string) $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $query = User::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (in_array($role, User::ROLES, true)) {
            $query->where('role', $role);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = $paginator->getCollection()
            ->map(fn (User $user) => $this->mapUser($user))
            ->values();

        return $this->success([
            'rows' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 'Users loaded');
    }

    public function show(Request $request, User $user)
    {
        return $this->success($this->mapUser($user), 'User loaded');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        AuditLogger::record(
            $request,
            'created',
            'user',
            $user->id,
            $user->name,
            'Created user account: ' . $user->name,
            null,
            $this->mapUser($user)
        );

        return $this->success($this->mapUser($user), 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $actor = $request->user();
        $before = $this->mapUser($user);
        $newIsActive = (bool) ($validated['is_active'] ?? $user->is_active);

        if ($actor->id === $user->id && $newIsActive === false) {
            return $this->error('You cannot deactivate your own account.', 422);
        }

        if ($user->role === 'admin' && $newIsActive === false) {
            $activeAdminCount = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count();

            if ($activeAdminCount <= 1) {
                return $this->error('Cannot deactivate the last active admin.', 422);
            }
        }

        $user->name = trim($validated['name']);
        $user->email = strtolower(trim($validated['email']));
        $user->role = $validated['role'];
        $user->is_active = $newIsActive;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLogger::record(
            $request,
            'updated',
            'user',
            $user->id,
            $user->name,
            'Updated user account: ' . $user->name,
            $before,
            $this->mapUser($user)
        );

        return $this->success($this->mapUser($user), 'User updated successfully.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $actor = $request->user();

        if ($actor->id === $user->id) {
            return $this->error('You cannot deactivate your own account.', 422);
        }

        $before = $this->mapUser($user);
        $newStatus = !$user->is_active;

        if ($user->role === 'admin' && $newStatus === false) {
            $activeAdminCount = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count();

            if ($activeAdminCount <= 1) {
                return $this->error('Cannot deactivate the last active admin.', 422);
            }
        }

        $user->is_active = $newStatus;
        $user->save();

        AuditLogger::record(
            $request,
            $user->is_active ? 'activated' : 'deactivated',
            'user',
            $user->id,
            $user->name,
            ($user->is_active ? 'Activated' : 'Deactivated') . ' user account: ' . $user->name,
            $before,
            $this->mapUser($user)
        );

        return $this->success([
            'id' => $user->id,
            'is_active' => (bool) $user->is_active,
        ], $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.');
    }

    protected function mapUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
    }
}
