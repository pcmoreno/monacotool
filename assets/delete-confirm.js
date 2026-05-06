export const icons = {
    get trash() {
        const tpl = document.getElementById('icon-trash');
        return tpl ? tpl.content.cloneNode(true) : null;
    },
    get magnifier() {
        const tpl = document.getElementById('icon-magnifier');
        return tpl ? tpl.content.cloneNode(true) : null;
    },
};

const callbackQueue = [];

const closeConfirm = () => {
    document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    callbackQueue.length = 0;
};

async function onClick(e) {
    if (e.target.closest('#confirm-delete-cancel')) {
        closeConfirm();
        return;
    }
    if (e.target.closest('#confirm-delete-confirm')) {
        const cb = callbackQueue.shift();
        if (cb) await cb();
        document.getElementById('confirm-delete-modal')?.classList.add('hidden');
    }
}

document.addEventListener('turbo:load', () => {
    document.removeEventListener('click', onClick);
    document.addEventListener('click', onClick);
});

document.addEventListener('turbo:before-cache', () => {
    document.removeEventListener('click', onClick);
});

export const showDeleteConfirm = (callback) => {
    callbackQueue.push(callback);
    document.getElementById('confirm-delete-modal')?.classList.remove('hidden');
};
