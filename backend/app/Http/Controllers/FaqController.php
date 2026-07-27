<?php

namespace App\Http\Controllers;

use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Centre d'aide public (CDC 8.8 : "FAQ, guides, contact").
 */
class FaqController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FaqResource::collection(Faq::query()->orderBy('position')->orderBy('id')->get());
    }
}
