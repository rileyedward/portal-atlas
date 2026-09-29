<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class MapNoteRequest extends FormRequest
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
            'x' => [$creating ? 'required' : 'sometimes', 'numeric', 'between:0,100'],
            'y' => [$creating ? 'required' : 'sometimes', 'numeric', 'between:0,100'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }
}
