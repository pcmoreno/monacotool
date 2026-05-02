import { apiFetch } from 'csrf';

// --- Forgot password ---

const openForgotModal = () => {
    document.getElementById('forgot-password-modal').classList.remove('hidden');
    document.getElementById('forgot-email').focus();
};

const closeForgotModal = () => {
    const modal = document.getElementById('forgot-password-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    const feedback = document.getElementById('forgot-feedback');
    if (feedback) { feedback.classList.add('hidden'); feedback.textContent = ''; }
    const email = document.getElementById('forgot-email');
    if (email) email.value = '';
};

const submitForgotPassword = async () => {
    const feedback = document.getElementById('forgot-feedback');
    const btn = document.getElementById('forgot-submit');

    feedback.classList.add('hidden');
    btn.disabled = true;

    try {
        await apiFetch('/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: document.getElementById('forgot-email').value.trim() }),
        });

        feedback.textContent = 'If that email is registered, a reset link has been sent.';
        feedback.classList.remove('hidden');
        document.getElementById('forgot-email').value = '';
    } catch {
        feedback.textContent = 'Something went wrong. Please try again.';
        feedback.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
};

// --- Resend verification (login page) ---

const openResendModal = () => {
    document.getElementById('resend-verification-modal')?.classList.remove('hidden');
    document.getElementById('resend-email')?.focus();
};

const closeResendModal = () => {
    document.getElementById('resend-verification-modal')?.classList.add('hidden');
    const feedback = document.getElementById('resend-feedback');
    if (feedback) { feedback.classList.add('hidden'); feedback.textContent = ''; }
};

const submitResend = async () => {
    const feedback = document.getElementById('resend-feedback');
    const btn = document.getElementById('resend-submit');

    feedback.classList.add('hidden');
    btn.disabled = true;

    try {
        await apiFetch('/resend-verification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: document.getElementById('resend-email').value.trim() }),
        });

        feedback.textContent = 'If that email exists and is unverified, a new link has been sent.';
        feedback.classList.remove('hidden');
    } catch {
        feedback.textContent = 'Something went wrong. Please try again.';
        feedback.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
};

// --- Reset password ---

const openResetModal = (token) => {
    const modal = document.getElementById('reset-password-modal');
    if (!modal) return;
    document.getElementById('reset-token').value = token;
    modal.classList.remove('hidden');
    document.getElementById('reset-password-input').focus();
};

const showResetErrors = (errors) => {
    const container = document.getElementById('reset-errors');
    container.replaceChildren(...errors.map(msg => {
        const p = document.createElement('p');
        p.textContent = msg;
        return p;
    }));
    container.classList.remove('hidden');
};

const submitResetPassword = async () => {
    const btn = document.getElementById('reset-submit');
    const errorsEl = document.getElementById('reset-errors');
    const success = document.getElementById('reset-success');

    errorsEl.classList.add('hidden');
    btn.disabled = true;

    try {
        const response = await apiFetch('/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token: document.getElementById('reset-token').value,
                password: document.getElementById('reset-password-input').value,
                confirmPassword: document.getElementById('reset-confirm-input').value,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            success.textContent = data.message;
            success.classList.remove('hidden');
            btn.classList.add('hidden');
            window.history.replaceState({}, '', '/login');
        } else {
            const errors = data.errors
                ?? data.violations?.map(v => v.title)
                ?? ['Something went wrong. Please try again.'];
            showResetErrors(errors);
        }
    } catch {
        showResetErrors(['Something went wrong. Please try again.']);
    } finally {
        btn.disabled = false;
    }
};

// --- Event listeners ---

document.addEventListener('click', (e) => {
    if (e.target.closest('#open-forgot-modal')) { openForgotModal(); return; }
    if (e.target.closest('#close-forgot-modal')) { closeForgotModal(); return; }
    if (e.target.closest('#forgot-backdrop')) { closeForgotModal(); return; }
    if (e.target.closest('#forgot-submit')) { submitForgotPassword(); return; }

    if (e.target.closest('#open-resend-modal')) { openResendModal(); return; }
    if (e.target.closest('#close-resend-modal')) { closeResendModal(); return; }
    if (e.target.closest('#resend-backdrop')) { closeResendModal(); return; }
    if (e.target.closest('#resend-submit')) { submitResend(); return; }

    if (e.target.closest('#reset-submit')) { submitResetPassword(); return; }
});

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (!document.getElementById('forgot-password-modal')?.classList.contains('hidden')) { closeForgotModal(); return; }
    if (!document.getElementById('resend-verification-modal')?.classList.contains('hidden')) { closeResendModal(); return; }
});

// Open reset modal if token is present in URL
const resetToken = new URLSearchParams(window.location.search).get('reset-token');
if (resetToken) {
    openResetModal(resetToken);
}
