document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('preinscripcion-form');
    const formContainer = document.getElementById('form-container');
    const resumenContainer = document.getElementById('resumen-container');
    const mensajeDiv = document.getElementById('mensaje-final');
    
    const btnVolver = document.getElementById('btn-volver-editar');
    const btnFinalizar = document.getElementById('btn-finalizar-salir');
    
    // Almacenamiento temporal en memoria
    let pendingFormData = null;
    let pendingCursosSeleccionados = [];

    // --- CARGAR DATOS PREVIOS SI EXISTEN ---
    const urlParams = new URLSearchParams(window.location.search);
    const urlEmail = urlParams.get('email');
    
    if (urlEmail) {
        document.getElementById('alumno-email').value = urlEmail;
        
        // Tratar de obtener inscripción previa
        fetch('obtener_inscripcion.php?email=' + encodeURIComponent(urlEmail))
            .then(res => res.json())
            .then(result => {
                if (result.success && result.data) {
                    // Prellena nombres
                    const partesNombre = result.data.nombre.trim().split(' ');
                    if (partesNombre.length > 1) {
                        document.getElementById('primer-apellido').value = partesNombre.pop();
                        document.getElementById('primer-nombre').value = partesNombre.join(' ');
                    } else {
                        document.getElementById('primer-nombre').value = result.data.nombre;
                    }
                    
                    document.getElementById('alumno-codigo').value = result.data.codigo;
                    
                    // Espera a que los cursos dinámicos terminen de inyectarse para poder marcarlos
                    setTimeout(() => {
                        const checkboxes = document.querySelectorAll('input[name="cursos[]"]');
                        checkboxes.forEach(cb => {
                            if (result.data.cursos.includes(cb.value)) {
                                cb.checked = true;
                            }
                        });
                    }, 50);
                    
                    document.querySelector('#btn-preinscribir .btn-text').textContent = 'Actualizar Preinscripción';
                }
            })
            .catch(err => console.error("Error cargando inscripción anterior:", err));
    }

    // Paso 1: Procesar formulario y mostrar "Vista Previa / Resumen"
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const email = document.getElementById('alumno-email').value.trim();
        const primerNombre = document.getElementById('primer-nombre').value.trim();
        const primerApellido = document.getElementById('primer-apellido').value.trim();
        const nombre = primerNombre + ' ' + primerApellido;
        const codigo = document.getElementById('alumno-codigo').value.trim();
        const checkboxes = document.querySelectorAll('input[name="cursos[]"]:checked');
        
        if (checkboxes.length === 0) {
            const confirmarVacio = confirm("Atención: No estás seleccionando ningún curso.\n\Si continúas, al salir de aquí se borrarán tus cursos previos y no quedará ningún curso grabado a tu nombre.\n\n¿Estás seguro de que deseas continuar sin ningún curso?");
            if (!confirmarVacio) {
                return;
            }
        }

        pendingCursosSeleccionados = Array.from(checkboxes).map(cb => cb.value);

        pendingFormData = new FormData();
        pendingFormData.append('email', email);
        pendingFormData.append('nombre', nombre);
        pendingFormData.append('codigo', codigo);
        pendingFormData.append('cursos', JSON.stringify(pendingCursosSeleccionados));

        // Ocultar formulario, mostrar resumen
        formContainer.style.display = 'none';
        
        resumenContainer.style.display = 'block';
        resumenContainer.style.opacity = '1';
        resumenContainer.style.transform = 'translateY(0)';
        
        document.getElementById('resumen-nombre').textContent = nombre;
        document.getElementById('resumen-codigo').textContent = codigo;
        document.getElementById('resumen-email').textContent = email;
        
        const listaCursos = document.getElementById('resumen-cursos');
        listaCursos.innerHTML = '';
        if (pendingCursosSeleccionados.length === 0) {
            listaCursos.innerHTML = '<li style="color: #ef4444; list-style: none;">⚠️ Ningún curso seleccionado. (Tu preinscripción será cancelada)</li>';
        } else {
            pendingCursosSeleccionados.forEach(curso => {
                const li = document.createElement('li');
                li.textContent = curso;
                listaCursos.appendChild(li);
            });
        }
    });

    // Paso 2A: El alumno se arrepiente y quiere regresar a editar
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            resumenContainer.style.display = 'none';
            formContainer.style.display = 'block';
        });
    }

    // Paso 2B: Confirmar y grabar finalmente en el Excel
    if (btnFinalizar) {
        btnFinalizar.addEventListener('click', async () => {
            const confirmar = confirm("¿Está seguro de grabar los datos permanentemente en el sistema?");
            if (!confirmar) {
                return; // Si dice 'Cancelar', no hace nada
            }

            // Cambiar estado visual del botón
            btnFinalizar.disabled = true;
            btnFinalizar.textContent = 'Grabando...';

            try {
                // Hacer el guardado real
                const response = await fetch('guardar_inscripcion.php', {
                    method: 'POST',
                    body: pendingFormData
                });

                let result;
                try {
                    const responseText = await response.text();
                    result = JSON.parse(responseText);
                } catch (jsonErr) {
                    throw new Error("El servidor devolvió una respuesta no válida al intentar grabar.");
                }

                if (response.ok && result && result.success) {
                    alert("¡Sus datos han sido grabados con éxito!");
                    window.location.href = 'index.html'; // Lo mandamos de regreso
                } else {
                    alert(result.message || 'Error al guardar.');
                    btnFinalizar.disabled = false;
                    btnFinalizar.textContent = 'Grabar y Finalizar';
                }
            } catch (error) {
                console.error(error);
                alert('Ocurrió un error de conexión al guardar los datos.');
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = 'Grabar y Finalizar';
            }
        });
    }

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.textContent = texto;
        mensajeDiv.style.display = 'block';
        if (tipo === 'success') {
            mensajeDiv.style.background = 'rgba(16, 185, 129, 0.2)';
            mensajeDiv.style.color = '#10b981';
            mensajeDiv.style.border = '1px solid #10b981';
        } else {
            mensajeDiv.style.background = 'rgba(239, 68, 68, 0.2)';
            mensajeDiv.style.color = '#ef4444';
            mensajeDiv.style.border = '1px solid #ef4444';
        }
    }
});

