export const openModal = (id, focusId) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    el.setAttribute('aria-hidden', 'false');
    const target = focusId ? document.getElementById(focusId) : el.querySelector('button, input, [tabindex="0"]');
    target?.focus();
};

export const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    el.setAttribute('aria-hidden', 'true');
};

export const isModalOpen = (id) => {
    const el = document.getElementById(id);
    return !!el && !el.classList.contains('hidden');
};
