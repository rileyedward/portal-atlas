<?php

namespace App\Http\Resources;

use App\Models\Map;
use App\Support\SourceVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Map
 */
class MapResource extends JsonResource
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
            'description' => $this->description,
            'status' => $this->status->value,
            'image_url' => $this->imageUrl(),
            'image_attribution' => SourceVisibility::visibleTo($request->user()) ? $this->image_attribution : null,
            'width' => $this->width,
            'height' => $this->height,
            'source_url' => SourceVisibility::visibleTo($request->user()) ? $this->source_url : null,
            'game_version' => $this->gameVersion?->label(),
            'metadata' => (object) SourceVisibility::filterMetadata($this->metadata, SourceVisibility::PUBLIC_MAP_METADATA, $request->user()),
        ];
    }
}
