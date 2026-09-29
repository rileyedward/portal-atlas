import type { Option } from '@/types/game';

/** Mirrors App\Enums\ItemIntent. */
export const ITEM_INTENTS: Option[] = [
    { value: 'need', label: 'Need' },
    { value: 'have', label: 'Have' },
    { value: 'dont_need', label: "Don't need" },
    { value: 'keep', label: 'Keep' },
    { value: 'sell', label: 'Sell' },
    { value: 'quest', label: 'Quest item' },
    { value: 'upgrade', label: 'Upgrade item' },
];

/** The game's documented rarity tiers, in ascending order. */
export const RARITY_ORDER = ['common', 'uncommon', 'rare', 'epic'];

export function rarityRank(rarity: string | null): number {
    const index = rarity ? RARITY_ORDER.indexOf(rarity.toLowerCase()) : -1;

    return index === -1 ? RARITY_ORDER.length : index;
}

export function formatDate(value: string | null): string {
    return value
        ? new Date(value).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : 'Never';
}

export const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
