<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakingFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'band_score' => (float) $this->band_score,
            'strengths' => $this->strengths,
            'areas_to_improve' => $this->areas_to_improve,
        ];
    }
}
