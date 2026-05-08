/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { apiFetch } from 'csrf';
import { showToast, errorMessageFromResponse } from 'toast';

const inputClass = (extra = '') =>
    'w-full border border-primary-400 rounded px-1 py-0.5 focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm bg-surface' + (extra ? ' ' + extra : '');

export default class extends Controller {
    _onClick = (e) => {
        const editable = e.target.closest('[data-editable]');
        if (editable) { this._beginEdit(editable); return; }

        const trigger = e.target.closest('#add-iteration-trigger');
        if (trigger) this._beginAdd(trigger);
    };

    connect() {
        this.element.addEventListener('click', this._onClick);
    }

    disconnect() {
        this.element.removeEventListener('click', this._onClick);
    }

    _sortByDate() {
        const tbody = this.element.querySelector('#iterations-tbody');
        if (!tbody) return;
        const trigger = tbody.querySelector('#add-iteration-trigger');
        const rows = Array.from(tbody.querySelectorAll('tr[data-iteration-id]'));
        rows.sort((a, b) =>
            a.querySelector('[data-field="end_date"]').dataset.value
                .localeCompare(b.querySelector('[data-field="end_date"]').dataset.value)
        );
        rows.forEach(row => tbody.insertBefore(row, trigger));
    }

    async _beginEdit(cell) {
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
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/vnd.turbo-stream.html',
                    },
                    body: JSON.stringify(body),
                });

                if (response.ok) {
                    const html = await response.text();
                    await Turbo.renderStreamMessage(html);
                    if (field === 'end_date') this._sortByDate();
                } else {
                    cell.textContent = originalText;
                    showToast(await errorMessageFromResponse(response, 'Could not update iteration.'));
                }
            } catch (e) {
                if (!(e instanceof TypeError)) throw e;
                cell.textContent = originalText;
                showToast('Network error. Please try again.');
            }
        };

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); committed = true; save(); }
            if (e.key === 'Escape') { committed = true; cell.textContent = originalText; }
        });
        input.addEventListener('blur', () => { if (!committed) { committed = true; save(); } });
    }

    async _beginAdd(trigger) {
        if (trigger.querySelector('input')) return;

        const teamId = this.element.dataset.teamId;

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
            dateTd.className = 'py-2.5 px-1 text-center text-primary-400 hover:text-primary-600 text-sm font-medium select-none';
            dateTd.textContent = '+ Add iteration';
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
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/vnd.turbo-stream.html',
                    },
                    body: JSON.stringify({ end_date: endDate, output: parsed }),
                });

                if (response.ok) {
                    const html = await response.text();
                    resetTrigger();
                    await Turbo.renderStreamMessage(html);
                    this._sortByDate();
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
        dateInput.addEventListener('keydown', e => {
            if (e.key === 'Tab') return;
            if (e.key === 'Escape') resetTrigger();
        });
        trigger.addEventListener('focusout', (e) => {
            if (trigger.contains(e.relatedTarget)) return;
            if (!committed) { committed = true; save(); }
        });
    }
}
