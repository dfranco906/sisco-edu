#pragma once
#include <Arduino.h>

/**
 * ============================================================================
 * SISCO-EDU: INTERFAZ WEB EMBEBIDA PARA ESP32 NODO DE AULA
 * ============================================================================
 * Portal cautivo para aprovisionamiento en campo de nodos de aula LoRa RYLR998.
 */

const char PAGINA_CONFIG_AULA[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aprovisionamiento de Aula | SISCO-EDU</title>
  <style>
    :root {
      --bg: #0f172a; --panel: #1e293b; --border: #334155; --text: #f8fafc;
      --muted: #94a3b8; --primary: #4f46e5; --primary-hover: #4338ca; --success: #10b981;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background-color: var(--bg); color: var(--text); padding: 1.25rem; min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; }
    .container { width: 100%; max-width: 540px; }
    .header { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
    .header h1 { font-size: 1.45rem; font-weight: 700; color: #fff; }
    .header p { color: var(--muted); font-size: 0.85rem; margin-top: 0.25rem; }
    .badge { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem; background: rgba(79, 70, 229, 0.15); color: #818cf8; border: 1px solid rgba(79, 70, 229, 0.4); }
    .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .panel-header { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 0.5rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group:last-child { margin-bottom: 0; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 0.4rem; }
    input[type="text"], input[type="number"] {
      width: 100%; background: #0f172a; border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.7rem 0.85rem; color: #fff; font-size: 0.95rem; font-family: monospace;
    }
    input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
    .hint { display: block; font-size: 0.72rem; color: #64748b; margin-top: 0.35rem; line-height: 1.3; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 600; font-size: 0.95rem; padding: 0.85rem 1.25rem; border-radius: 0.6rem; border: none; cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .info-card { background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); padding: 0.85rem 1rem; border-radius: 0.75rem; font-size: 0.78rem; color: #93c5fd; margin-bottom: 1.25rem; line-height: 1.45; }
    .footer { display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; padding-top: 0.75rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🏫 SISCO-EDU</h1>
      <p>Aprovisionamiento de Hardware: Nodo de Aula LoRa</p>
      <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:0.5rem;">
        <span class="badge">🟠 MODO SETUP AP (192.168.4.1)</span>
        <a href="/logout" class="badge" style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.4);text-decoration:none;">🔒 Salir</a>
      </div>
    </div>

    <div class="info-card">
      📋 <strong>Datos desde el Panel Web:</strong> Ingresa aquí el <strong>ID de Aula</strong>, <strong>Código de Aula</strong> y <strong>LoRa ID</strong> que asignaste previamente en el panel administrativo de SISCO-EDU.
    </div>

    <form method="POST" action="/guardar">
      <div class="panel">
        <div class="panel-header">
          <span>🏛️</span> Identificación del Aula
        </div>

        <div class="form-group">
          <label for="id_aula">ID del Aula en Base de Datos (id_aula) *</label>
          <input type="number" id="id_aula" name="id_aula" value="%ID_AULA%" placeholder="ej. 28" min="1" required autofocus>
          <small class="hint">Identificador primario de la tabla aulas en el servidor central.</small>
        </div>

        <div class="form-group">
          <label for="codigo_aula">Código RF del Aula (OLED y Filtro RF) *</label>
          <input type="text" id="codigo_aula" name="codigo_aula" value="%CODIGO_AULA%" placeholder="ej. 1_RO_BTI" required>
          <small class="hint">Nombre visible en la pantalla OLED del aula y filtro de paquetes LoRa.</small>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span>📻</span> Parámetros de Radiofrecuencia LoRa (RYLR998)
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="lora_id">Dirección LoRa del Aula (Addr) *</label>
            <input type="number" id="lora_id" name="lora_id" value="%LORA_ID%" min="1" max="65535" placeholder="108" required>
            <small class="hint">Dirección única de este aula (ej. 101-199).</small>
          </div>

          <div class="form-group">
            <label for="lora_red_id">Red LoRa (Network ID) *</label>
            <input type="number" id="lora_red_id" name="lora_red_id" value="%LORA_RED_ID%" min="1" max="255" required>
            <small class="hint">Red compartida con el Gateway (def: 18).</small>
          </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
          <label for="gateway_id">Dirección LoRa del Gateway *</label>
          <input type="number" id="gateway_id" name="gateway_id" value="%GATEWAY_ID%" min="1" max="65535" required>
          <small class="hint">Dirección destino para reportar asistencias (def: 100).</small>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        💾 Guardar en Memoria NVS y Activar Modo Aula
      </button>

      <div class="footer">
        <span>MAC: %MAC%</span>
        <span>Firmware: v2.4-Aula</span>
      </div>
    </form>
  </div>
</body>
</html>
)rawliteral";

const char PAGINA_GUARDADO_AULA[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aula Configurada | SISCO-EDU</title>
  <style>
    body { background: #0f172a; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; padding: 1rem; }
    .box { background: #1e293b; padding: 2.5rem; border-radius: 1.25rem; border: 1px solid #334155; max-width: 440px; }
    .spinner { font-size: 3rem; margin-bottom: 1rem; }
    h2 { font-size: 1.4rem; color: #34d399; margin-bottom: 0.75rem; }
    p { color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.5rem; }
  </style>
</head>
<body>
  <div class="box">
    <div class="spinner">🎉</div>
    <h2>¡Aula Configurada Exitosamente!</h2>
    <p>Los parámetros se guardaron permanentemente en memoria NVS.</p>
    <p>El nodo se reiniciará en modo operativo normal (LoRa + Sensor Biométrico DY50).</p>
  </div>
</body>
</html>
)rawliteral";
