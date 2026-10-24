#pragma once
#include <Arduino.h>
#include <WebServer.h>
#include <Preferences.h>

/**
 * ============================================================================
 * SISCO-EDU: MÓDULO DE SEGURIDAD Y CONTROL DE ACCESO PARA ESP32
 * ============================================================================
 * Protege los portales cautivos y servidores web embebidos en el hardware:
 *  - Autenticación por sesión (Cookie HTTP).
 *  - Credenciales de fábrica predeterminadas: admin / sisco2026.
 *  - Obligatoriedad estricta de cambio de contraseña en el primer inicio.
 *  - Bloqueo de visualización de configuraciones hasta cambiar la clave.
 *  - Persistencia segura en memoria flash no volátil (NVS).
 */

class SeguridadPortal {
private:
    WebServer *serverRef = nullptr;
    Preferences prefs;
    String nombreDispositivo;
    String adminUser = "admin";
    String adminPass = "sisco2026";
    bool passCambiada = false;
    String currentSessionToken = "";
    unsigned long sessionTimeoutMs = 1800000; // 30 minutos de sesión
    unsigned long lastActivity = 0;

public:
    SeguridadPortal(const String &nombreDisp = "Nodo ESP32") : nombreDispositivo(nombreDisp) {}

    void begin(WebServer &server) {
        serverRef = &server;
        cargarCredenciales();

        serverRef->on("/login", HTTP_GET, [this]() { handleLoginPage(); });
        serverRef->on("/login", HTTP_POST, [this]() { handleLoginSubmit(); });
        serverRef->on("/cambiar_password", HTTP_GET, [this]() { handleCambiarPasswordPage(); });
        serverRef->on("/cambiar_password", HTTP_POST, [this]() { handleCambiarPasswordSubmit(); });
        serverRef->on("/logout", HTTP_GET, [this]() { handleLogout(); });
    }

    void cargarCredenciales() {
        prefs.begin("sisco_sec", true);
        adminUser = prefs.getString("user", "admin");
        adminPass = prefs.getString("pass", "sisco2026");
        passCambiada = prefs.getBool("changed", false);
        prefs.end();

        Serial.println("[SEGURIDAD] Estado de acceso:");
        Serial.printf("  - Usuario: %s\n", adminUser.c_str());
        Serial.printf("  - Clave personalizada establecida: %s\n", passCambiada ? "SI (Segura)" : "NO (Clave de fabrica activa)");
    }

    void guardarNuevaPassword(const String &nuevaPass) {
        prefs.begin("sisco_sec", false);
        prefs.putString("pass", nuevaPass);
        prefs.putBool("changed", true);
        prefs.end();

        adminPass = nuevaPass;
        passCambiada = true;
        Serial.println("[SEGURIDAD] Nueva contrasena guardada en memoria flash NVS.");
    }

    void restablecerFabrica() {
        prefs.begin("sisco_sec", false);
        prefs.putString("user", "admin");
        prefs.putString("pass", "sisco2026");
        prefs.putBool("changed", false);
        prefs.end();

        adminUser = "admin";
        adminPass = "sisco2026";
        passCambiada = false;
        currentSessionToken = "";
        Serial.println("\n[SEGURIDAD] !!! RESET DE FABRICA REALIZADO: Credenciales restablecidas a admin/sisco2026 !!!\n");
    }

    bool estaAutenticado() {
        if (!serverRef) return false;
        if (currentSessionToken.length() == 0) return false;

        // Comprobar expiración por inactividad
        if (millis() - lastActivity > sessionTimeoutMs) {
            currentSessionToken = "";
            return false;
        }

        if (serverRef->hasHeader("Cookie")) {
            String cookie = serverRef->header("Cookie");
            if (cookie.indexOf("sisco_token=" + currentSessionToken) >= 0) {
                lastActivity = millis();
                return true;
            }
        }
        return false;
    }

    bool debeCambiarPassword() {
        return !passCambiada;
    }

    /**
     * Guardia de seguridad: valida autenticación y cambio obligatorio de clave.
     * Si no cumple, redirige inmediatamente y retorna FALSE.
     */
    bool verificarAcceso() {
        if (!estaAutenticado()) {
            serverRef->sendHeader("Location", "/login", true);
            serverRef->send(302, "text/plain", "");
            return false;
        }

        if (debeCambiarPassword()) {
            serverRef->sendHeader("Location", "/cambiar_password", true);
            serverRef->send(302, "text/plain", "");
            return false;
        }

        return true;
    }

