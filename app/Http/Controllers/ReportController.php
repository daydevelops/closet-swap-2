<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot report yourself.'], 422);
        }

        $request->validate([
            'reason'  => 'required|string|max:255',
            'details' => 'nullable|string|max:500',
        ]);

        $alreadyReported = Report::where('reported_by', $request->user()->id)
            ->where('reported_user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyReported) {
            return response()->json(['message' => 'You have already submitted a pending report for this user.'], 422);
        }

        Report::create([
            'reported_by'      => $request->user()->id,
            'reported_user_id' => $user->id,
            'reason'           => $request->reason,
            'details'          => $request->details,
            'status'           => 'pending',
        ]);

        return response()->json(['message' => 'Report submitted.'], 201);
    }
}
