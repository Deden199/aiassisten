<?php

namespace App\Http\Controllers;

use App\Models\AiProject;
use App\Models\ChatSession;
use App\Services\AiProvider;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')
                ->with('status', __('Please log in to use chat.'));
        }

        $session = ChatSession::firstOrCreate(
            ['user_id' => $user->id],
            ['tenant_id' => $user->tenant_id]
        );

        $messages = $session->messages()->orderBy('created_at')->get(['role','content']);

        return view('chat.index', compact('messages'));
    }

    public function send(Request $request, AiProvider $provider)
    {
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentication required.'], 401);
            }
            return redirect()->route('login');
        }

        $data = $request->validate([
            'message' => 'required|string|max:8000',
            'locale'  => 'nullable|string|max:10',
        ]);
        $locale = $data['locale'] ?? app()->getLocale();

        $fakeProject = new AiProject([
            'id'        => 'chat',
            'title'     => 'Chat',
            'language'  => $locale,
            'tenant_id' => $user->tenant_id,
        ]);
        $fakeProject->setRelation('user', $user);
        $fakeProject->setRelation('tenant', $user->tenant);

        $session = ChatSession::firstOrCreate(
            ['user_id' => $user->id],
            ['tenant_id' => $user->tenant_id]
        );

        $session->messages()->create([
            'tenant_id' => $user->tenant_id,
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $messages = $session->messages()
            ->orderByDesc('created_at')
            ->take(20)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'role' => $m['role'] === 'bot' ? 'assistant' : $m['role'],
                'content' => $m['content'],
            ])
            ->toArray();

        $result = $provider->chat($fakeProject, $locale, $messages);
        $content = \App\Services\AiProvider::extractContent($result) ?: 'Sorry, no response.';

        $session->messages()->create([
            'tenant_id' => $user->tenant_id,
            'role' => 'bot',
            'content' => $content,
        ]);

        return response()->json([
            'ok' => true,
            'reply' => $content,
            'meta' => [
                'input_tokens'  => (int)($result['input_tokens'] ?? 0),
                'output_tokens' => (int)($result['output_tokens'] ?? 0),
                'cost_cents'    => (int)($result['cost_cents'] ?? 0),
            ],
        ]);
    }
}

