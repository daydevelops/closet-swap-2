<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Returns the authenticated Sanctum user only if they have a verified email.
     * Used to gate exposure of sensitive data (e.g. contact handles) on public routes.
     */
    protected function verifiedViewer(): ?\App\Models\User
    {
        $user = auth('sanctum')->user();
        return ($user && $user->hasVerifiedEmail()) ? $user : null;
    }
}
