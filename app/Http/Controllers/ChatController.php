<?php

namespace App\Http\Controllers;

use App\Services\AiProvider;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
    }

    public function send(Request $request, AiProvider $provider)
    {
        $data = $request->validate([
            'message' => 'required|string|max:8000',
            'locale'  => 'nullable|string|max:10',
        ]);
        $locale = $data['locale'] ?? app()->getLocale();
        $fakeProject = (object)[
            'id' => 'chat',
            'title' => 'Chat',
            'language' => $locale,
            'tenant_id' => $request->user()->tenant_id,
            'user' => $request->user(),
            'tenant' => $request->user()->tenant,
        ];

        $result = $provider->chat($fakeProject, $locale, $data['message']);
        $content = \App\Services\AiProvider::extractContent($result);
        return response()->json([
            'ok' => true,
            'reply' => $content ?: 'Sorry, no response.',
            'meta' => [
                'input_tokens'  => (int)($result['input_tokens'] ?? 0),
                'output_tokens' => (int)($result['output_tokens'] ?? 0),
                'cost_cents'    => (int)($result['cost_cents'] ?? 0),
            ],
        ]);
    }
}
