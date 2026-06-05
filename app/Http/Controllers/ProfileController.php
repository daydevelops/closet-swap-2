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

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);

        $user = auth()->user();
        $blockedIds = $user->blocks->pluck('id')
            ->merge($user->blockedBy->pluck('id'))
            ->unique();

        $followingIds = $user->followings()->pluck('users.id')->flip();

        $results = User::where('name', 'like', '%' . $request->q . '%')
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', $blockedIds)
            ->paginate(20);

        $results->getCollection()->transform(fn ($u) => [
            'id'           => $u->id,
            'name'         => $u->name,
            'bio'          => $u->bio,
            'avatar_url'   => $u->avatar_url ?? null,
            'is_following' => $followingIds->has($u->id),
        ]);

        return response()->json($results);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        $old_path = $user->avatar_path;
        $path = (new ImageService())->upload($request->file('avatar'));

        $user->avatar_path = $path;
        $user->save();

        if ($old_path) {
            ImageService::delete($old_path);
        }

        return response()->json(['avatar_url' => $user->avatar_url]);
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

        if ($user->avatar_path) {
            ImageService::delete($user->avatar_path);
        }

        $user->tokens()->delete();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully'], 201);
    }
}
