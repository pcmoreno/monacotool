export const showToast = (message, type = 'error') => {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const variants = {
        error: 'bg-red-50 text-red-700 ring-red-200',
        success: 'bg-green-50 text-green-700 ring-green-200',
    };

    const toast = document.createElement('div');
    toast.className = `rounded-md px-4 py-3 text-sm shadow-lg ring-1 ${variants[type] ?? variants.error}`;
    toast.dataset.turboTemporary = '';
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => toast.remove(), 4000);
};

export const errorMessageFromResponse = async (response, fallback = 'Something went wrong. Please try again.') => {
    try {
        const data = await response.json();
        if (Array.isArray(data.errors) && data.errors.length) return data.errors.join(' ');
        if (typeof data.error === 'string') return data.error;
    } catch {
        /* not JSON */
    }
    return fallback;
};
