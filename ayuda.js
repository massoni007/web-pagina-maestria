document.addEventListener('DOMContentLoaded', () => {
    // Definimos los textos de ayuda según la URL de la página
    const textosAyuda = {
        'home.html': '🏠 Home / Coordinación:\n\nEsta es la página central. Sirve como un tablón de anuncios y repositorio de documentos y reglamentos clave para todos los alumnos de la Maestría. El coordinador puede actualizarla para dar avisos generales.',
        'inscripcion_cursos.html': '📝 Preinscripción de Cursos:\n\nAquí los estudiantes de maestría ingresan sus datos y seleccionan a qué cursos se matricularán. La lista de cursos se carga en tiempo real desde la base de datos controlada por el profesor en la pestaña Admin.',
        'admin.html': '⚙️ Admin (Panel de Control):\n\nEsta herramienta es solo para profesores. Requiere ingresar con el código que se enviará automáticamente a massoni007@gmail.com.\n\nAquí se puede:\n1) Descargar el CSV con alumnos pre-inscritos.\n2) Editar nombres y créditos del catálogo global de Cursos.\n3) Revisar quiénes enviaron Tareas de Física Atómica, ver la hora de entrega y descargarlas.',
        'default': '🌐 Portal de Gestión:\n\nUtiliza el menú oscuro de la izquierda para navegar entre las distintas áreas (Home, Preinscripción, Tareas, Panel de Administración).'
    };

    // Crear el botón flotante
    const containerAyuda = document.createElement('div');
    containerAyuda.innerHTML = `
        <button id="btn-ayuda-global" style="
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: #3b82f6;
            color: #ffffff;
            border: 2px solid #ffffff;
            border-radius: 50px;
            padding: 10px 20px;
            font-family: 'Inter', system-ui, sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
            z-index: 999999;
            transition: transform 0.2s, background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        ">
            <span style="font-size: 16px;">💡</span> Ayuda
        </button>
    `;
    
    document.body.appendChild(containerAyuda);

    const btn = document.getElementById('btn-ayuda-global');

    // Efectos hover
    btn.addEventListener('mouseover', () => { 
        btn.style.transform = 'scale(1.05) translateY(-2px)'; 
        btn.style.backgroundColor = '#2563eb'; 
    });
    btn.addEventListener('mouseout', () => { 
        btn.style.transform = 'scale(1) translateY(0)'; 
        btn.style.backgroundColor = '#3b82f6'; 
    });
    
    // Acción al hacer click
    btn.addEventListener('click', () => {
        let path = window.location.pathname;
        let file = path.split('/').pop() || 'index.html';
        
        let ayudaTxt = textosAyuda[file] || textosAyuda['default'];
        
        // Excepciones para paginas index.html en carpetas anidadas
        if (path.includes('maestria/web/index.html')) {
            ayudaTxt = '📝 Info Preinscripción:\n\nLanding page (página de bienvenida) que explica a los alumnos qué están a punto de hacer antes de redirigirlos al formulario interactivo de matrícula de cursos.';
        } else if (path.includes('fisicaatomica/index.html')) {
            ayudaTxt = '⚛️ Tareas Física Atómica:\n\nEste es el portal de acceso especial para los alumnos del curso de Física Atómica (de Carlos y Eduardo). Aquí los alumnos usarán su contraseña para someter los documentos de sus tareas, los cuales viajarán al servidor y podrás verlos en Admin.';
        } else if (file === 'index.html' || file === '') {
            ayudaTxt = textosAyuda['default'] + '\n\n💡 Sugerencia: Al navegar a otras páginas por el menú, podrás seguir consultando información detallada dando click en este botón de ayuda.';
        }
        
        alert("--- DOCUMENTACIÓN RÁPIDA ---\n\n" + ayudaTxt);
    });
});
