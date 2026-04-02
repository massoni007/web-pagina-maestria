document.addEventListener('DOMContentLoaded', () => {
    const adminLoginBtn = document.getElementById('btn-admin-login');
    const adminLoginModal = document.getElementById('admin-login-modal');
    const adminPassInput = document.getElementById('admin-password');
    const adminSubmit = document.getElementById('admin-login-submit');
    const adminCancel = document.getElementById('admin-login-cancel');
    const adminDashboard = document.getElementById('admin-dashboard-section');
    const adminLogout = document.getElementById('btn-admin-logout');

    // Portal UI Elements
    const portalSection = document.getElementById('portal-section');
    const step1 = document.getElementById('step-1-email');
    const step2 = document.getElementById('step-2-code');
    const emailInput = document.getElementById('identifier');
    const codeInput = document.getElementById('verification-code');
    const btnSendCode = document.getElementById('btn-send-code');
    const btnVerifyCode = document.getElementById('btn-verify-code');
    const btnBackToEmail = document.getElementById('btn-back-to-email');
    const msgBox = document.getElementById('form-msg');

    const showMsg = (text, type = 'error') => {
        msgBox.textContent = text;
        msgBox.className = `msg-box msg-${type}`;
        msgBox.classList.remove('hidden');
    };

    // ---------------- PORTAL LOGIC --------------------------------------------
    if (btnSendCode) {
        btnSendCode.onclick = async () => {
            const email = emailInput.value.trim();
            if (!email || !email.includes('@')) return showMsg("Ingresa un correo válido.");

            btnSendCode.disabled = true;
            btnSendCode.textContent = "Enviando...";
            
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'send_code', email })
                });
                const result = await res.json();
                if (result.success) {
                    step1.classList.add('hidden');
                    step2.classList.remove('hidden');
                    msgBox.classList.add('hidden');
                } else {
                    showMsg(result.message);
                }
            } catch(e) {
                showMsg("Error de conexión con el servidor.");
            }
            btnSendCode.disabled = false;
            btnSendCode.textContent = "Enviar Código de Acceso";
        };
    }

    if (btnVerifyCode) {
        btnVerifyCode.onclick = async () => {
            const code = codeInput.value.trim();
            if (code.length !== 6) return showMsg("El código debe tener 6 dígitos.");

            btnVerifyCode.disabled = true;
            btnVerifyCode.textContent = "Verificando...";

            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'verify_code', code })
                });
                const result = await res.json();
                if (result.success) {
                    showMsg("¡Verificado! Redirigiendo...", "success");
                    setTimeout(() => {
                        window.location.href = 'editor.html';
                    }, 1000);
                } else {
                    showMsg(result.message);
                }
            } catch(e) {
                showMsg("Error de conexión.");
            }
            btnVerifyCode.disabled = false;
            btnVerifyCode.textContent = "Verificar e Ingresar";
        };
    }

    if (btnBackToEmail) {
        btnBackToEmail.onclick = () => {
            step2.classList.add('hidden');
            step1.classList.remove('hidden');
            msgBox.classList.add('hidden');
        };
    }

    // ---------------- ADMIN LOGIC ---------------------------------------------
    if(adminLoginBtn) {
        adminLoginBtn.onclick = () => {
            adminPassInput.value = '';
            adminLoginModal.classList.remove('hidden');
            adminPassInput.focus();
        };
        adminCancel.onclick = () => adminLoginModal.classList.add('hidden');
        adminSubmit.onclick = async () => {
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'admin_login', password: adminPassInput.value })
                });
                const result = await res.json();
                if (result.success) {
                    adminLoginModal.classList.add('hidden');
                    window.location.href = 'editor.html';
                } else {
                    alert(result.message);
                }
            } catch(e) {
                alert("Error de conexión.");
            }
        };
    }

    // ---------------- LOGOUT & SESSION CHECK ----------------------------------
    const btnLogout = document.getElementById('btn-logout');
    
    const checkSession = async () => {
        try {
            const res = await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'check_auth' }) });
            const data = await res.json();
            if (data.success && btnLogout) {
                btnLogout.classList.remove('hidden');
                btnLogout.onclick = async () => {
                    await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
                    window.location.reload();
                };
            }
        } catch(e) {}
    };
    checkSession();
});
