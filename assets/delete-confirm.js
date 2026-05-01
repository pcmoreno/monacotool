globalThis.icons = {
    trash: document.getElementById('icon-trash').innerHTML,
    magnifier: document.getElementById('icon-magnifier').innerHTML,
};

let onConfirmCallback = null;

const closeConfirm = () => {
    document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    onConfirmCallback = null;
};

document.addEventListener('click', async (e) => {
    if (e.target.closest('#confirm-delete-cancel')) {
        closeConfirm();
        return;
    }
    if (e.target.closest('#confirm-delete-confirm')) {
        const cb = onConfirmCallback;
        onConfirmCallback = null;
        if (cb) await cb();
        document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    }
});

globalThis.showDeleteConfirm = (callback) => {
    onConfirmCallback = callback;
    document.getElementById('confirm-delete-modal')?.classList.remove('hidden');
};
