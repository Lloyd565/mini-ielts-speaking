<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'answer_text' => $this->answer_text,
            'question' => new SpeakingQuestionResource($this->whenLoaded('question')),
            'feedback' => $this->whenLoaded('feedback', fn () => $this->feedback ? new SpeakingFeedbackResource($this->feedback) : null),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
