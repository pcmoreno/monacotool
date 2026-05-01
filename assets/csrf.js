const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export const apiFetch = (input, init = {}) => {
    const method = (init.method ?? 'GET').toUpperCase();
    if (['POST', 'PATCH', 'PUT', 'DELETE'].includes(method)) {
        init.headers = { 'X-CSRF-Token': csrfToken(), ...init.headers };
    }
    return fetch(input, init);
};
