import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';
import { icons, showDeleteConfirm } from 'delete-confirm';

const openForecast = () => {
    document.getElementById('forecast-modal').classList.remove('hidden');
    document.getElementById('forecast-target-output').focus();
};

const closeForecast = () => {
    const modal = document.getElementById('forecast-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    const output = document.getElementById('forecast-target-output');
    const iterations = document.getElementById('forecast-target-iterations');
    if (output) output.value = '';
    if (iterations) iterations.value = '';
};

const submitForecast = async () => {
    const btn = document.getElementById('forecast-submit');
    const teamId = btn.dataset.teamId;
    const targetOutput = parseInt(document.getElementById('forecast-target-output').value, 10);
    const targetIterations = parseInt(document.getElementById('forecast-target-iterations').value, 10);

    if (!targetOutput || targetOutput < 1 || targetOutput > 100000 || !targetIterations || targetIterations < 1 || targetIterations > 100) return;

    btn.disabled = true;

    try {
        const response = await apiFetch(`/team/${teamId}/forecast`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetOutput, targetIterations }),
        });

        if (response.ok) {
            const data = await response.json();
            closeForecast();
            addForecastRow(data);
        } else {
            showToast(await errorMessageFromResponse(response, 'Could not run forecast.'));
        }
    } catch {
        showToast('Network error. Please try again.');
    } finally {
        btn.disabled = false;
    }
};

const deleteForecast = (forecastId, row) => {
    showDeleteConfirm(async () => {
        try {
            const response = await apiFetch(`/forecast/${forecastId}`, { method: 'DELETE' });

            if (response.ok) {
                row.remove();

                const tbody = document.getElementById('forecasts-tbody');
                if (!tbody.querySelector('tr')) {
                    tbody.closest('table').classList.add('hidden');
                    const p = document.createElement('p');
                    p.id = 'forecasts-empty';
                    p.className = 'text-sm text-graphite-400';
                    p.textContent = 'No forecasts yet.';
                    tbody.closest('table').before(p);
                }
            } else {
                showToast(await errorMessageFromResponse(response, 'Could not delete forecast.'));
            }
        } catch {
            showToast('Network error. Please try again.');
        }
    });
};

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-forecast-modal')) { openForecast(); return; }
    if (e.target.closest('#close-forecast-modal')) { closeForecast(); return; }
    if (e.target.closest('#forecast-backdrop')) { closeForecast(); return; }
    if (e.target.closest('#forecast-submit')) { submitForecast(); return; }

    const deleteBtn = e.target.closest('[data-delete-forecast]');
    if (deleteBtn) {
        deleteForecast(deleteBtn.dataset.deleteForecast, deleteBtn.closest('tr'));
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('forecast-modal')?.classList.contains('hidden')) {
        closeForecast();
    }
});

const addForecastRow = (forecast) => {
    const empty = document.getElementById('forecasts-empty');
    if (empty) empty.remove();

    const table = document.getElementById('forecasts-tbody').closest('table');
    table.classList.remove('hidden');

    const probability = forecast.result !== null
        ? (forecast.result * 100).toFixed(1) + '%'
        : '—';

    const tr = document.createElement('tr');
    tr.dataset.forecastId = forecast.id;

    const magnifierBtn = document.createElement('button');
    magnifierBtn.type = 'button';
    magnifierBtn.className = 'text-graphite-400 hover:text-graphite-600 transition';
    magnifierBtn.innerHTML = icons.magnifier;

    const trashBtn = document.createElement('button');
    trashBtn.type = 'button';
    trashBtn.dataset.deleteForecast = forecast.id;
    trashBtn.className = 'text-graphite-400 hover:text-red-500 transition';
    trashBtn.innerHTML = icons.trash;

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'flex items-center gap-2';
    actionsDiv.appendChild(magnifierBtn);
    actionsDiv.appendChild(trashBtn);

    const actionsTd = document.createElement('td');
    actionsTd.className = 'py-2.5';
    actionsTd.appendChild(actionsDiv);

    const createdAtTd = document.createElement('td');
    createdAtTd.className = 'py-2.5 text-graphite-600';
    createdAtTd.textContent = forecast.createdAt;

    const targetOutputTd = document.createElement('td');
    targetOutputTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    targetOutputTd.textContent = forecast.targetOutput;

    const targetIterationsTd = document.createElement('td');
    targetIterationsTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    targetIterationsTd.textContent = forecast.targetIterations;

    const probabilityTd = document.createElement('td');
    probabilityTd.className = 'py-2.5 text-graphite-900 font-semibold text-right';
    probabilityTd.textContent = probability;

    tr.appendChild(actionsTd);
    tr.appendChild(createdAtTd);
    tr.appendChild(targetOutputTd);
    tr.appendChild(targetIterationsTd);
    tr.appendChild(probabilityTd);

    document.getElementById('forecasts-tbody').prepend(tr);
};
