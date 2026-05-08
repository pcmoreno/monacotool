import '@hotwired/turbo';
import './stimulus_bootstrap.js';
import './styles/app.css';
import 'theme';

Turbo.config.forms.confirm = (message) =>
    new Promise((resolve) =>
        document.dispatchEvent(new CustomEvent('turbo:confirm', { detail: { message, resolve } }))
    );

document.addEventListener('turbo:submit-start', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        event.detail.formSubmission.fetchRequest.headers['X-CSRF-Token'] = token;
    }
});
