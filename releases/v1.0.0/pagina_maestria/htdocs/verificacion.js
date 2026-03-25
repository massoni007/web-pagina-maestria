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

    // --- ESTADÍSTICAS EN VIVO ---
    async function loadStatistics() {
        try {
            // Añadimos timestamp para evitar caché agresivo
            const response = await fetch('stats_inscripciones.php?t=' + new Date().getTime());
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('stat-total-alumnos').textContent = data.total_alumnos;
                
                const ulCursos = document.getElementById('stat-lista-cursos');
                ulCursos.innerHTML = '';
                
                const cursos = data.cursos;
                // Ordenar por popularidad (cantidad descendente)
                const cursosOrdenados = Object.entries(cursos).sort((a, b) => b[1] - a[1]);
                
                if (cursosOrdenados.length === 0) {
                    ulCursos.innerHTML = '<li style="opacity: 0.5;">Aún no hay inscripciones registradas.</li>';
                } else {
                    cursosOrdenados.forEach(([curso, cantidad]) => {
                        const li = document.createElement('li');
                        li.style.cssText = 'padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;';
                        li.innerHTML = `<span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 10px;" title="${curso}">${curso}</span> 
                                        <span style="color: #10b981; font-weight: bold; flex-shrink: 0; background: rgba(16,185,129,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                            ${cantidad} 
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-left: 2px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                        </span>`;
                        ulCursos.appendChild(li);
                    });
                }
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            document.getElementById('stat-lista-cursos').innerHTML = '<li style="color: #ef4444;">No se pudieron cargar las métricas en este momento.</li>';
        }
    }
    
    // Cargar métricas al iniciar
    loadStatistics();
});
