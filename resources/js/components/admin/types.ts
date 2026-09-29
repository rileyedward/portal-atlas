export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** Shape of Laravel's LengthAwarePaginator when serialized by Inertia. */
export type Paginated<T> = {
    data: T[];
    links: PaginatorLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type IdName = { id: number; name: string };

export type VersionOption = { id: number; version: string };

export type SelectOption = { value: string | number; label: string };

export function slugify(value: string): string {
    return value
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    // Date-only values (e.g. release dates) are serialized as UTC midnight;
    // format them in UTC so they don't shift a day in western time zones.
    const dateOnly = /^\d{4}-\d{2}-\d{2}(T00:00:00(\.0+)?Z)?$/.test(value);

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        ...(dateOnly ? { timeZone: 'UTC' } : {}),
    });
}

export const tableClass = 'w-full text-left text-sm';
export const theadClass =
    'sticky top-0 z-10 bg-card text-[11px] tracking-wider text-muted-foreground uppercase shadow-[inset_0_-1px_0_var(--border)]';
export const rowClass = 'border-t transition-colors hover:bg-accent/30';

/** One editable row of an item list (objective items, recipe ingredients). */
export type ItemRow = {
    uid: string;
    id: number | null;
    quantity: string;
    role?: 'required' | 'reward';
};

export function toItemRows(
    rows:
        | { id: number; quantity: number | null; role?: string | null }[]
        | undefined,
    withRole = false,
): ItemRow[] {
    return (rows ?? []).map((row) => ({
        uid: `${row.id}-${Math.random().toString(36).slice(2, 8)}`,
        id: row.id,
        quantity: String(row.quantity ?? 1),
        ...(withRole
            ? {
                  role:
                      row.role === 'reward'
                          ? ('reward' as const)
                          : ('required' as const),
              }
            : {}),
    }));
}

export function fromItemRows(
    rows: ItemRow[],
): { id: number | null; quantity: string; role?: string }[] {
    return rows.map(({ id, quantity, role }) =>
        role ? { id, quantity, role } : { id, quantity },
    );
}
