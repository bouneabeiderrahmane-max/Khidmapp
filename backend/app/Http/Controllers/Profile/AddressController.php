<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => AddressResource::collection($request->user()->addresses()->orderByDesc('is_default')->get()),
        ]);
    }

    public function store(AddressRequest $request): AddressResource
    {
        $user = $request->user();
        $data = $request->validated();
        $data['is_default'] = ($data['is_default'] ?? false) || $user->addresses()->doesntExist();

        if ($data['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        return new AddressResource($user->addresses()->create($data));
    }

    public function update(AddressRequest $request, Address $address): AddressResource
    {
        $this->authorizeOwnership($request, $address);

        $data = $request->validated();

        if ($data['is_default'] ?? false) {
            $address->user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return new AddressResource($address->fresh());
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwnership($request, $address);
        $address->delete();

        return response()->json(['message' => 'ok']);
    }

    private function authorizeOwnership(Request $request, Address $address): void
    {
        abort_if($address->user_id !== $request->user()->id, 403);
    }
}