let cursosDisponibles = {};

document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById('contenedor-cursos');
    if (contenedor) {
        contenedor.innerHTML = '<p style="color: white; padding: 15px;">Cargando cursos disponibles...</p>';
        try {
            const res = await fetch('data/cursos.json?_=' + new Date().getTime()); // Evitar caché local
            const txt = await res.text();
            cursosDisponibles = JSON.parse(txt);
            
            let htmlContent = '<div style="padding: 15px;">';
            
            for (const [categoria, cursos] of Object.entries(cursosDisponibles)) {
                const color = categoria === "Cursos Obligatorios" ? "#10b981" : "#3b82f6";
                
                htmlContent += `<h4 style="color: ${color}; margin-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; font-size: 1.1rem; margin-top: ${categoria === 'Cursos Obligatorios' ? '0' : '20px'};">${categoria}</h4>`;
                htmlContent += '<ul style="list-style: none; padding: 0; margin-bottom: 0;">';
                
                cursos.forEach(curso => {
                    htmlContent += `
                        <li style="padding: 8px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;">
                            <label for="${curso.id}" style="margin: 0; cursor: pointer; flex-grow: 1; font-weight: 400; padding-right: 15px; font-size: 0.95rem;">
                                <strong style="color: #cbd5e1;">${curso.id}</strong> - ${curso.nombre} 
                                <span style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin-left: 5px;">(${curso.creditos} cr)</span>
                            </label>
                            <input type="checkbox" id="${curso.id}" name="cursos[]" value="${curso.id} - ${curso.nombre}" style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;">
                        </li>
                    `;
                });
                htmlContent += '</ul>';
            }
            
            htmlContent += '</div>';
            contenedor.innerHTML = htmlContent;
        } catch (error) {
            console.error('Error cargando cursos', error);
            contenedor.innerHTML = '<p style="color: #ef4444; padding: 15px;">Error cargando la lista de cursos desde el servidor.</p>';
        }
    }
});
