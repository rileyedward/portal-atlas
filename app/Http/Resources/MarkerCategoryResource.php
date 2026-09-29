<?php

namespace App\Http\Resources;

use App\Models\MarkerCategory;
use App\Models\MarkerType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MarkerCategory
 */
class MarkerCategoryResource extends JsonResource
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
            'color' => $this->color,
            'icon' => $this->icon,
            'visible_by_default' => $this->visible_by_default,
            'types' => $this->types->map(fn (MarkerType $type) => [
                'id' => $type->id,
                'slug' => $type->slug,
                'name' => $type->name,
                'icon' => $type->icon ?? $this->icon,
                'color' => $type->color ?? $this->color,
                'geometry' => $type->geometry->value,
            ])->values(),
        ];
    }
}
