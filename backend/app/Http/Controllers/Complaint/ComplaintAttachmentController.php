<?php

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Models\ComplaintMessage;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Téléchargement d'une pièce jointe de réclamation — accessible au client
 * propriétaire de la réclamation ou à un agent disposant de la permission
 * complaints.manage (route partagée entre les deux audiences, d'où une
 * vérification manuelle plutôt qu'un middleware can:).
 */
class ComplaintAttachmentController extends Controller
{
    public function show(Request $request, ComplaintMessage $message, int $index): StreamedResponse
    {
        $complaint = $message->complaint;
        $isOwner = $complaint->user_id === $request->user()->id;
        $canManage = $request->user()->can(Permissions::COMPLAINTS_MANAGE);

        abort_unless($isOwner || $canManage, 403);

        $path = ($message->attachments ?? [])[$index] ?? null;

        abort_if($path === null, 404);

        return Storage::response($path);
    }
}
