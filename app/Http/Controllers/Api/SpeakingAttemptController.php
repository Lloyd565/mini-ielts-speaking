<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\EvaluationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitSpeakingAnswerRequest;
use App\Http\Resources\SpeakingAttemptResource;
use App\Http\Resources\SpeakingQuestionResource;
use App\Models\SpeakingAttempt;
use App\Models\SpeakingQuestion;
use App\Services\Contracts\EvaluationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeakingAttemptController extends Controller
{
    public function __construct(
        private readonly EvaluationServiceInterface $evaluationService,
    ) {}

    /**
     * Display a paginated list of past attempts, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $attempts = SpeakingAttempt::query()
            ->where('user_id', $request->user()->id)
            ->with(['question', 'feedback'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => SpeakingAttemptResource::collection($attempts->items()),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'per_page' => $attempts->perPage(),
                'total' => $attempts->total(),
            ],
        ]);
    }

    /**
     * Display the full detail of a single attempt.
     */
    public function show(Request $request, SpeakingAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        $attempt->load(['question', 'feedback']);

        return response()->json([
            'success' => true,
            'data' => new SpeakingAttemptResource($attempt),
            'message' => 'OK',
        ]);
    }

    /**
     * Submit an answer, evaluate it, and return the feedback.
     */
    public function store(SubmitSpeakingAnswerRequest $request): JsonResponse
    {
        $question = SpeakingQuestion::findOrFail($request->validated('question_id'));

        $attempt = SpeakingAttempt::create([
            // Always server-derived from the authenticated user, never from the payload.
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
            'answer_text' => $request->validated('answer_text'),
            'status' => 'pending',
        ]);

        try {
            $evaluation = $this->evaluationService->evaluate($question, $attempt->answer_text);
        } catch (EvaluationFailedException) {
            // The attempt row must stay visible with an accurate status.
            $attempt->update(['status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'Your answer was saved, but the evaluation service is unavailable. Please try again later.',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'status' => 'failed',
                ],
            ], 502);
        }

        $attempt->feedback()->create($evaluation);
        $attempt->update(['status' => 'evaluated']);

        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'status' => 'evaluated',
                'question' => new SpeakingQuestionResource($question),
                'answer_text' => $attempt->answer_text,
                'feedback' => [
                    'band_score' => (float) $attempt->feedback->band_score,
                    'strengths' => $attempt->feedback->strengths,
                    'areas_to_improve' => $attempt->feedback->areas_to_improve,
                ],
            ],
            'message' => 'Evaluation completed.',
        ], 201);
    }
}
