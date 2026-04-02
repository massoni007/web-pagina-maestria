document.addEventListener('DOMContentLoaded', () => {
    const emailForm = document.getElementById('email-form');
    const codeForm = document.getElementById('code-form');
    
    const emailInput = document.getElementById('email');
    const codeInput = document.getElementById('code');
    
    const emailBtn = document.getElementById('submit-btn-email');
    const codeBtn = document.getElementById('submit-btn-code');

    const messageContainer = document.getElementById('message-container');

    // Step 1: Send the email
    emailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = emailInput.value.trim();
        if (!email) return;

        hideMessage();
        setLoading(emailBtn, true);

        try {
            const formData = new FormData();
            formData.append('email', email);

            const response = await fetch('enviar_codigo.php', {
                method: 'POST',
                body: formData
            });

            let result;
            try {
                result = await response.json();
            } catch (jsonErr) {
                console.error("Non-JSON response received:", await response.text());
                throw new Error("El servidor devolvió una respuesta no válida.");
            }

            if (response.ok && result.success) {
                showMessage('¡Código enviado con éxito! Revisa tu bandeja de entrada o SPAM.', 'success');
                // Hide email form, show code form
                emailForm.style.display = 'none';
                codeForm.style.display = 'block';
                codeInput.focus();
            } else {
                showMessage(result.message || 'Ocurrió un error al enviar el código. Intenta nuevamente.', 'error');
            }
        } catch (error) {
            console.error('Submission Error:', error);
            showMessage(error.message || 'Error de conexión. Por favor verifica tu internet e intenta nuevamente.', 'error');
        } finally {
            setLoading(emailBtn, false);
        }
    });

    // Step 2: Verify the code
    codeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const code = codeInput.value.trim();
        // if (!code) return; // Comentado para permitir validación en blanco de pruebas

        hideMessage();
        setLoading(codeBtn, true);

        try {
            const formData = new FormData();
            formData.append('code', code);

            const response = await fetch('verificar_codigo.php', {
                method: 'POST',
                body: formData
            });

            let result;
            try {
                // Read the text first, then parse it to JSON
                const responseText = await response.text();
                result = JSON.parse(responseText);
            } catch (jsonErr) {
                console.error("Non-JSON response received:", jsonErr);
                throw new Error("El servidor devolvió una respuesta no válida.");
            }

            if (response.ok && result.success) {
                showMessage(result.message || 'Cuenta verificada con éxito. Redirigiendo...', 'success');
                codeForm.style.display = 'none'; // Hide the form on success
                
                // Redirigir a la página de inscripción pasándole el correo verificado
                setTimeout(() => {
                    const emailValue = document.getElementById('email').value.trim();
                    window.location.href = `inscripcion_cursos.html?email=${encodeURIComponent(emailValue)}`;
                }, 1500);

            } else {
                showMessage(result.message || 'Código incorrecto.', 'error');
            }
        } catch (error) {
            console.error('Verification Error:', error);
            showMessage(error.message || 'Error de conexión.', 'error');
        } finally {
            setLoading(codeBtn, false);
        }
    });

    // Helper to toggle visual feedback loading state
    function setLoading(btn, isLoading) {
        const btnText = btn.querySelector('.btn-text');
        const loader = btn.querySelector('.loader');
        
        if (isLoading) {
            btn.disabled = true;
            btnText.style.display = 'none';
            loader.style.display = 'block';
        } else {
            btn.disabled = false;
            btnText.style.display = 'block';
            loader.style.display = 'none';
        }
    }

    // Displays the success/error message nicely animated
    function showMessage(text, type) {
        messageContainer.className = `message ${type} show`;
        messageContainer.textContent = text;
    }

    // Hides the message box
    function hideMessage() {
        messageContainer.className = 'message hidden';
        messageContainer.textContent = '';
    }

});
