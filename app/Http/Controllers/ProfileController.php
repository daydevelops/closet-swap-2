<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ClothingItem;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(User $user) : JsonResponse
    {
        $isOwn = auth()->id() === $user->id;

        if ($isOwn) {
            // Return full profile data to the owner only
            return response()->json($user->toArray());
        }

        // Return only public-safe fields to anyone else
        $data = [
            'name'       => $user->name,
            'bio'        => $user->bio,
            'avatar_url' => $user->avatar_url,
        ];

        $viewer = auth('sanctum')->user();

        if ($viewer) {
            $data['is_following'] = $viewer->isFollowing($user);
            $data['contact_handle'] = $user->contact_handle;
        }

        return response()->json($data);
    }

    public function items(User $user, Request $request): JsonResponse
    {
        $query = ClothingItem::where('user_id', $user->id)->with('images');

        if (auth()->check()) {
            $query->notBlocked();
        }

        $paginated = $query->paginate(20, ['*'], 'page', $request->query('page', 1));

        $paginated->getCollection()->transform(function ($item) {
            $data = $item->toArray();
            $data['images'] = ImageService::signedUrls($item->images);
            return $data;
        });

        return response()->json($paginated);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return response()->json(['message' => 'Profile updated successfully'], 201);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->tokens()->delete();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully'], 201);
    }
}
