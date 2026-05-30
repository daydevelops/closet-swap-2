<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ClothingItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (auth()->check()) {
            $data['is_following'] = auth()->user()->isFollowing($user);
        }

        return response()->json($data);
    }

    public function items(User $user): JsonResponse
    {
        $query = ClothingItem::where('user_id', $user->id)->with('images');

        if (auth()->check()) {
            $query->notBlocked();
        }

        return response()->json($query->get());
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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Account deleted successfully'],201);
    }
}
