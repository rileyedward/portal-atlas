<?php

namespace App\Models;

use Database\Factories\GameVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $version
 * @property string|null $name
 * @property Carbon|null $released_at
 * @property bool $is_current
 * @property string|null $notes
 * @property string|null $source_url
 */
#[Fillable(['version', 'name', 'released_at', 'is_current', 'notes', 'source_url'])]
class GameVersion extends Model
{
    /** @use HasFactory<GameVersionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'released_at' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->latest('released_at')->first();
    }

    public function label(): string
    {
        return $this->name ? "{$this->version} ({$this->name})" : $this->version;
    }
}
