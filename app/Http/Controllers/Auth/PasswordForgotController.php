<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;

class PasswordForgotController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the email input
        $request->validate(['email' => 'required|email']);

        // Send the password reset link
        Password::sendResetLink(
            $request->only('email')
        );

        return response()->json(['message' => trans(Password::RESET_LINK_SENT)]);
    }
}
