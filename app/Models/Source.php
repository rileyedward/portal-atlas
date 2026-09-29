<?php

namespace App\Models;

use App\Enums\SourceKind;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property SourceKind $kind
 * @property string|null $url
 * @property int $reliability
 * @property string|null $notes
 */
#[Fillable(['name', 'kind', 'url', 'reliability', 'notes'])]
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => SourceKind::class,
            'reliability' => 'integer',
        ];
    }
}
