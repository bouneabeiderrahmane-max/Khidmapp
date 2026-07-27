<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationLogResource;
use App\Models\User;
use App\Support\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Historique des notifications reçues et préférences par canal, côté
 * client (CDC 8.7.2 : "consultable dans le profil du client").
 */
class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()->notificationLogs()->paginate($request->integer('per_page', 20));

        return NotificationLogResource::collection($notifications);
    }

    public function showPreferences(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->currentPreferences($request->user())]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();

        foreach (NotificationChannel::all() as $channel) {
            if ($request->has($channel)) {
                $user->notificationPreferences()->updateOrCreate(
                    ['channel' => $channel],
                    ['enabled' => $request->boolean($channel)],
                );
            }
        }

        return response()->json(['data' => $this->currentPreferences($user->fresh())]);
    }

    /**
     * @return array<string, bool>
     */
    private function currentPreferences(User $user): array
    {
        $stored = $user->notificationPreferences()->pluck('enabled', 'channel');

        return collect(NotificationChannel::all())
            ->mapWithKeys(fn (string $channel) => [$channel => $stored->get($channel, true)])
            ->all();
    }
}
