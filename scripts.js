// Cambiar entre pestañas en el login
function cambiarTab(tipo) {
    if(tipo === 'rep') {
        document.getElementById('form-representante').style.display = 'block';
        document.getElementById('form-docente').style.display = 'none';
        document.getElementById('tab-rep').classList.add('active');
        document.getElementById('tab-doc').classList.remove('active');
        document.getElementById('enlaces-generales').style.display = 'block';
    } else {
        document.getElementById('form-representante').style.display = 'none';
        document.getElementById('form-docente').style.display = 'block';
        document.getElementById('tab-rep').classList.remove('active');
        document.getElementById('tab-doc').classList.add('active');
        document.getElementById('enlaces-generales').style.display = 'none';
    }
}

// Compartir boletín
function compartir(token) {
    const url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
    const mensaje = '📚 Boletín de calificaciones - U.E.E. Monseñor Rafael Arias Blanco\n\n' + url;
    
    if (navigator.share) {
        navigator.share({
            title: 'Boletín de calificaciones',
            text: mensaje,
            url: url
        }).catch(() => {
            window.open('https://wa.me/?text=' + encodeURIComponent(mensaje), '_blank');
        });
    } else {
        window.open('https://wa.me/?text=' + encodeURIComponent(mensaje), '_blank');
    }
}

// Copiar enlace al portapapeles
function copiarEnlace(token) {
    const url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            alert('✅ Enlace copiado al portapapeles:\n' + url);
        }).catch(() => {
            copiarManual(url);
        });
    } else {
        copiarManual(url);
    }
}

function copiarManual(texto) {
    const input = document.createElement('input');
    input.value = texto;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    alert('✅ Enlace copiado al portapapeles:\n' + texto);
}

// Confirmar eliminación
function confirmarEliminar(mensaje) {
    return confirm(mensaje || '⚠️ ¿Estás seguro de que quieres realizar esta acción?');
}

// Validar que las notas estén entre 0 y 20
function validarNota(input) {
    const valor = parseFloat(input.value);
    if (isNaN(valor) || valor < 0 || valor > 20) {
        input.setCustomValidity('La nota debe estar entre 0 y 20');
    } else {
        input.setCustomValidity('');
    }
}

console.log('✅ Sistema de Boletines - Scripts cargados correctamente');