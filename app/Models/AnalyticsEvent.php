<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $term
 * @property int|null $result_count
 */
#[Fillable(['name', 'subject_type', 'subject_id', 'term', 'result_count'])]
class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;
}
