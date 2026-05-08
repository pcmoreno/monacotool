/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { apiFetch } from 'csrf';
import { visit } from '@hotwired/turbo';

export default class extends Controller {
    _pendingTeamId = null;
    _pendingTeamName = null;

    _onClick = (e) => {
        const trigger = e.target.closest('#open-delete-team-modal');
        if (trigger) { this._openDeleteModal(trigger.dataset.teamId, trigger.dataset.teamName); return; }

        if (e.target.closest('#delete-team-cancel') || e.target.closest('#delete-team-backdrop')) {
            this._closeDeleteModal();
            return;
        }
        if (e.target.closest('#delete-team-confirm') && !document.getElementById('delete-team-confirm')?.disabled) {
            this._deleteTeam();
        }
    };

    _onKeydown = (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('delete-team-modal');
            if (modal && !modal.classList.contains('hidden')) this._closeDeleteModal();
        }
    };

    _onInput = (e) => {
        if (e.target.id !== 'delete-team-confirm-input') return;
        const btn = document.getElementById('delete-team-confirm');
        if (btn) btn.disabled = e.target.value !== this._pendingTeamName;
    };

    connect() {
        document.addEventListener('click', this._onClick);
        document.addEventListener('keydown', this._onKeydown);
        document.addEventListener('input', this._onInput);
    }

    disconnect() {
        document.removeEventListener('click', this._onClick);
        document.removeEventListener('keydown', this._onKeydown);
        document.removeEventListener('input', this._onInput);
    }

    _openDeleteModal(teamId, teamName) {
        this._pendingTeamId = teamId;
        this._pendingTeamName = teamName;

        const hint = document.getElementById('delete-team-name-hint');
        const input = document.getElementById('delete-team-confirm-input');
        const btn = document.getElementById('delete-team-confirm');
        const errorEl = document.getElementById('delete-team-error');

        if (hint) hint.textContent = teamName;
        if (input) input.value = '';
        if (btn) btn.disabled = true;
        if (errorEl) errorEl.classList.add('hidden');

        const el = document.getElementById('delete-team-modal');
        this.application.getControllerForElementAndIdentifier(el, 'modal')?.open();
    }

    _closeDeleteModal() {
        const el = document.getElementById('delete-team-modal');
        this.application.getControllerForElementAndIdentifier(el, 'modal')?.close();
        this._pendingTeamId = null;
        this._pendingTeamName = null;
    }

    async _deleteTeam() {
        const btn = document.getElementById('delete-team-confirm');
        const errorEl = document.getElementById('delete-team-error');
        if (btn) btn.disabled = true;

        try {
            const response = await apiFetch(`/team/${this._pendingTeamId}`, { method: 'DELETE' });

            if (response.ok) {
                visit('/team');
            } else {
                if (errorEl) { errorEl.textContent = 'Something went wrong. Please try again.'; errorEl.classList.remove('hidden'); }
                if (btn) btn.disabled = false;
            }
        } catch (e) {
            if (!(e instanceof TypeError)) throw e;
            if (errorEl) { errorEl.textContent = 'Something went wrong. Please try again.'; errorEl.classList.remove('hidden'); }
            if (btn) btn.disabled = false;
        }
    }
}
