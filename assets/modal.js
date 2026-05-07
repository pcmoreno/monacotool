const focusStack = [];

export function openModal(id, focusId = null) {
    const modal = document.getElementById(id);
    if (!modal) return;
    focusStack.push(document.activeElement);
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.remove('hidden');
    const target = focusId
        ? document.getElementById(focusId)
        : modal.querySelector('button:not([disabled]), input:not([disabled]), [tabindex="0"]');
    target?.focus();
}

export function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.add('hidden');
    const prev = focusStack.pop();
    if (prev && document.contains(prev)) prev.focus();
}

export function isModalOpen(id) {
    return !document.getElementById(id)?.classList.contains('hidden');
}
