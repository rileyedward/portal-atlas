<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class RaidRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'map_id' => [$creating ? 'required' : 'prohibited', 'integer', 'exists:maps,id'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['sometimes', 'boolean'],
            'points' => [$creating ? 'required' : 'sometimes', 'array', 'min:2', 'max:50'],
            'points.*.x' => ['required', 'numeric', 'between:0,100'],
            'points.*.y' => ['required', 'numeric', 'between:0,100'],
            'points.*.marker_id' => ['nullable', 'integer', 'exists:markers,id'],
            'points.*.label' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return list<array{x: float, y: float, marker_id: int|null, label: string|null}>
     */
    public function points(): array
    {
        /** @var list<array<string, mixed>> $points */
        $points = $this->validated('points', []);

        return array_map(fn (array $p) => [
            'x' => round((float) $p['x'], 4),
            'y' => round((float) $p['y'], 4),
            'marker_id' => isset($p['marker_id']) ? (int) $p['marker_id'] : null,
            'label' => isset($p['label']) ? strip_tags((string) $p['label']) : null,
        ], $points);
    }
}
