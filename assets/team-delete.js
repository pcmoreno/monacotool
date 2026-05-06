import { apiFetch } from 'csrf';
import { openModal, closeModal, isModalOpen } from 'modal';

let pendingTeamId = null;
let pendingTeamName = null;

const modal = () => document.getElementById('delete-team-modal');
const input = () => document.getElementById('delete-team-confirm-input');
const confirmBtn = () => document.getElementById('delete-team-confirm');
const errorEl = () => document.getElementById('delete-team-error');

const openDeleteModal = (teamId, teamName) => {
    pendingTeamId = teamId;
    pendingTeamName = teamName;
    document.getElementById('delete-team-name-hint').textContent = teamName;
    input().value = '';
    confirmBtn().disabled = true;
    errorEl().classList.add('hidden');
    openModal('delete-team-modal', 'delete-team-confirm-input');
};

const closeDeleteModal = () => {
    closeModal('delete-team-modal');
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
    } catch (e) {
        if (!(e instanceof TypeError)) throw e;
        errorEl().textContent = 'Something went wrong. Please try again.';
        errorEl().classList.remove('hidden');
        btn.disabled = false;
    }
};

function onClick(e) {
    const trigger = e.target.closest('#open-delete-team-modal');
    if (trigger) {
        openDeleteModal(trigger.dataset.teamId, trigger.dataset.teamName);
        return;
    }
    if (e.target.closest('#delete-team-cancel') || e.target.closest('#delete-team-backdrop')) {
        closeDeleteModal();
        return;
    }
    if (e.target.closest('#delete-team-confirm') && !confirmBtn().disabled) {
        deleteTeam();
    }
}

function onKeydown(e) {
    if (e.key === 'Escape' && isModalOpen('delete-team-modal')) closeDeleteModal();
}

function onInput(e) {
    if (e.target.id !== 'delete-team-confirm-input') return;
    confirmBtn().disabled = e.target.value !== pendingTeamName;
}

document.addEventListener('turbo:load', () => {
    document.removeEventListener('click', onClick);
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('input', onInput);
    document.addEventListener('click', onClick);
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('input', onInput);
});

document.addEventListener('turbo:before-cache', () => {
    document.removeEventListener('click', onClick);
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('input', onInput);
});
