import { apiFetch } from 'csrf';
import { errorMessageFromResponse } from 'toast';
import { openModal, closeModal, isModalOpen } from 'modal';

const closeInvite = () => {
    closeModal('invite-modal');
    document.getElementById('invite-name').value = '';
    document.getElementById('invite-email').value = '';
    document.getElementById('invite-error').classList.add('hidden');
    document.getElementById('invite-error').textContent = '';
};

const openInvite = () => openModal('invite-modal', 'invite-name');

const submitInvite = async () => {
    const btn = document.getElementById('invite-submit');
    const teamId = btn.dataset.teamId;
    const name = document.getElementById('invite-name').value.trim();
    const email = document.getElementById('invite-email').value.trim();
    const errorEl = document.getElementById('invite-error');

    errorEl.classList.add('hidden');

    if (!name || !email) return;

    btn.disabled = true;

    const showError = (msg) => {
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    };

    try {
        const response = await apiFetch(`/team/${teamId}/invite`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email }),
        });

        if (response.ok) {
            closeInvite();
            addPendingMember(name, email);
        } else {
            showError(await errorMessageFromResponse(response, 'Could not send invitation.'));
        }
    } catch (e) {
        if (!(e instanceof TypeError)) throw e;
        showError('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
};

const addPendingMember = (name, email) => {
    const ul = document.querySelector('.space-y-2');
    if (!ul) return;

    const nameSpan = document.createElement('span');
    nameSpan.className = 'text-sm text-graphite-400';
    nameSpan.textContent = name || email;

    const invitedBadge = document.createElement('span');
    invitedBadge.className = 'text-xs text-amber-500 font-medium';
    invitedBadge.textContent = 'Invited';

    const roleSpan = document.createElement('span');
    roleSpan.className = 'text-xs text-graphite-400 font-medium';
    roleSpan.textContent = 'User';

    const badgeGroup = document.createElement('div');
    badgeGroup.className = 'flex items-center gap-2';
    badgeGroup.appendChild(invitedBadge);
    badgeGroup.appendChild(roleSpan);

    const li = document.createElement('li');
    li.className = 'flex items-center justify-between';
    li.appendChild(nameSpan);
    li.appendChild(badgeGroup);
    ul.appendChild(li);
};

function onClick(e) {
    if (e.target.closest('#open-invite-modal')) { openInvite(); return; }
    if (e.target.closest('#close-invite-modal')) { closeInvite(); return; }
    if (e.target.closest('#invite-backdrop')) { closeInvite(); return; }
    if (e.target.closest('#invite-submit')) { submitInvite(); return; }
}

function onKeydown(e) {
    if (e.key === 'Escape' && isModalOpen('invite-modal')) closeInvite();
}

document.addEventListener('turbo:load', () => {
    document.removeEventListener('click', onClick);
    document.removeEventListener('keydown', onKeydown);
    document.addEventListener('click', onClick);
    document.addEventListener('keydown', onKeydown);
});

document.addEventListener('turbo:before-cache', () => {
    document.removeEventListener('click', onClick);
    document.removeEventListener('keydown', onKeydown);
});
