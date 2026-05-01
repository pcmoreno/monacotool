import { csrfToken } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';

const openTeamCreate = () => {
    document.getElementById('team-create-modal').classList.remove('hidden');
    document.getElementById('team-create-name').focus();
};

const closeTeamCreate = () => {
    const modal = document.getElementById('team-create-modal');
    if (!modal) return;
    modal.classList.add('hidden');
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
        const response = await fetch('/team', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
            body: JSON.stringify({ name }),
        });

        if (response.ok) {
            const team = await response.json();
            closeTeamCreate();
            addTeamCard(team);
        } else {
            showToast(await errorMessageFromResponse(response, 'Could not create team.'));
        }
    } catch {
        showToast('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
};

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-team-create-modal')) { openTeamCreate(); return; }
    if (e.target.closest('#close-team-create-modal')) { closeTeamCreate(); return; }
    if (e.target.closest('#team-create-backdrop')) { closeTeamCreate(); return; }
    if (e.target.closest('#team-create-submit')) { submitTeamCreate(); return; }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('team-create-modal')?.classList.contains('hidden')) {
        closeTeamCreate();
        return;
    }
    if (e.key === 'Enter' && e.target.id === 'team-create-name') {
        e.preventDefault();
        submitTeamCreate();
    }
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
