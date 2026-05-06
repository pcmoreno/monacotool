import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';
import { openModal, closeModal, isModalOpen } from 'modal';

const openTeamCreate = () => openModal('team-create-modal', 'team-create-name');

const closeTeamCreate = () => {
    closeModal('team-create-modal');
    const input = document.getElementById('team-create-name');
    if (input) input.value = '';
};

const submitTeamCreate = async () => {
    const input = document.getElementById('team-create-name');
    const btn = document.getElementById('team-create-submit');
    const name = input.value.trim();
    if (!name) return;

    btn.disabled = true;

    try {
        const response = await apiFetch('/team', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name }),
        });

        if (response.ok) {
            const team = await response.json();
            closeTeamCreate();
            addTeamCard(team);
        } else {
            showToast(await errorMessageFromResponse(response, 'Could not create team.'));
        }
    } catch (e) {
        if (!(e instanceof TypeError)) throw e;
        showToast('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
};

function onClick(e) {
    if (e.target.closest('#open-team-create-modal')) { openTeamCreate(); return; }
    if (e.target.closest('#close-team-create-modal')) { closeTeamCreate(); return; }
    if (e.target.closest('#team-create-backdrop')) { closeTeamCreate(); return; }
    if (e.target.closest('#team-create-submit')) { submitTeamCreate(); return; }

    if (e.target.closest('#close-welcome-modal') || e.target.closest('#welcome-dismiss')) {
        document.getElementById('welcome-modal')?.classList.add('hidden');
        return;
    }
    if (e.target.closest('#welcome-create-team')) {
        document.getElementById('welcome-modal')?.classList.add('hidden');
        openTeamCreate();
        return;
    }
}

function onKeydown(e) {
    if (e.key === 'Escape' && isModalOpen('team-create-modal')) { closeTeamCreate(); return; }
    if (e.key === 'Enter' && e.target.id === 'team-create-name') {
        e.preventDefault();
        submitTeamCreate();
    }
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

const addTeamCard = (team) => {
    const empty = document.getElementById('teams-empty');
    if (empty) empty.remove();

    let grid = document.getElementById('teams-grid');
    if (!grid) {
        grid = document.createElement('div');
        grid.id = 'teams-grid';
        grid.className = 'grid gap-4 sm:grid-cols-2';
        document.getElementById('teams-container').appendChild(grid);
    }

    const a = document.createElement('a');
    a.href = `/team/${team.id}`;
    a.className = 'block rounded-xl bg-surface shadow ring-1 ring-graphite-200 p-6 hover:shadow-md transition';

    const h2 = document.createElement('h2');
    h2.className = 'text-lg font-semibold text-graphite-900';
    h2.textContent = team.name;

    a.append(h2);
    grid.appendChild(a);
};
