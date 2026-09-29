export type MarkerTypeData = {
    id: number;
    slug: string;
    name: string;
    icon: string;
    color: string;
    geometry: 'point' | 'polygon' | 'polyline';
};

export type MarkerCategoryData = {
    id: number;
    slug: string;
    name: string;
    color: string;
    icon: string;
    visible_by_default: boolean;
    types: MarkerTypeData[];
};

export type Point = [number, number];

export type MapMarker = {
    id: number;
    type_id: number;
    name: string;
    x: number | null;
    y: number | null;
    geometry: Point[] | null;
    floor: string | null;
    variant?: string | null;
    confidence: number;
    status: string;
};

export type VariantOption = { key: string; label: string; count: number };

export type MapData = {
    id: number;
    slug: string;
    name: string;
    summary: string | null;
    description: string | null;
    status: string;
    image_url: string | null;
    image_attribution: string | null;
    width: number;
    height: number;
    source_url: string | null;
    game_version: string | null;
    metadata: {
        region?: string;
        region_note?: string;
        variants?: string[];
        facts?: { text: string; source_url?: string; confidence?: string }[];
        variant_options?: VariantOption[];
        calibration?: {
            lat: [number, number];
            lng: [number, number];
            source?: string;
        };
        [key: string]: unknown;
    };
};

export type MapSummary = {
    id: number;
    slug: string;
    name: string;
    summary: string | null;
    status: string;
    image_url: string | null;
    markers_count?: number;
};

export type MarkerDetail = {
    id: number;
    name: string;
    description: string | null;
    x: number | null;
    y: number | null;
    floor: string | null;
    variant: string | null;
    status: string;
    map: { slug: string; name: string };
    type: { id: number; name: string; category: string; category_slug: string };
    items: {
        slug: string;
        name: string;
        rarity: string | null;
        likelihood: string | null;
        note: string | null;
    }[];
    loot_table: {
        name: string;
        items: {
            slug: string;
            name: string;
            rarity: string | null;
            chance: number | null;
        }[];
    } | null;
    objectives: {
        slug: string;
        name: string;
        kind: string;
        role: string | null;
    }[];
    confidence: {
        score: number;
        label: string;
        confirmations: number;
        open_reports: number;
    };
    /** Only present for editors/admins. */
    source?: {
        name: string | null;
        kind: string | null;
        url: string | null;
    } | null;
    source_note?: string | null;
    last_verified_at: string | null;
    introduced_version: string | null;
    verified_version: string | null;
    metadata: { conditions?: string; [key: string]: unknown };
};

export type MapNote = {
    id: number;
    x: number;
    y: number;
    title: string;
    body: string | null;
    color: string | null;
    is_shared: boolean;
};

export type RoutePoint = {
    x: number;
    y: number;
    marker_id?: number | null;
    label?: string | null;
};

export type RaidRoute = {
    id: number;
    name: string;
    description: string | null;
    points: RoutePoint[];
    is_public: boolean;
    share_token: string | null;
};

export type Option = { value: string; label: string };

export type SearchResult = {
    type: 'item' | 'marker' | 'objective' | 'map';
    id: number;
    slug?: string;
    name: string;
    subtitle: string;
    rarity?: string | null;
    category?: string;
    map?: { slug: string; name: string };
    x?: number | null;
    y?: number | null;
    found_at?: {
        map: { slug: string; name: string };
        markers: { id: number; name: string }[];
    }[];
};

export type SearchResponse = {
    query: string;
    total: number;
    groups: { key: string; label: string; results: SearchResult[] }[];
};
