<?php

namespace app\service;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private $config;

    public function __construct()
    {
        $rutaConfig = __DIR__ . '/../../config/mail.local.ini';

        if (!file_exists($rutaConfig)) {
            throw new \RuntimeException(
                "No se encontró la configuración del correo."
            );
        }

        $this->config = parse_ini_file($rutaConfig);

        if (!$this->config) {
            throw new \RuntimeException(
                "No se pudo leer la configuración del correo."
            );
        }
    }

    public function enviarCodigo($destinatario, $usuario, $codigo)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->config["mail_host"];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config["mail_user"];
            $mail->Password = $this->config["mail_password"];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config["mail_port"];

            $mail->CharSet = "UTF-8";

            $mail->setFrom(
                $this->config["mail_user"],
                "Preguntados Musical"
            );

            $mail->addAddress($destinatario);

            $mail->isHTML(true);
            $mail->Subject = "Código de verificación - Preguntados Musical";

            $mail->Body = "
                <h2>¡Bienvenido a Preguntados Musical!</h2>
                <p>Hola, " . htmlspecialchars($usuario, ENT_QUOTES, "UTF-8") . ".</p>
                <p>Tu código de verificación es:</p>
                <h1>$codigo</h1>
                <p>Este código vence en 15 minutos.</p>
            ";

            $mail->AltBody = "Tu código de verificación es: $codigo. Vence en 15 minutos.";

            $mail->send();

            return true;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Error al enviar correo: " . $mail->ErrorInfo);
            return false;
        }
    }
}
