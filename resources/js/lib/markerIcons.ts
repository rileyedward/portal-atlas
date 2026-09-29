import type { IconNode } from 'lucide';
import {
    Archive,
    Boxes,
    Box,
    Car,
    Crown,
    Diamond,
    FileText,
    Hand,
    HeartPulse,
    Key,
    Lock,
    LogOut,
    Orbit,
    PackageOpen,
    Puzzle,
    Timer,
    Vault,
    Atom,
    Bug,
    Building2,
    CircleHelp,
    Crosshair,
    DoorOpen,
    FileSignature,
    Flag,
    Gem,
    KeyRound,
    Landmark,
    LocateFixed,
    MapPin,
    Package,
    Pickaxe,
    Route,
    ScrollText,
    Search,
    Shuffle,
    Skull,
    Sparkles,
    SquareDashed,
    StickyNote,
    Swords,
    Target,
    Tent,
    TriangleAlert,
    createElement,
} from 'lucide';

/**
 * Icon keys stored in the database (marker_types.icon) mapped to Lucide icons.
 * Unknown keys fall back to a question mark rather than breaking the map.
 */
const ICONS: Record<string, IconNode> = {
    boxes: Boxes,
    box: Box,
    car: Car,
    crown: Crown,
    diamond: Diamond,
    'file-text': FileText,
    hand: Hand,
    'heart-pulse': HeartPulse,
    key: Key,
    lock: Lock,
    'log-out': LogOut,
    orbit: Orbit,
    'package-open': PackageOpen,
    puzzle: Puzzle,
    timer: Timer,
    vault: Vault,
    archive: Archive,
    atom: Atom,
    bug: Bug,
    'building-2': Building2,
    crosshair: Crosshair,
    'door-open': DoorOpen,
    'file-signature': FileSignature,
    flag: Flag,
    gem: Gem,
    'key-round': KeyRound,
    landmark: Landmark,
    'locate-fixed': LocateFixed,
    'map-pin': MapPin,
    package: Package,
    pickaxe: Pickaxe,
    route: Route,
    'scroll-text': ScrollText,
    search: Search,
    shuffle: Shuffle,
    skull: Skull,
    sparkles: Sparkles,
    'square-dashed': SquareDashed,
    'sticky-note': StickyNote,
    swords: Swords,
    target: Target,
    tent: Tent,
    'triangle-alert': TriangleAlert,
};

export const ICON_KEYS = Object.keys(ICONS);

const cache = new Map<string, string>();

export function iconSvg(key: string): string {
    const cached = cache.get(key);

    if (cached) {
        return cached;
    }

    const element = createElement(ICONS[key] ?? CircleHelp, {
        'stroke-width': 2.5,
        'aria-hidden': 'true',
    });
    const html = element.outerHTML;
    cache.set(key, html);

    return html;
}
