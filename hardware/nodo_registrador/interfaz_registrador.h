#pragma once
#include <Arduino.h>

/**
 * ============================================================================
 * SISCO-EDU: INTERFAZ WEB EMBEBIDA PARA ESP32 NODO REGISTRADOR
 * ============================================================================
 * Portal cautivo y configuración Wi-Fi para el nodo enrolador biométrico DY50.
 * Almacenado en PROGMEM para autonomía total sin dependencias externas.
 */

const char PAGINA_CONFIG_REGISTRADOR[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración | Nodo Registrador Biométrico</title>
  <style>
    :root {
      --bg: #0f172a;
      --panel: #1e293b;
      --panel-border: #334155;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --primary: #4f46e5;
      --primary-hover: #4338ca;
      --accent: #06b6d4;
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg); color: var(--text); padding: 1.25rem; min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; }
    .container { width: 100%; max-width: 540px; }
    .header { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--panel-border); }
    .header h1 { font-size: 1.45rem; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
    .header p { color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem; }
    .badge-status { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem; }
    .badge-online { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
    .badge-ap { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
    
    .panel { background: var(--panel); border: 1px solid var(--panel-border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .panel-header { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 0.5rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group:last-child { margin-bottom: 0; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
    .input-wrap { position: relative; display: flex; align-items: center; }
    input[type="text"], input[type="password"] {
      width: 100%; background: #0f172a; border: 1px solid var(--panel-border); border-radius: 0.6rem; padding: 0.7rem 0.85rem; color: #fff; font-size: 0.95rem; transition: border-color 0.2s;
    }
    input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
    .toggle-pass { position: absolute; right: 0.75rem; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; padding: 0.25rem; }
    .hint { display: block; font-size: 0.72rem; color: #64748b; margin-top: 0.35rem; line-height: 1.3; }
    
    .info-card { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.78rem; color: #93c5fd; margin-bottom: 1.25rem; line-height: 1.45; }
    
    .status-list { list-style: none; font-size: 0.8rem; }
    .status-list li { display: flex; justify-content: space-between; padding: 0.45rem 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
    .status-list li:last-child { border-bottom: none; }
    .status-list .label { color: var(--text-muted); }
    .status-list .val { font-family: monospace; color: #fff; font-weight: 600; }
    
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 600; font-size: 0.95rem; padding: 0.85rem 1.25rem; border-radius: 0.6rem; border: none; cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    
    .footer { display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; padding-top: 0.75rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>👆 SISCO-EDU</h1>
      <p>Aprovisionamiento de Hardware: Nodo Registrador Biométrico</p>
      <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:0.5rem;">
        <span class="badge-status %MODO_CLASE%">%MODO_TEXTO%</span>
        <a href="/logout" class="badge-status" style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.4);text-decoration:none;">🔒 Salir</a>
      </div>
    </div>

    <div class="info-card">
      📶 <strong>Conexión Wi-Fi Requerida:</strong> Ingrese el SSID y la contraseña de la red Wi-Fi local para que la aplicación web de SISCO-EDU pueda comunicarse directamente con este lector biométrico.
    </div>

    <form method="POST" action="/guardar">
      <div class="panel">
        <div class="panel-header">
          <span>📡</span> Parámetros de Red Wi-Fi
        </div>

        <div class="form-group">
          <label for="ssid">Nombre de la Red Wi-Fi (SSID) *</label>
          <input type="text" id="ssid" name="ssid" value="%SSID%" placeholder="MiRedWiFi" required autofocus>
          <small class="hint">Red Wi-Fi 2.4 GHz a la que se conectará el nodo registrador.</small>
        </div>

        <div class="form-group">
          <label for="pass">Contraseña Wi-Fi *</label>
          <div class="input-wrap">
            <input type="password" id="pass" name="pass" value="%PASS%" placeholder="••••••••" required>
            <button type="button" class="toggle-pass" onclick="togglePass('pass')">👁️</button>
          </div>
          <small class="hint">Clave de acceso WPA/WPA2 de la red inalámbrica.</small>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span>🔍</span> Diagnóstico y Hardware
        </div>

        <ul class="status-list">
          <li><span class="label">Sensor DY50 / AS608:</span><span class="val" style="color:#34d399;">%ESTADO_SENSOR%</span></li>
          <li><span class="label">Contrato Biométrico:</span><span class="val">1536B (3072 HEX)</span></li>
          <li><span class="label">Dirección MAC:</span><span class="val">%MAC%</span></li>
          <li><span class="label">Tiempo Activo:</span><span class="val">%UPTIME%</span></li>
        </ul>
      </div>

      <button type="submit" class="btn btn-primary">
        💾 Guardar en Memoria NVS y Conectar
      </button>

      <div class="footer">
        <span>Hardware: ESP32 + DY50</span>
        <span>Firmware: v2.4-Registrador</span>
      </div>
    </form>
  </div>

  <script>
    function togglePass(id) {
      var el = document.getElementById(id);
      el.type = el.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
)rawliteral";

const char PAGINA_GUARDADO_REGISTRADOR[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrador Configurado | SISCO-EDU</title>
  <style>
    body { background: #0f172a; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; padding: 1rem; }
    .box { background: #1e293b; padding: 2.5rem; border-radius: 1.25rem; border: 1px solid #334155; max-width: 440px; }
    .icon { font-size: 3rem; margin-bottom: 1rem; }
    h2 { font-size: 1.4rem; color: #34d399; margin-bottom: 0.75rem; }
    p { color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.5rem; }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">💾</div>
    <h2>¡Credenciales Guardadas!</h2>
    <p>La configuración Wi-Fi se guardó permanentemente en memoria NVS.</p>
    <p>El nodo se reiniciará para conectarse a la red. Observe la pantalla OLED para consultar la IP asignada.</p>
  </div>
</body>
</html>
)rawliteral";
