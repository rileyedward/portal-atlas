<?php

namespace App\Services;

use App\Enums\ItemIntent;
use App\Models\Item;
use App\Models\User;
use App\Support\Pivot;

/**
 * Answers "Should I keep this?" from the item usage graph plus the player's
 * own tracking data. Deterministic: no guesses beyond the known relationships.
 */
class KeepAdvisor
{
    /**
     * @return array{verdict: string, priority: string|null, reasons: list<string>, needed: int, owned: int, uses: int}
     */
    public function advise(Item $item, ?User $user): array
    {
        $item->loadMissing(['usedInRecipes', 'objectives']);

        $uses = $item->usedInRecipes->count() + $item->objectives->where('pivot.role', 'required')->count();
        $reasons = [];

        foreach ($item->usedInRecipes as $recipe) {
            $reasons[] = sprintf('%s: %s (×%d)', $recipe->kind->label(), $recipe->name, Pivot::int($recipe, 'quantity'));
        }
        foreach ($item->objectives->where('pivot.role', 'required') as $objective) {
            $reasons[] = sprintf('%s: %s (×%d)', $objective->kind->label(), $objective->name, Pivot::int($objective, 'quantity'));
        }

        $tracking = $user?->trackedItems()->whereKey($item->id)->first()?->pivot;
        $needed = (int) $tracking?->getAttribute('quantity_needed');
        $owned = (int) $tracking?->getAttribute('quantity_owned');
        $intentValue = $tracking?->getAttribute('intent');
        $intent = is_string($intentValue) ? ItemIntent::tryFrom($intentValue) : null;

        $verdict = match (true) {
            in_array($intent, [ItemIntent::DontNeed, ItemIntent::Sell], true) => 'sell',
            in_array($intent, [ItemIntent::Need, ItemIntent::Keep, ItemIntent::Quest, ItemIntent::Upgrade], true) => 'keep',
            $needed > $owned => 'keep',
            $uses > 0 => 'keep',
            default => 'unknown',
        };

        $priority = match (true) {
            $verdict !== 'keep' => null,
            $needed > $owned => 'high',
            $intent !== null => 'medium',
            default => 'low',
        };

        return compact('verdict', 'priority', 'reasons', 'needed', 'owned', 'uses');
    }
}
