import { apiFetch } from 'csrf';

let pendingTeamId = null;
let pendingTeamName = null;

const modal = () => document.getElementById('delete-team-modal');
const input = () => document.getElementById('delete-team-confirm-input');
const confirmBtn = () => document.getElementById('delete-team-confirm');
const errorEl = () => document.getElementById('delete-team-error');

const openModal = (teamId, teamName) => {
    pendingTeamId = teamId;
    pendingTeamName = teamName;
    document.getElementById('delete-team-name-hint').textContent = teamName;
    input().value = '';
    confirmBtn().disabled = true;
    errorEl().classList.add('hidden');
    modal().classList.remove('hidden');
    input().focus();
};

const closeModal = () => {
    modal().classList.add('hidden');
    pendingTeamId = null;
    pendingTeamName = null;
};

const deleteTeam = async () => {
    const btn = confirmBtn();
    btn.disabled = true;

    try {
        const response = await apiFetch(`/team/${pendingTeamId}`, { method: 'DELETE' });

        if (response.ok) {
            window.location.href = '/team';
        } else {
            errorEl().textContent = 'Something went wrong. Please try again.';
            errorEl().classList.remove('hidden');
            btn.disabled = false;
        }
    } catch {
        errorEl().textContent = 'Something went wrong. Please try again.';
        errorEl().classList.remove('hidden');
        btn.disabled = false;
    }
};

document.addEventListener('click', (e) => {
    const trigger = e.target.closest('#open-delete-team-modal');
    if (trigger) {
        openModal(trigger.dataset.teamId, trigger.dataset.teamName);
        return;
    }
    if (e.target.closest('#delete-team-cancel') || e.target.closest('#delete-team-backdrop')) {
        closeModal();
        return;
    }
    if (e.target.closest('#delete-team-confirm') && !confirmBtn().disabled) {
        deleteTeam();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal().classList.contains('hidden')) {
        closeModal();
    }
});

document.addEventListener('input', (e) => {
    if (e.target.id !== 'delete-team-confirm-input') return;
    confirmBtn().disabled = e.target.value !== pendingTeamName;
});
