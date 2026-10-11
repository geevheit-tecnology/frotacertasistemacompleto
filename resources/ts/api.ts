export type ApiRecord = Record<string, unknown> & { id?: string };

export type AppUser = {
    name?: string;
    email?: string;
};

export type AppState = {
    token: string;
    user: AppUser | null;
    erpModule: string;
    erpRecords: ApiRecord[];
    erpEditingId: string | null;
};

export type LoginResponse = {
    access_token: string;
    user: AppUser;
};

export const api = async <T>(state: AppState, path: string, options: RequestInit = {}): Promise<T> => {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
        ...(options.headers || {}),
    };

    const response = await fetch(path, { ...options, headers });
    const text = await response.text();
    const payload = text ? JSON.parse(text) : null;

    if (!response.ok) {
        throw new Error(payload?.message || 'Falha na requisicao.');
    }

    return payload as T;
};

export const asPageData = <T>(payload: { data?: T[] } | T[] | null | undefined): T[] => {
    if (Array.isArray(payload)) {
        return payload;
    }

    return payload?.data || [];
};
