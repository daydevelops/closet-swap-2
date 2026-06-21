<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        $user = auth('sanctum')->user();

        ContactMessage::create([
            ...$data,
            'user_id' => $user?->id,
        ]);

        return response()->json(['message' => 'Message sent.'], 201);
    }
}
