import { apiFetch } from 'csrf';
import { errorMessageFromResponse } from 'toast';

const openInvite = () => {
    document.getElementById('invite-modal').classList.remove('hidden');
    document.getElementById('invite-name').focus();
};

const closeInvite = () => {
    document.getElementById('invite-modal').classList.add('hidden');
    document.getElementById('invite-name').value = '';
    document.getElementById('invite-email').value = '';
    document.getElementById('invite-error').classList.add('hidden');
    document.getElementById('invite-error').textContent = '';
};

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
    } catch {
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

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-invite-modal')) { openInvite(); return; }
    if (e.target.closest('#close-invite-modal')) { closeInvite(); return; }
    if (e.target.closest('#invite-backdrop')) { closeInvite(); return; }
    if (e.target.closest('#invite-submit')) { submitInvite(); return; }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('invite-modal')?.classList.contains('hidden')) {
        closeInvite();
    }
});
