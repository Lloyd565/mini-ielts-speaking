<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListSpeakingQuestionsRequest;
use App\Http\Resources\SpeakingQuestionResource;
use App\Models\SpeakingQuestion;
use Illuminate\Http\JsonResponse;

class SpeakingQuestionController extends Controller
{
    /**
     * Display a listing of speaking questions, optionally filtered by part.
     */
    public function index(ListSpeakingQuestionsRequest $request): JsonResponse
    {
        $questions = SpeakingQuestion::query()
            ->when($request->validated('part'), fn ($query, string $part) => $query->where('part', $part))
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SpeakingQuestionResource::collection($questions),
            'message' => 'OK',
        ]);
    }
}
