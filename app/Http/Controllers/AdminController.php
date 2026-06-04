<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount('clothingItems');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        $users->getCollection()->transform(fn ($user) => [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'created_at'        => $user->created_at,
            'email_verified_at'  => $user->email_verified_at,
            'item_count'        => $user->clothing_items_count,
            'is_admin'          => $user->is_admin,
        ]);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('clothingItems');

        return response()->json([
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'bio'               => $user->bio,
            'contact_handle'    => $user->contact_handle,
            'created_at'        => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
            'item_count'        => $user->clothing_items_count,
            'is_admin'          => $user->is_admin,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $user->notify(new AccountDeletedNotification($request->reason));

        foreach ($user->clothingItems as $item) {
            foreach ($item->images as $image) {
                if (! str_starts_with($image->path, 'http')) {
                    Storage::disk('s3')->delete($image->path);
                }
                $image->delete();
            }
            $item->likes()->detach();
            $item->delete();
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function reports(User $user): JsonResponse
    {
        $reports = Report::with('reporter:id,name')
            ->where('reported_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'reason'           => $r->reason,
                'details'          => $r->details,
                'status'           => $r->status,
                'created_at'       => $r->created_at,
                'reported_by_id'   => $r->reported_by,
                'reported_by_name' => $r->reporter?->name,
            ]);

        return response()->json($reports);
    }

    public function updateReport(Request $request, Report $report): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:reviewed,dismissed',
        ]);

        $report->update(['status' => $request->status]);

        return response()->json(['message' => 'Report updated.']);
    }
}
