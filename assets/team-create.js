import { csrfToken } from './csrf.js';

const teamCreateModal = document.getElementById('team-create-modal');
const teamCreateBackdrop = document.getElementById('team-create-backdrop');
const teamCreateNameInput = document.getElementById('team-create-name');
const teamCreateSubmitBtn = document.getElementById('team-create-submit');

const openTeamCreate = () => { teamCreateModal.classList.remove('hidden'); teamCreateNameInput.focus(); };
const closeTeamCreate = () => { teamCreateModal.classList.add('hidden'); teamCreateNameInput.value = ''; };

document.getElementById('open-team-create-modal').addEventListener('click', openTeamCreate);
document.getElementById('close-team-create-modal').addEventListener('click', closeTeamCreate);
teamCreateBackdrop.addEventListener('click', closeTeamCreate);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTeamCreate(); });

teamCreateSubmitBtn.addEventListener('click', async () => {
    const name = teamCreateNameInput.value.trim();
    if (!name) return;

    teamCreateSubmitBtn.disabled = true;

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
        }
    } finally {
        teamCreateSubmitBtn.disabled = false;
    }
});

teamCreateNameInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); teamCreateSubmitBtn.click(); }
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
    a.innerHTML = `
        <h2 class="text-lg font-semibold text-graphite-900">${team.name}</h2>
        <p class="text-sm text-graphite-500 mt-1">0 iterations</p>
    `;
    grid.appendChild(a);
};
