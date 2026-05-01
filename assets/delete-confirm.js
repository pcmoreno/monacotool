globalThis.icons = {
    trash: document.getElementById('icon-trash').innerHTML,
    magnifier: document.getElementById('icon-magnifier').innerHTML,
};

const callbackQueue = [];

const closeConfirm = () => {
    document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    callbackQueue.length = 0;
};

document.addEventListener('click', async (e) => {
    if (e.target.closest('#confirm-delete-cancel')) {
        closeConfirm();
        return;
    }
    if (e.target.closest('#confirm-delete-confirm')) {
        const cb = callbackQueue.shift();
        if (cb) await cb();
        document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    }
});

globalThis.showDeleteConfirm = (callback) => {
    callbackQueue.push(callback);
    document.getElementById('confirm-delete-modal')?.classList.remove('hidden');
};
