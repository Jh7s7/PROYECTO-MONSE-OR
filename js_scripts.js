// ============================================================
// SISTEMA DE BOLETINES - SCRIPTS GLOBALES
// ============================================================

function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var btn = input.parentElement.querySelector('.toggle-password');
    if (input.type === 'password') { input.type = 'text'; btn.textContent = '[X]'; }
    else { input.type = 'password'; btn.textContent = '[O]'; }
}

function seleccionarLapso(lapso) {
    document.querySelectorAll('.lapso-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if (parseInt(btn.dataset.lapso) === lapso) btn.classList.add('active');
    });
    document.getElementById('edit_period').value = lapso;
    var desc = document.getElementById('formato_descriptivo');
    var lit = document.getElementById('formato_literal');
    if (lapso <= 2) { desc.style.display = 'block'; lit.style.display = 'none'; }
    else { desc.style.display = 'none'; lit.style.display = 'block'; }
}

function publicarBoletin(id) {
    if (confirm('¿Publicar este boletín?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="publish"><input type="hidden" name="bulletin_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function editarBoletin(id) {
    fetch('get_bulletin.php?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('edit_bulletin_id').value = data.id;
            document.getElementById('edit_student_id').value = data.student_id;
            document.getElementById('edit_apreciacion').value = data.apreciacion || '';
            document.getElementById('edit_nota_literal').value = data.nota_literal || '';
            document.getElementById('edit_fecha_entrega').value = data.fecha_entrega || '';
            document.getElementById('edit_status').value = data.status || 'draft';
            seleccionarLapso(parseInt(data.period) || 1);
            document.querySelector('.admin-card h2').textContent = 'Editar Boletin';
            document.querySelector('button[type="submit"]').textContent = 'Actualizar';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
}

function compartirWhatsApp(token, estudiante) {
    var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
    var mensaje = 'Boletin de Calificaciones\nU.E.E. Monseñor Rafael Arias Blanco\nEstudiante: ' + estudiante + '\n' + url;
    window.open('https://wa.me/?text=' + encodeURIComponent(mensaje), '_blank');
}

function compartirCorreo(token, estudiante) {
    var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
    var asunto = encodeURIComponent('Boletin - ' + estudiante);
    var cuerpo = encodeURIComponent('Estimado Representante,\n\nSe encuentra disponible el boletin de ' + estudiante + ':\n\n' + url + '\n\nSaludos cordiales,\nU.E.E. Monseñor Rafael Arias Blanco');
    window.open('mailto:?subject=' + asunto + '&body=' + cuerpo, '_blank');
}

function copiarEnlace(token) {
    var url = window.location.origin + '/gestion_boletines/ver_publico.php?token=' + token;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() { alert('Enlace copiado'); });
    } else {
        var input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Enlace copiado');
    }
}

function mostrarToast(mensaje, tipo) {
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);padding:12px 24px;background:' + (tipo === 'success' ? '#48bb78' : '#fc8181') + ';color:white;border-radius:10px;font-weight:600;font-size:14px;box-shadow:0 8px 30px rgba(0,0,0,0.2);z-index:9999;max-width:90%;text-align:center;font-family:Segoe UI,sans-serif;';
    toast.textContent = mensaje;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema de Boletines - Scripts cargados');
});