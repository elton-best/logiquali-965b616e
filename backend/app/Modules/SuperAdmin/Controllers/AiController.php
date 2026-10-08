<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiGenerateRequest;
use App\Models\AiSuggestion;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;

class AiController extends Controller
{
    public function generate(AiGenerateRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user?->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Entreprise requise pour utiliser l’IA.',
            ], 422);
        }

        $system = $request->input('system');
        $prompt = $request->input('prompt');
        $model = $request->input('model', config('grok.model'));
        $temperature = $request->input('temperature', 0.2);
        $maxTokens = $request->input('max_tokens', 800);

        $client = OpenAI::factory()
            ->withApiKey(config('grok.api_key'))
            ->withBaseUri(rtrim(config('grok.base_url'), '/') . '/')
            ->make();

        $messages = [];
        if ($system) {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = $client->chat()->create([
            'model' => $model,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ]);

        $content = $response->choices[0]->message->content ?? '';
        $usage = $response->usage ?? null;

        AiSuggestion::create([
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $user->site_id,
            'suggestion_context' => 'general',
            'user_prompt' => $prompt,
            'ai_response' => $content,
            'confidence_score' => null,
            'accepted' => false,
            'modified_by_user' => false,
            'api_model' => $model,
            'api_tokens_used' => $usage?->total_tokens ?? null,
            'api_cost' => null,
            'user_id' => $user?->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $content,
                'model' => $model,
                'usage' => $usage,
            ],
        ]);
    }
}
