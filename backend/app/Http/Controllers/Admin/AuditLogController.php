<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Consultation du journal d'audit (CDC 8.9.6) : validations de paiement,
 * modifications de marge/taux, changements de rôle, blocages de compte.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->with('actor')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->string('subject_type')))
            ->when($request->filled('actor_id'), fn ($q) => $q->where('actor_id', $request->integer('actor_id')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return AuditLogResource::collection($logs);
    }
}
