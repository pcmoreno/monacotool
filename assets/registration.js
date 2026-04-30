const registerModal = document.getElementById('register-modal');
const registerBackdrop = document.getElementById('register-backdrop');
const registerNameInput = document.getElementById('register-name');
const registerEmailInput = document.getElementById('register-email');
const registerPasswordInput = document.getElementById('register-password');
const registerConfirmInput = document.getElementById('register-confirm');
const registerSubmitBtn = document.getElementById('register-submit');
const registerErrors = document.getElementById('register-errors');

const openRegister = () => { registerModal.classList.remove('hidden'); registerNameInput.focus(); };
const closeRegister = () => {
    registerModal.classList.add('hidden');
    registerErrors.classList.add('hidden');
    registerErrors.innerHTML = '';
    [registerNameInput, registerEmailInput, registerPasswordInput, registerConfirmInput].forEach(i => i.value = '');
};

document.getElementById('open-register-modal').addEventListener('click', openRegister);
document.getElementById('close-register-modal').addEventListener('click', closeRegister);
registerBackdrop.addEventListener('click', closeRegister);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeRegister(); });

const showErrors = (errors) => {
    registerErrors.innerHTML = errors.map(e => `<p>${e}</p>`).join('');
    registerErrors.classList.remove('hidden');
};

registerSubmitBtn.addEventListener('click', async () => {
    registerErrors.classList.add('hidden');
    registerSubmitBtn.disabled = true;

    try {
        const response = await fetch('/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: registerEmailInput.value.trim(),
                name: registerNameInput.value.trim(),
                password: registerPasswordInput.value,
                confirmPassword: registerConfirmInput.value,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            window.location.href = data.redirect;
        } else {
            showErrors(data.errors ?? ['Something went wrong. Please try again.']);
        }
    } catch {
        showErrors(['Something went wrong. Please try again.']);
    } finally {
        registerSubmitBtn.disabled = false;
    }
});
