<?php

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\StoreComplaintMessageRequest;
use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Http\Resources\ComplaintMessageResource;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\Order;
use App\Services\Complaint\ComplaintService;
use App\Support\OrderActorType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Réclamations côté client (CDC 7.2.4, 8.8) : ouverture rattachée à une
 * commande précise, fil de discussion avec pièces jointes.
 */
class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $complaints) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $complaints = $request->user()->complaints()->latest()->paginate($request->integer('per_page', 20));

        return ComplaintResource::collection($complaints);
    }

    public function show(Request $request, Complaint $complaint): ComplaintResource
    {
        $this->authorizeOwnership($request, $complaint);

        return new ComplaintResource($complaint->load('messages.sender'));
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));
        $attachments = $this->storeAttachments($request);

        $complaint = $this->complaints->open(
            $request->user(),
            $order,
            $request->string('category')->toString(),
            $request->string('message')->toString(),
            $attachments,
        );

        return (new ComplaintResource($complaint->load('messages.sender')))->response()->setStatusCode(201);
    }

    public function storeMessage(StoreComplaintMessageRequest $request, Complaint $complaint): JsonResponse
    {
        $this->authorizeOwnership($request, $complaint);

        $attachments = $this->storeAttachments($request);

        $message = $this->complaints->addMessage(
            $complaint,
            $request->user(),
            OrderActorType::CLIENT,
            $request->input('message'),
            $attachments,
        );

        return (new ComplaintMessageResource($message->load('sender')))->response()->setStatusCode(201);
    }

    /**
     * @return array<int, string>
     */
    private function storeAttachments(Request $request): array
    {
        return collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('complaint-attachments'))
            ->all();
    }

    private function authorizeOwnership(Request $request, Complaint $complaint): void
    {
        abort_if($complaint->user_id !== $request->user()->id, 403);
    }
}
