import { csrfToken } from './csrf.js';

const forecastModal = document.getElementById('forecast-modal');
const forecastBackdrop = document.getElementById('forecast-backdrop');
const forecastOpenBtn = document.getElementById('open-forecast-modal');
const forecastSubmitBtn = document.getElementById('forecast-submit');
const forecastOutputInput = document.getElementById('forecast-target-output');
const forecastIterationsInput = document.getElementById('forecast-target-iterations');

const openForecast = () => {
    forecastModal.classList.remove('hidden');
    forecastOutputInput.focus();
};

const closeForecast = () => {
    forecastModal.classList.add('hidden');
    forecastOutputInput.value = '';
    forecastIterationsInput.value = '';
};

forecastOpenBtn.addEventListener('click', openForecast);
forecastBackdrop.addEventListener('click', closeForecast);
document.getElementById('close-forecast-modal').addEventListener('click', closeForecast);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeForecast(); });

forecastSubmitBtn.addEventListener('click', async () => {
    const teamId = forecastSubmitBtn.dataset.teamId;
    const targetOutput = parseInt(forecastOutputInput.value, 10);
    const targetIterations = parseInt(forecastIterationsInput.value, 10);

    if (!targetOutput || targetOutput < 1 || !targetIterations || targetIterations < 1) return;

    forecastSubmitBtn.disabled = true;

    try {
        const response = await fetch(`/team/${teamId}/forecast`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken() },
            body: JSON.stringify({ targetOutput, targetIterations }),
        });

        if (response.ok) {
            const data = await response.json();
            closeForecast();
            addForecastRow(data);
        }
    } finally {
        forecastSubmitBtn.disabled = false;
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
    tr.innerHTML = `
        <td class="py-2.5">
            <div class="flex items-center gap-2">
                <button type="button" class="text-graphite-400 hover:text-graphite-600 transition">${globalThis.icons.magnifier}</button>
                <button type="button" data-delete-forecast="${forecast.id}" class="text-graphite-400 hover:text-red-500 transition">${globalThis.icons.trash}</button>
            </div>
        </td>
        <td class="py-2.5 text-graphite-600">${forecast.createdAt}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${forecast.targetOutput}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${forecast.targetIterations}</td>
        <td class="py-2.5 text-graphite-900 font-semibold text-right">${probability}</td>
    `;

    document.getElementById('forecasts-tbody').prepend(tr);
};

document.getElementById('forecasts-tbody').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-delete-forecast]');
    if (!btn) return;

    const forecastId = btn.dataset.deleteForecast;
    const row = btn.closest('tr');

    globalThis.showDeleteConfirm(async () => {
        const response = await fetch(`/forecast/${forecastId}`, { method: 'DELETE', headers: { 'X-CSRF-Token': csrfToken() } });

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
        }
    });
});
