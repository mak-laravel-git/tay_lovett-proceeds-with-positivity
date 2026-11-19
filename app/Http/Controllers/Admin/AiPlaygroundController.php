<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiDriver;
use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiProviderCredential;
use App\Services\AiChat;
use App\Services\QuoteRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiPlaygroundController extends Controller
{
    public function index(QuoteRouter $router): Response
    {
        return Inertia::render('admin/ai/playground', [
            'credentials' => AiProviderCredential::query()
                ->where('is_enabled', true)
                ->orderBy('priority')
                ->get(['id', 'driver', 'label', 'health'])
                ->map(fn (AiProviderCredential $credential): array => [
                    'id' => $credential->id,
                    'label' => $credential->label,
                    'driver_label' => AiDriver::labelFor($credential->driver),
                    'driver' => $credential->driver,
                    'health' => $credential->health,
                ]),
            'models' => AiModel::query()
                ->where('is_enabled', true)
                ->orderBy('id')
                ->get(['id', 'driver', 'name']),
            'route' => $router->simulate(),
        ]);
    }

    public function send(Request $request, AiChat $chat): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['array', 'max:20'],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
            'credential_id' => ['nullable', 'integer'],
            'model_id' => ['nullable', 'integer'],
        ]);

        $credential = isset($validated['credential_id'])
            ? AiProviderCredential::query()->where('is_enabled', true)->findOrFail($validated['credential_id'])
            : null;

        $model = isset($validated['model_id'])
            ? AiModel::query()->where('is_enabled', true)->findOrFail($validated['model_id'])
            : null;

        if ($credential !== null && $model !== null && $model->driver !== $credential->driver) {
            return response()->json([
                'message' => 'The selected model belongs to a different provider.',
                'errors' => ['message' => ['The selected model belongs to a different provider.']],
            ], 422);
        }

        return response()->json(
            $chat->send($request->user(), $validated['history'] ?? [], $validated['message'], $credential, $model),
        );
    }
}
