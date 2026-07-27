<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintMessageRequest;
use App\Http\Requests\Complaint\UpdateComplaintStatusRequest;
use App\Http\Resources\ComplaintMessageResource;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Services\Complaint\ComplaintService;
use App\Services\Notification\NotificationService;
use App\Support\NotificationTemplate;
use App\Support\OrderActorType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Gestion des réclamations par le service client/administrateur (CDC 7.3 :
 * "prise en charge, suivi, clôture, historique").
 */
class ComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintService $complaints,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $complaints = Complaint::query()
            ->with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ComplaintResource::collection($complaints);
    }

    public function show(Complaint $complaint): ComplaintResource
    {
        return new ComplaintResource($complaint->load(['messages.sender', 'user']));
    }

    /**
     * Une réponse d'agent sur une réclamation "ouverte" vaut prise en
     * charge (transition automatique vers "en_cours" — voir
     * ComplaintService). Notifie également le client (extension au-delà
     * des 10 événements listés en 8.7, signalée explicitement).
     */
    public function storeMessage(StoreComplaintMessageRequest $request, Complaint $complaint): JsonResponse
    {
        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('complaint-attachments'))
            ->all();

        $message = $this->complaints->addMessage(
            $complaint,
            $request->user(),
            OrderActorType::forAgent($request->user()),
            $request->input('message'),
            $attachments,
        );

        $this->notifications->notify(
            $complaint->user,
            NotificationTemplate::COMPLAINT_REPLY,
            ['order_id' => $complaint->order_id, 'complaint_id' => $complaint->id],
        );

        return (new ComplaintMessageResource($message->load('sender')))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateComplaintStatusRequest $request, Complaint $complaint): ComplaintResource
    {
        $complaint = $this->complaints->updateStatus($complaint, $request->string('status')->toString());

        return new ComplaintResource($complaint->load('messages.sender'));
    }
}
