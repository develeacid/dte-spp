<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: sans-serif; background-color: #f3f4f6; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #064e3b, #065f46); padding: 32px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 24px;">Sistema de Planeación y Programación</h1>
            <p style="color: #6ee7b7; margin: 8px 0 0; font-size: 14px;">Ejercicio Fiscal 2026</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #374151; font-size: 16px;">Hola <strong>{{ $user->name }}</strong>,</p>

            <p style="color: #4b5563; font-size: 15px;">
                Has sido invitado a participar en el Sistema de Planeación y Programación
                como <strong>{{ $roleName }}</strong> en la unidad <strong>{{ $urName }}</strong>.
            </p>

            <p style="color: #4b5563; font-size: 15px;">
                Para activar tu cuenta, haz clic en el siguiente enlace:
            </p>

            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ $activationUrl }}"
                   style="background-color: #059669; color: white; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; display: inline-block;">
                    Activar mi cuenta
                </a>
            </div>

            <p style="color: #6b7280; font-size: 13px;">
                Este enlace expira en 72 horas. Si no solicitaste esta invitación, ignora este mensaje.
            </p>

            <p style="color: #6b7280; font-size: 13px; margin-top: 24px;">
                Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                <span style="color: #059669; word-break: break-all;">{{ $activationUrl }}</span>
            </p>
        </div>

        {{-- Footer --}}
        <div style="background: #f9fafb; padding: 16px 32px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                &copy; {{ date('Y') }} — Dirección de Tecnología y Evaluación
            </p>
            <p style="color: #c0c5cc; font-size: 10px; margin: 4px 0 0;">
                Desarrollado por eleacid — Ing. en Sistemas Computacionales, Especialista en TALL
            </p>
        </div>
    </div>
</body>
</html>
