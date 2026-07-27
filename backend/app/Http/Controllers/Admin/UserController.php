<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUser\BlockUserRequest;
use App\Http\Requests\AdminUser\CreateInternalUserRequest;
use App\Http\Requests\AdminUser\UpdateUserRolesRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditAction;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestion des utilisateurs — clients et comptes internes (CDC 8.9.2,
 * 8.9.6). Réservé à l'administrateur (7.4).
 */
class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->toString()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(fn ($qq) => $qq->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return AdminUserResource::collection($users);
    }

    public function show(User $user): AdminUserResource
    {
        return new AdminUserResource($user);
    }

    public function block(BlockUserRequest $request, User $user): AdminUserResource
    {
        $user->update([
            'blocked_at' => now(),
            'blocked_until' => $request->input('until'),
            'blocked_reason' => $request->string('reason')->toString(),
            'blocked_by' => $request->user()->id,
        ]);

        $this->audit->log($request->user(), AuditAction::USER_BLOCKED, $user, [
            'reason' => $request->input('reason'),
            'until' => $request->input('until'),
        ]);

        return new AdminUserResource($user->fresh());
    }

    public function unblock(Request $request, User $user): AdminUserResource
    {
        $user->update([
            'blocked_at' => null,
            'blocked_until' => null,
            'blocked_reason' => null,
            'blocked_by' => null,
        ]);

        $this->audit->log($request->user(), AuditAction::USER_UNBLOCKED, $user);

        return new AdminUserResource($user->fresh());
    }

    /**
     * Création d'un compte interne (service client/administrateur — 8.9.2,
     * 8.9.6). Les clients s'inscrivent eux-mêmes (OTP/e-mail).
     */
    public function store(CreateInternalUserRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'email_verified_at' => now(),
            'password' => $request->string('password')->toString(),
            'locale' => app()->getLocale(),
        ]);

        $role = $request->string('role')->toString();
        $user->assignRole($role);

        $this->audit->log($request->user(), AuditAction::USER_CREATED, $user, ['role' => $role]);

        return (new AdminUserResource($user->fresh()))->response()->setStatusCode(201);
    }

    /**
     * Attribution/révocation des rôles d'un compte interne (8.9.6).
     * Remplace intégralement l'ensemble des rôles par celui fourni.
     */
    public function updateRoles(UpdateUserRolesRequest $request, User $user): AdminUserResource
    {
        abort_if($user->hasRole(Roles::CLIENT), 422, __('khidmapp.cannot_assign_internal_roles_to_client'));

        $previousRoles = $user->getRoleNames()->all();
        $newRoles = $request->input('roles');

        $user->syncRoles($newRoles);

        $this->audit->log($request->user(), AuditAction::USER_ROLE_ASSIGNED, $user, [
            'from' => $previousRoles,
            'to' => $newRoles,
        ]);

        return new AdminUserResource($user->fresh());
    }
}
