<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\AssistantUsageLog;
use App\Services\OpenAiAssistantService;
use App\Services\AssistantSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class AssistantChatController extends Controller
{
    private const DAILY_MESSAGE_LIMIT = 5;

    public function quota(Request $request): JsonResponse
    {
        return response()->json(['quota' => $this->quotaFor($request->user())]);
    }

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
            ->map(function ($message) {
                [$content, $chart] = $this->extractChartMetadata($message->content);

                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->role === 'assistant'
                        ? Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
                        : $content,
                    'chart' => $chart,
                    'created_at' => $message->created_at,
                ];
            });

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
        $userMessage = trim($validated['message']);
        $startedAt = microtime(true);

        // Lock the user row so simultaneous requests cannot exceed the daily limit.
        [$conversation, $quota, $usageLog] = DB::transaction(function () use ($user, $validated, $userMessage, $startedAt) {
            $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $quota = $this->quotaFor($lockedUser);

            if (!$quota['is_admin'] && $quota['remaining'] < 1) {
                return [null, $quota, null];
            }

            if (!empty($validated['conversation_id'])) {
                $conversation = $lockedUser->assistantConversations()->findOrFail($validated['conversation_id']);
            } else {
                $conversation = $lockedUser->assistantConversations()->create([
                    'title' => Str::limit($userMessage, 100),
                ]);
            }

            $conversation->messages()->create([
                'role' => 'user',
                'content' => $userMessage,
            ]);

            // Reserve this request in the audit log before calling OpenAI. This
            // makes the limit atomic and independent of conversation deletion.
            $usageLog = AssistantUsageLog::query()->create([
                'user_id' => $lockedUser->id,
                'user_name' => $lockedUser->name,
                'conversation_id' => $conversation->id,
                'user_message' => $userMessage,
                'model' => app(AssistantSettingsService::class)->model(),
                'status' => 'processing',
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            if (!$quota['is_admin']) {
                $quota['used']++;
                $quota['remaining'] = max(0, $quota['limit'] - $quota['used']);
            }

            return [$conversation, $quota, $usageLog];
        });

        if (!$conversation) {
            return response()->json([
                'message' => 'Ya utilizaste tus 5 consultas de hoy. Podrás volver a escribir cuando se reinicie el contador.',
                'quota' => $quota,
            ], 429);
        }

        // Keep the full transcript in the database, but send only the last four turns.
        $history = $conversation->messages()
            ->latest('id')
            ->limit(8)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => Str::limit($this->extractChartMetadata($message->content)[0], 2000, ''),
            ])
            ->values()
            ->all();

        try {
            $result = $assistant->answer($history);
        } catch (Throwable $exception) {
            logger()->error('Assistant chat request failed', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'exception' => get_class($exception),
            ]);

            $this->updateUsageLog($usageLog, [
                'status' => 'failed',
                'error_type' => class_basename($exception),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return response()->json([
                'message' => 'No se pudo consultar el asistente. Revisa su configuración e inténtalo nuevamente.',
                'conversation_id' => $conversation->id,
                'quota' => $quota,
            ], 502);
        }

        $answer = $result['content'];
        $chart = $result['chart'] ?? null;

        $saved = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $this->storeChartMetadata($answer, $chart),
        ]);
        $conversation->touch();

        $this->updateUsageLog($usageLog, [
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
            'quota' => $quota,
            'message' => [
                'id' => $saved->id,
                'role' => 'assistant',
                'content' => Str::markdown($answer, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
                'chart' => $chart,
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

    private function updateUsageLog(AssistantUsageLog $log, array $data): void
    {
        try {
            $log->update($data);
        } catch (Throwable $exception) {
            logger()->warning('Assistant usage audit could not be saved', [
                'exception' => get_class($exception),
            ]);
        }
    }

    private function storeChartMetadata(string $content, ?array $chart): string
    {
        if ($chart === null) {
            return $content;
        }

        $json = json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($json)) {
            return $content;
        }

        return rtrim($content)."\n\n<!--assistant-chart:".base64_encode($json).'-->';
    }

    /** @return array{0:string,1:?array} */
    private function extractChartMetadata(string $content): array
    {
        if (!preg_match('/\\n?<!--assistant-chart:([A-Za-z0-9+\\/=]+)-->$/', $content, $matches)) {
            return [$content, null];
        }

        $decoded = base64_decode($matches[1], true);
        $chart = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($chart) || !isset($chart['title'], $chart['type'], $chart['labels'], $chart['series'])) {
            return [$content, null];
        }

        return [rtrim(substr($content, 0, -strlen($matches[0]))), $chart];
    }

    private function quotaFor($user): array
    {
        if ($user->hasRole('admin')) {
            return ['is_admin' => true, 'limit' => null, 'used' => null, 'remaining' => null, 'reset_at' => null];
        }

        $timezone = config('assistant.quota_timezone', 'America/Santiago');
        $localNow = CarbonImmutable::now($timezone);
        $startUtc = $localNow->startOfDay()->utc();
        $endUtc = $localNow->addDay()->startOfDay()->utc();
        $used = AssistantUsageLog::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startUtc)
            ->where('created_at', '<', $endUtc)
            ->count();

        return [
            'is_admin' => false,
            'limit' => self::DAILY_MESSAGE_LIMIT,
            'used' => (int) $used,
            'remaining' => max(0, self::DAILY_MESSAGE_LIMIT - $used),
            'reset_at' => $endUtc->toIso8601String(),
            'reset_time' => $endUtc->setTimezone($timezone)->format('H:i'),
        ];
    }
}
