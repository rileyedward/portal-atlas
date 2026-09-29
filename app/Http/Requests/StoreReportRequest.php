<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['nullable', Rule::in(['marker', 'item', 'objective', 'map'])],
            'subject_id' => ['required_with:subject_type', 'nullable', 'integer'],
            'type' => ['required', Rule::enum(ReportType::class)],
            // General feedback needs words; feedback on a specific thing can be just the type.
            'message' => ['required_without:subject_type', 'nullable', 'string', 'min:5', 'max:2000'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:255'],
            'suggested_x' => ['nullable', 'required_with:suggested_y', 'numeric', 'between:0,100'],
            'suggested_y' => ['nullable', 'required_with:suggested_x', 'numeric', 'between:0,100'],
            // Honeypot: real users never fill this.
            'website' => ['prohibited'],
        ];
    }

    /**
     * Only keep the path and query of the page URL (never another host).
     */
    public function pagePath(): ?string
    {
        $url = $this->string('page_url')->trim()->toString();
        if ($url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($path) && str_starts_with($path, '/')
            ? substr($path.(is_string($query) ? '?'.$query : ''), 0, 500)
            : null;
    }
}
