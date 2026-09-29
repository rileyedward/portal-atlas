<?php

namespace App\Http\Resources;

use App\Models\Marker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact marker payload for rendering many markers on the map.
 *
 * @mixin Marker
 */
class MarkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->marker_type_id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'geometry' => $this->geometry,
            'floor' => $this->floor,
            'variant' => $this->variant,
            'confidence' => $this->effectiveConfidence(),
            'status' => $this->status->value,
        ];
    }
}
