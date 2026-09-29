/**
 * Minimal JSON client for the app's session-authenticated endpoints.
 * Sends Laravel's XSRF token so CSRF protection stays on for every write.
 */
export class HttpError extends Error {
    constructor(
        public status: number,
        public data: { message?: string; errors?: Record<string, string[]> },
    ) {
        super(data.message ?? `Request failed (${status})`);
    }

    firstError(): string {
        const errors = Object.values(this.data.errors ?? {}).flat();

        return errors[0] ?? this.message;
    }
}

function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
}

export async function http<T = unknown>(
    method: 'get' | 'post' | 'put' | 'patch' | 'delete',
    url: string,
    body?: unknown,
    signal?: AbortSignal,
): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const token = xsrfToken();

    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method: method.toUpperCase(),
        headers,
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
        signal,
    });

    if (response.status === 204) {
        return undefined as T;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new HttpError(response.status, data);
    }

    return data as T;
}
