<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\AssistantUsageLog;
use App\Services\OpenAiAssistantService;
use App\Services\AssistantSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class AssistantChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->assistantConversations()
            ->latest('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'updated_at']);

        return response()->json(['data' => $conversations]);
    }

    public function show(Request $request, AssistantConversation $conversation): JsonResponse
    {
        abort_unless((int) $conversation->user_id === (int) $request->user()->id, 404);

        $messages = $conversation->messages()
            ->latest('id')
            ->limit(50)
            ->get(['id', 'role', 'content', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->role === 'assistant'
                    ? Str::markdown($message->content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
                    : $message->content,
                'created_at' => $message->created_at,
            ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'data' => $messages,
        ]);
    }

    public function send(Request $request, OpenAiAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $user = $request->user();
        if (!empty($validated['conversation_id'])) {
            $conversation = $user->assistantConversations()->findOrFail($validated['conversation_id']);
        } else {
            $conversation = $user->assistantConversations()->create([
                'title' => Str::limit(trim($validated['message']), 100),
            ]);
        }

        $userMessage = trim($validated['message']);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // Keep the full transcript in the database, but send only the last four turns.
        $history = $conversation->messages()
            ->latest('id')
            ->limit(8)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => Str::limit($message->content, 2000, ''),
            ])
            ->values()
            ->all();

        $startedAt = microtime(true);
        try {
            $result = $assistant->answer($history);
        } catch (Throwable $exception) {
            logger()->error('Assistant chat request failed', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'exception' => get_class($exception),
            ]);

            $this->writeUsageLog([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'conversation_id' => $conversation->id,
                'user_message' => $userMessage,
                'assistant_message' => null,
                'model' => app(AssistantSettingsService::class)->model(),
                'status' => 'failed',
                'error_type' => class_basename($exception),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return response()->json([
                'message' => 'No se pudo consultar el asistente. Revisa su configuración e inténtalo nuevamente.',
                'conversation_id' => $conversation->id,
            ], 502);
        }

        $answer = $result['content'];

        $saved = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
        ]);
        $conversation->touch();

        $this->writeUsageLog([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'conversation_id' => $conversation->id,
            'user_message' => $userMessage,
            'assistant_message' => $answer,
            'model' => $result['model'] ?? app(AssistantSettingsService::class)->model(),
            'input_tokens' => (int) ($result['usage']['input_tokens'] ?? 0),
            'output_tokens' => (int) ($result['usage']['output_tokens'] ?? 0),
            'total_tokens' => (int) ($result['usage']['total_tokens'] ?? 0),
            'tool_calls' => (int) ($result['tool_calls'] ?? 0),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'status' => 'success',
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => [
                'id' => $saved->id,
                'role' => 'assistant',
                'content' => Str::markdown($answer, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'created_at' => $saved->created_at,
            ],
        ]);
    }

    public function destroy(Request $request, AssistantConversation $conversation): JsonResponse
    {
        abort_unless((int) $conversation->user_id === (int) $request->user()->id, 404);
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    private function writeUsageLog(array $data): void
    {
        try {
            AssistantUsageLog::query()->create($data);
        } catch (Throwable $exception) {
            logger()->warning('Assistant usage audit could not be saved', [
                'exception' => get_class($exception),
            ]);
        }
    }
}
