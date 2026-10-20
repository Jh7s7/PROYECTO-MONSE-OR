<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Ayuda</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: 
                linear-gradient(135deg, rgba(26, 54, 93, 0.92) 0%, rgba(26, 54, 93, 0.82) 100%),
                url('https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .ayuda-wrapper {
            max-width: 820px;
            width: 100%;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ayuda-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px 30px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .ayuda-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a365d, #e8c84c, #1a365d);
            background-size: 200% 100%;
            animation: gradientMove 4s ease-in-out infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .encabezado {
            text-align: center;
            padding: 20px 20px 15px 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #1a365d, #2d4a7a);
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 20px rgba(26, 54, 93, 0.3);
        }

        .encabezado .logo-institucion {
            display: block;
            max-width: 70px;
            height: auto;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: white;
            padding: 5px;
            border: 3px solid #e8c84c;
            box-shadow: 0 8px 30px rgba(232, 200, 76, 0.2);
            transition: transform 0.3s ease;
        }

        .encabezado .logo-institucion:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .encabezado h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin: 0;
        }

        .encabezado h1 span {
            color: #e8c84c;
        }

        .encabezado p {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .seccion-ayuda {
            background: #f7fafc;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .seccion-ayuda:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .seccion-ayuda .titulo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .seccion-ayuda .titulo .icono {
            font-family: 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #1a365d;
            background: #e2e8f0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seccion-ayuda .titulo h3 {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 17px;
            color: #1a365d;
            font-weight: 700;
            margin: 0;
        }

        .seccion-ayuda .titulo h3 span {
            color: #e8c84c;
        }

        .seccion-ayuda p {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #4a5568;
            line-height: 1.6;
            margin: 6px 0;
        }

        .seccion-ayuda p strong {
            color: #1a365d;
        }

        .seccion-ayuda ul {
            padding-left: 20px;
            margin: 6px 0;
        }

        .seccion-ayuda ul li {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #4a5568;
            padding: 4px 0;
            list-style: none;
            position: relative;
            padding-left: 26px;
        }

        .seccion-ayuda ul li::before {
            content: '✅';
            position: absolute;
            left: 0;
            font-size: 14px;
        }

        .seccion-ayuda .pasos {
            padding-left: 20px;
            margin: 6px 0;
        }

        .seccion-ayuda .pasos li {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #4a5568;
            padding: 4px 0;
            list-style: none;
            position: relative;
            padding-left: 26px;
        }

        .seccion-ayuda .pasos li::before {
            content: '➜';
            position: absolute;
            left: 0;
            font-size: 14px;
            color: #e8c84c;
            font-weight: 700;
        }

        .btn-volver {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #1a365d, #2d4a7a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            text-align: center;
        }

        .btn-volver:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 54, 93, 0.3);
        }

        .footer-ayuda {
            text-align: center;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px dashed #e2e8f0;
        }

        .footer-ayuda p {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #4a5568;
            margin-top: 8px;
        }

        .footer-ayuda p strong {
            color: #1a365d;
        }

        @media (max-width: 768px) {
            .ayuda-card {
                padding: 22px 18px;
            }
            .encabezado h1 {
                font-size: 20px;
            }
            .seccion-ayuda {
                padding: 14px 16px;
            }
            .seccion-ayuda .titulo .icono {
                width: 30px;
                height: 30px;
                font-size: 18px;
            }
            .seccion-ayuda .titulo h3 {
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .ayuda-card {
                padding: 16px 12px;
                border-radius: 16px;
            }
            .encabezado h1 {
                font-size: 18px;
            }
            .encabezado .logo-institucion {
                max-width: 55px;
            }
            .seccion-ayuda ul li,
            .seccion-ayuda .pasos li {
                font-size: 13px;
            }
            .btn-volver {
                width: 100%;
                text-align: center;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1a365d, #e8c84c);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2d4a7a, #d4a830);
        }
    </style>
</head>
<body>
    <div class="ayuda-wrapper">
        <div class="ayuda-card">

            <!-- ===== ENCABEZADO ===== -->
            <div class="encabezado">
                <img src="img/logo_colegio.png" alt="Logo" class="logo-institucion" onerror="this.style.display='none'">
                <h1>Centro de <span>Ayuda</span></h1>
                <p>Sistema de Boletines - U.E.E. Monseñor Rafael Arias Blanco</p>
            </div>

            <!-- ============================================================ -->
            <!-- SECCIÓN 1: DOCENTES                                           -->
            <!-- ============================================================ -->
            <div class="seccion-ayuda">
                <div class="titulo">
                    <span class="icono">[D]</span>
                    <h3>Para <span>Docentes</span></h3>
                </div>
                <p><strong>¿Cómo accedo?</strong> Usa tu email y contraseña en el login.</p>
                
                <p style="margin-top:10px;"><strong>¿Cómo creo un boletín?</strong></p>
                <ul class="pasos">
                    <li>Ve al Panel del Docente</li>
                    <li>Selecciona el estudiante y el lapso</li>
                    <li>Ingresa las observaciones o la calificación literal</li>
                    <li>Guarda como borrador o publica</li>
                </ul>

                <p style="margin-top:10px;"><strong>¿Cómo publico un boletín?</strong></p>
                <ul>
                    <li>Crea o edita el boletín</li>
                    <li>En la lista de boletines, haz clic en "Publicar"</li>
                    <li>Se genera un enlace compartible automáticamente</li>
                    <li>Puedes compartirlo por WhatsApp o correo</li>
                </ul>
            </div>

            <!-- ============================================================ -->
            <!-- SECCIÓN 2: REPRESENTANTES                                     -->
            <!-- ============================================================ -->
            <div class="seccion-ayuda">
                <div class="titulo">
                    <span class="icono">[R]</span>
                    <h3>Para <span>Representantes</span></h3>
                </div>
                <p><strong>¿Cómo veo el boletín de mi hijo?</strong></p>
                <ul class="pasos">
                    <li>Inicia sesión con tu email y contraseña</li>
                    <li>Verás automáticamente los boletines disponibles</li>
                    <li>Si tienes varios hijos, podrás ver todos sus boletines</li>
                </ul>

                <p style="margin-top:10px;"><strong>¿Cómo confirmo la lectura?</strong></p>
                <ul>
                    <li>Al final del boletín, haz clic en "Confirmar Lectura"</li>
                    <li>Puedes agregar una observación opcional</li>
                    <li>La fecha y hora quedan registradas</li>
                </ul>

                <p style="margin-top:10px;"><strong>¿Qué hago si no veo a mi hijo?</strong></p>
                <ul>
                    <li>Verifica que tu cuenta esté vinculada al estudiante</li>
                    <li>Contacta al administrador o docente</li>
                </ul>
            </div>

            <!-- ============================================================ -->
            <!-- SECCIÓN 3: ADMINISTRADORES                                   -->
            <!-- ============================================================ -->
            <div class="seccion-ayuda">
                <div class="titulo">
                    <span class="icono">[A]</span>
                    <h3>Para <span>Administradores</span></h3>
                </div>
                <p><strong>¿Cómo apruebo usuarios?</strong></p>
                <ul class="pasos">
                    <li>Ve al Panel de Administración</li>
                    <li>En la sección "Usuarios Pendientes" verás la lista</li>
                    <li>Haz clic en "Aprobar" o "Rechazar"</li>
                </ul>

                <p style="margin-top:10px;"><strong>¿Cómo gestiono usuarios?</strong></p>
                <ul>
                    <li>Usa el botón "Gestionar Usuarios"</li>
                    <li>Puedes activar, desactivar o eliminar usuarios</li>
                    <li>También puedes restablecer contraseñas</li>
                </ul>

                <p style="margin-top:10px;"><strong>¿Cómo veo la bitácora de accesos?</strong></p>
                <ul>
                    <li>Usa el botón "Bitácora" en el panel de administración</li>
                    <li>Puedes filtrar por usuario, fecha y acción</li>
                    <li>Exporta los datos a CSV</li>
                </ul>
            </div>

            <!-- ============================================================ -->
            <!-- SECCIÓN 4: PROBLEMAS FRECUENTES                              -->
            <!-- ============================================================ -->
            <div class="seccion-ayuda" style="border-left:4px solid #e8c84c;">
                <div class="titulo">
                    <span class="icono">[?]</span>
                    <h3>Problemas <span>Frecuentes</span></h3>
                </div>
                <p><strong>❌ No puedo iniciar sesión</strong></p>
                <ul>
                    <li>Verifica que tu email y contraseña sean correctos</li>
                    <li>Asegúrate de que tu cuenta esté aprobada (estado "Activo")</li>
                    <li>Si olvidaste tu contraseña, usa el enlace "Recuperar contraseña"</li>
                </ul>

                <p style="margin-top:10px;"><strong>❌ No veo a mi hijo como representante</strong></p>
                <ul>
                    <li>El administrador debe vincular tu cuenta al estudiante</li>
                    <li>Verifica que el estudiante esté registrado en el sistema</li>
                </ul>

                <p style="margin-top:10px;"><strong>❌ El sistema no carga bien en mi teléfono</strong></p>
                <ul>
                    <li>El sistema es responsivo, se adapta a cualquier dispositivo</li>
                    <li>Prueba usar Chrome, Firefox o Safari en tu móvil</li>
                </ul>
            </div>

            <!-- ============================================================ -->
            <!-- FOOTER Y BOTÓN VOLVER                                         -->
            <!-- ============================================================ -->
            <div class="footer-ayuda">
                <a href="index.php" class="btn-volver">← Volver al Inicio</a>
                <p>📧 Para soporte técnico: <strong>Comunicarse con el administrador del sistema</strong></p>
            </div>

        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>