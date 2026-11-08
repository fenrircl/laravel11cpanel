<?php

namespace App\Http\Controllers;

use App\Models\ChatApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatApiTokensController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.api-tokens', [
            'tokens' => $request->user()->chatApiTokens()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'expires_in_days' => ['required', 'integer', 'in:30,90,365'],
        ]);

        if ($request->user()->chatApiTokens()->count() >= 10) {
            return back()->withErrors(['name' => 'Revoca un token existente antes de crear otro.']);
        }

        $secret = Str::random(64);
        $token = $request->user()->chatApiTokens()->create([
            'name' => $validated['name'],
            'token_hash' => hash('sha256', $secret),
            'expires_at' => now()->addDays((int) $validated['expires_in_days']),
        ]);

        return redirect()->route('account.api-tokens')->with('plainToken', $token->id . '|' . $secret);
    }

    public function destroy(Request $request, ChatApiToken $token): RedirectResponse
    {
        abort_unless($token->user_id === $request->user()->id, 404);
        $token->delete();

        return back()->with('success', 'Token revocado.');
    }
}
