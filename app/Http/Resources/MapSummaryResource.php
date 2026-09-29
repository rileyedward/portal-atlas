<?php

namespace App\Http\Resources;

use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Map
 */
class MapSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'status' => $this->status->value,
            'image_url' => $this->imageUrl(),
            'markers_count' => $this->whenCounted('markers'),
        ];
    }
}