    /**
     * Detección de botón físico presionado al inicio para resetear claves de fábrica.
     * @param pinBoton Pin GPIO a monitorear con pull-up.
     * @param tiempoMs Milisegundos sostenidos requeridos para confirmar el reseteo.
     * @param reiniciarInmediatamente Si es true, ejecuta ESP.restart() internamente.
     *                                Si es false, retorna true para permitir que el sketch
     *                                limpie datos propios (ej. Preferences Wi-Fi) antes de reiniciar.
     */
    bool verificarBotonReset(int pinBoton, unsigned long tiempoMs = 8000, bool reiniciarInmediatamente = true) {
        pinMode(pinBoton, INPUT_PULLUP);
        if (digitalRead(pinBoton) == LOW) {
            Serial.println("[SEGURIDAD] Detectado boton presionado. Mantener para reset de fabrica...");
            unsigned long t0 = millis();
            while (digitalRead(pinBoton) == LOW) {
                delay(100);
                if (millis() - t0 >= tiempoMs) {
                    restablecerFabrica();
                    while (digitalRead(pinBoton) == LOW) delay(100);
                    delay(500);
                    if (reiniciarInmediatamente) {
                        ESP.restart();
                    }
                    return true;
                }
            }
        }
        return false;
    }

    // Alias retrocompatible
    bool verificarBotonResetFisico(int pinBoton, unsigned long tiempoMs = 8000, bool reiniciarInmediatamente = false) {
        return verificarBotonReset(pinBoton, tiempoMs, reiniciarInmediatamente);
    }

private:
    void handleLoginPage(const String &errorMsg = "") {
        String html = F(R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Seguro | SISCO-EDU</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 1.25rem; max-width: 400px; width: 100%; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .header { text-align: center; margin-bottom: 1.75rem; }
    .header .icon { font-size: 2.5rem; margin-bottom: 0.5rem; display: inline-block; }
    .header h1 { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .header p { font-size: 0.8rem; color: #94a3b8; margin-top: 0.25rem; }
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.4rem; }
    input { width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 0.6rem; padding: 0.75rem 0.85rem; color: #fff; font-size: 0.95rem; }
    input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
    .btn { display: block; width: 100%; background: #4f46e5; color: #fff; font-weight: 600; font-size: 0.95rem; padding: 0.85rem; border: none; border-radius: 0.6rem; cursor: pointer; transition: background 0.2s; margin-top: 0.5rem; }
    .btn:hover { background: #4338ca; }
    .error-box { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 0.65rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; margin-bottom: 1.25rem; text-align: center; }
    .footer-note { text-align: center; font-size: 0.72rem; color: #64748b; margin-top: 1.5rem; line-height: 1.4; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <div class="icon">🔐</div>
      <h1>SISCO-EDU</h1>
      <p>)rawliteral");
        html += nombreDispositivo;
        html += F(R"rawliteral(</p>
    </div>
)rawliteral");

        if (errorMsg.length() > 0) {
            html += "<div class=\"error-box\">" + errorMsg + "</div>";
        }

        html += F(R"rawliteral(
    <form method="POST" action="/login">
      <div class="form-group">
        <label for="usuario">Usuario Administrador</label>
        <input type="text" id="usuario" name="usuario" placeholder="admin" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Contraseña de Acceso</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn">Ingresar al Portal</button>
    </form>

    <div class="footer-note">
      Acceso exclusivo para administradores y técnicos autorizados.
    </div>
  </div>
</body>
</html>)rawliteral");

        serverRef->send(200, "text/html", html);
    }

    void handleLoginSubmit() {
        String u = serverRef->arg("usuario");
        String p = serverRef->arg("password");

        if (u == adminUser && p == adminPass) {
            // Generar token de sesión seguro
            currentSessionToken = String(random(10000000, 99999999)) + String(millis());
            lastActivity = millis();

            String cookieHeader = "sisco_token=" + currentSessionToken + "; Path=/; HttpOnly";
            serverRef->sendHeader("Set-Cookie", cookieHeader);

            if (debeCambiarPassword()) {
                serverRef->sendHeader("Location", "/cambiar_password", true);
            } else {
                serverRef->sendHeader("Location", "/", true);
            }
            serverRef->send(302, "text/plain", "");
        } else {
            handleLoginPage("Usuario o contraseña incorrectos.");
        }
    }

    void handleCambiarPasswordPage(const String &errorMsg = "") {
        if (!estaAutenticado()) {
            serverRef->sendHeader("Location", "/login", true);
            serverRef->send(302, "text/plain", "");
            return;
        }

        String html = F(R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguridad Obligatoria | SISCO-EDU</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.25rem; }
    .card { background: #1e293b; border: 1px solid #334155; border-radius: 1.25rem; max-width: 440px; width: 100%; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .header { text-align: center; margin-bottom: 1.5rem; }
    .header .icon { font-size: 2.5rem; margin-bottom: 0.5rem; display: inline-block; }
    .header h1 { font-size: 1.35rem; font-weight: 700; color: #fbbf24; }
    .header p { font-size: 0.82rem; color: #cbd5e1; margin-top: 0.35rem; line-height: 1.4; }
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.4rem; }
    input { width: 100%; background: #0f172a; border: 1px solid #334155; border-radius: 0.6rem; padding: 0.75rem 0.85rem; color: #fff; font-size: 0.95rem; }
    input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
    .btn { display: block; width: 100%; background: #10b981; color: #fff; font-weight: 600; font-size: 0.95rem; padding: 0.85rem; border: none; border-radius: 0.6rem; cursor: pointer; transition: background 0.2s; margin-top: 1rem; }
    .btn:hover { background: #059669; }
    .error-box { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 0.65rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; margin-bottom: 1.25rem; text-align: center; }
    .info-box { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fde68a; padding: 0.75rem 0.9rem; border-radius: 0.6rem; font-size: 0.78rem; margin-bottom: 1.25rem; line-height: 1.4; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <div class="icon">🛡️</div>
      <h1>Cambio de Clave Obligatorio</h1>
      <p>Por seguridad física e informática, debes establecer una contraseña segura antes de poder acceder al panel de configuración.</p>
    </div>

    <div class="info-box">
      ⚠️ El dispositivo detectó la clave de fábrica predeterminada. Ingresa tu nueva contraseña para proteger el nodo contra manipulaciones.
    </div>
)rawliteral");

        if (errorMsg.length() > 0) {
            html += "<div class=\"error-box\">" + errorMsg + "</div>";
        }

        html += F(R"rawliteral(
    <form method="POST" action="/cambiar_password">
      <div class="form-group">
        <label for="nueva_pass">Nueva Contraseña (mínimo 6 caracteres)</label>
        <input type="password" id="nueva_pass" name="nueva_pass" minlength="6" required autofocus>
      </div>

      <div class="form-group">
        <label for="confirm_pass">Confirmar Nueva Contraseña</label>
        <input type="password" id="confirm_pass" name="confirm_pass" minlength="6" required>
      </div>

      <button type="submit" class="btn">Guardar Nueva Contraseña y Continuar</button>
    </form>
  </div>
</body>
</html>)rawliteral");

        serverRef->send(200, "text/html", html);
    }

    void handleCambiarPasswordSubmit() {
        if (!estaAutenticado()) {
            serverRef->sendHeader("Location", "/login", true);
            serverRef->send(302, "text/plain", "");
            return;
        }

        String n1 = serverRef->arg("nueva_pass");
        String n2 = serverRef->arg("confirm_pass");

        if (n1.length() < 6) {
            handleCambiarPasswordPage("La contraseña debe tener al menos 6 caracteres.");
            return;
        }

        if (n1 != n2) {
            handleCambiarPasswordPage("Las contraseñas no coinciden. Inténtalo nuevamente.");
            return;
        }

        if (n1 == "sisco2026") {
            handleCambiarPasswordPage("Debes elegir una contraseña diferente a la clave de fábrica.");
            return;
        }

        guardarNuevaPassword(n1);

        String okHtml = F(R"rawliteral(<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seguridad Actualizada</title>
<style>body{background:#0f172a;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:1rem;}
.box{background:#1e293b;padding:2rem;border-radius:1rem;border:1px solid #334155;max-width:400px;}
h2{color:#34d399;margin-bottom:0.5rem;}p{color:#cbd5e1;font-size:0.9rem;line-height:1.4;}</style>
<script>setTimeout(function(){window.location.href='/';},2500);</script>
</head><body><div class="box">
<h2>✅ ¡Seguridad Activada!</h2>
<p>La nueva contraseña se guardó permanentemente en la memoria flash NVS.</p>
<p style="margin-top:0.75rem;font-size:0.8rem;color:#94a3b8;">Redirigiendo al panel de configuración...</p>
</div></body></html>)rawliteral");

        serverRef->send(200, "text/html", okHtml);
    }

    void handleLogout() {
        currentSessionToken = "";
        serverRef->sendHeader("Set-Cookie", "sisco_token=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT");
        serverRef->sendHeader("Location", "/login", true);
        serverRef->send(302, "text/plain", "");
    }
};
