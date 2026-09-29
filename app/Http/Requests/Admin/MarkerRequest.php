<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageContent();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $required = $creating ? 'required' : 'sometimes';

        return [
            'marker_type_id' => [$required, 'integer', 'exists:marker_types,id'],
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'x' => ['nullable', 'required_with:y', 'numeric', 'between:0,100'],
            'y' => ['nullable', 'required_with:x', 'numeric', 'between:0,100'],
            'geometry' => ['nullable', 'array', 'max:500'],
            'geometry.*' => ['array', 'size:2'],
            'geometry.*.*' => ['numeric', 'between:0,100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'variant' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'loot_table_id' => ['nullable', 'integer', 'exists:loot_tables,id'],
            'status' => ['sometimes', Rule::enum(ContentStatus::class)],
            'is_visible' => ['sometimes', 'boolean'],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'source_note' => ['nullable', 'string', 'max:2000'],
            'confidence_override' => ['nullable', 'integer', 'between:0,100'],
            'introduced_version_id' => ['nullable', 'integer', 'exists:game_versions,id'],
            'verified_version_id' => ['nullable', 'integer', 'exists:game_versions,id'],
            'metadata' => ['nullable', 'array'],
            'metadata.conditions' => ['nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:items,id'],
            'items.*.likelihood' => ['nullable', Rule::in(['guaranteed', 'common', 'uncommon', 'rare'])],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            'objectives' => ['sometimes', 'array'],
            'objectives.*.id' => ['required', 'integer', 'exists:objectives,id'],
            'objectives.*.role' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function markerAttributes(): array
    {
        $attributes = $this->safe()->except(['items', 'objectives']);
        foreach (['x', 'y'] as $axis) {
            if (isset($attributes[$axis])) {
                $attributes[$axis] = round((float) $attributes[$axis], 4);
            }
        }
        if (isset($attributes['name'])) {
            $attributes['name'] = strip_tags((string) $attributes['name']);
        }

        return $attributes;
    }
}
