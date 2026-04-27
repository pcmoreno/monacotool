const confirmModal = document.getElementById('confirm-delete-modal');
globalThis.icons = {
    trash: document.getElementById('icon-trash').innerHTML,
    magnifier: document.getElementById('icon-magnifier').innerHTML,
};

let onConfirmCallback = null;

document.getElementById('confirm-delete-cancel').addEventListener('click', () => {
    confirmModal.classList.add('hidden');
    onConfirmCallback = null;
});

document.getElementById('confirm-delete-confirm').addEventListener('click', async () => {
    if (onConfirmCallback) await onConfirmCallback();
    confirmModal.classList.add('hidden');
    onConfirmCallback = null;
});

globalThis.showDeleteConfirm = (callback) => {
    onConfirmCallback = callback;
    confirmModal.classList.remove('hidden');
};
