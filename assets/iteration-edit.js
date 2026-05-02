import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';
import { icons, showDeleteConfirm } from 'delete-confirm';

const inputClass = (extra = '') =>
    'w-full border border-primary-400 rounded px-1 py-0.5 focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm bg-surface' + (extra ? ' ' + extra : '');

const updateStats = (data) => {
    document.getElementById('stat-velocity').textContent = data.outputAverage.toFixed(1);
    document.getElementById('stat-std-dev').textContent = data.standardDeviation.toFixed(3);
};

const sortTableByDate = (tbody) => {
    const trigger = document.getElementById('add-iteration-trigger');
    const rows = Array.from(tbody.querySelectorAll('tr:not(#add-iteration-trigger)'));
    rows.sort((a, b) =>
        a.querySelector('[data-field="end_date"]').dataset.value
            .localeCompare(b.querySelector('[data-field="end_date"]').dataset.value)
    );
    rows.forEach(row => tbody.insertBefore(row, trigger));
};

const makeEditableRow = (id, endDate, output, tbody) => {
    const tr = document.createElement('tr');
    tr.dataset.iterationId = id;

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.dataset.deleteIteration = id;
    deleteBtn.className = 'text-graphite-400 hover:text-red-500 transition';
    deleteBtn.innerHTML = icons.trash;

    const deleteTd = document.createElement('td');
    deleteTd.className = 'py-2.5 px-1';
    deleteTd.appendChild(deleteBtn);

    const endDateTd = document.createElement('td');
    endDateTd.className = 'py-2.5 text-graphite-600 cursor-pointer hover:bg-primary-50 rounded px-1 transition';
    endDateTd.dataset.editable = '';
    endDateTd.dataset.field = 'end_date';
    endDateTd.dataset.iterationId = id;
    endDateTd.dataset.value = endDate;
    endDateTd.textContent = endDate;

    const outputTd = document.createElement('td');
    outputTd.className = 'py-2.5 text-graphite-900 font-semibold text-right cursor-pointer hover:bg-primary-50 rounded px-1 transition';
    outputTd.dataset.editable = '';
    outputTd.dataset.field = 'output';
    outputTd.dataset.iterationId = id;
    outputTd.dataset.value = output;
    outputTd.textContent = output;

    tr.appendChild(deleteTd);
    tr.appendChild(endDateTd);
    tr.appendChild(outputTd);

    tbody.insertBefore(tr, document.getElementById('add-iteration-trigger'));
    return tr;
};

const beginEdit = (cell) => {
    if (cell.querySelector('input')) return;

    const { field, iterationId, value } = cell.dataset;
    const originalText = cell.textContent.trim();

    const input = document.createElement('input');
    input.type = field === 'output' ? 'number' : 'date';
    input.value = value;
    input.className = inputClass(field === 'output' ? 'text-right font-semibold' : '');

    cell.textContent = '';
    cell.appendChild(input);
    input.focus();

    let committed = false;

    const save = async () => {
        const newValue = input.value.trim();

        if (field === 'output') {
            const parsed = parseInt(newValue, 10);
            if (newValue === '' || isNaN(parsed) || parsed < 0) { cell.textContent = originalText; return; }
        } else {
            if (!newValue) { cell.textContent = originalText; return; }
        }

        const body = {};
        body[field] = field === 'output' ? parseInt(newValue, 10) : newValue;

        try {
            const response = await apiFetch(`/iteration/${iterationId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });

            if (response.ok) {
                const data = await response.json();
                cell.dataset.value = newValue;
                cell.textContent = newValue;
                updateStats(data);
                if (field === 'end_date') sortTableByDate(cell.closest('tbody'));
            } else {
                cell.textContent = originalText;
                showToast(await errorMessageFromResponse(response, 'Could not update iteration.'));
            }
        } catch {
            cell.textContent = originalText;
            showToast('Network error. Please try again.');
        }
    };

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); committed = true; save(); }
        if (e.key === 'Escape') { committed = true; cell.textContent = originalText; }
    });
    input.addEventListener('blur', () => { if (!committed) { committed = true; save(); } });
};

const beginAddIteration = (trigger) => {
    if (trigger.querySelector('input')) return;

    const tbody = trigger.closest('tbody');
    const teamId = trigger.closest('[data-team-id]').dataset.teamId;

    const dateInput = document.createElement('input');
    dateInput.type = 'date';
    dateInput.className = inputClass();

    const outputInput = document.createElement('input');
    outputInput.type = 'number';
    outputInput.min = '0';
    outputInput.className = inputClass('text-right font-semibold');

    const dateTd = trigger.cells[0];
    dateTd.colSpan = 1;
    dateTd.className = 'py-2 px-1';
    dateTd.textContent = '';
    dateTd.appendChild(dateInput);

    const outputTd = document.createElement('td');
    outputTd.className = 'py-2 px-1';
    outputTd.appendChild(outputInput);
    trigger.appendChild(outputTd);

    dateInput.focus();

    let committed = false;

    const resetTrigger = () => {
        committed = true;
        if (trigger.cells.length > 1) trigger.deleteCell(1);
        dateTd.colSpan = 3;
        dateTd.className = 'py-2.5 px-1 text-center text-primary-400 hover:text-primary-600 text-lg font-light select-none';
        dateTd.textContent = '+';
    };

    const save = async () => {
        const endDate = dateInput.value.trim();
        const outputVal = outputInput.value.trim();
        const parsed = parseInt(outputVal, 10);

        if (!endDate || outputVal === '' || isNaN(parsed) || parsed < 0) {
            resetTrigger();
            return;
        }

        try {
            const response = await apiFetch(`/team/${teamId}/iteration`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ end_date: endDate, output: parsed }),
            });

            if (response.ok) {
                const data = await response.json();
                resetTrigger();
                makeEditableRow(data.id, data.endDate, data.output, tbody);
                sortTableByDate(tbody);
                updateStats(data);
            } else {
                resetTrigger();
                showToast(await errorMessageFromResponse(response, 'Could not add iteration.'));
            }
        } catch {
            resetTrigger();
            showToast('Network error. Please try again.');
        }
    };

    outputInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); committed = true; save(); }
        if (e.key === 'Escape') resetTrigger();
    });
    outputInput.addEventListener('blur', () => { if (!committed) { committed = true; save(); } });
    dateInput.addEventListener('keydown', e => {
        if (e.key === 'Tab') return;
        if (e.key === 'Escape') resetTrigger();
    });
};

const deleteIteration = (iterationId, row) => {
    showDeleteConfirm(async () => {
        try {
            const response = await apiFetch(`/iteration/${iterationId}`, { method: 'DELETE' });

            if (response.ok) {
                row.remove();
                const data = await response.json();
                updateStats(data);
            } else {
                showToast(await errorMessageFromResponse(response, 'Could not delete iteration.'));
            }
        } catch {
            showToast('Network error. Please try again.');
        }
    });
};

document.addEventListener('click', (e) => {
    const editable = e.target.closest('[data-editable]');
    if (editable) { beginEdit(editable); return; }

    const deleteBtn = e.target.closest('[data-delete-iteration]');
    if (deleteBtn) {
        deleteIteration(deleteBtn.dataset.deleteIteration, deleteBtn.closest('tr'));
        return;
    }

    const trigger = e.target.closest('#add-iteration-trigger');
    if (trigger) beginAddIteration(trigger);
});
