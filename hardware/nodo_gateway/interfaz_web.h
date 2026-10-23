#pragma once
#include <Arduino.h>

/**
 * ============================================================================
 * SISCO-EDU: INTERFAZ WEB EMBEBIDA PARA ESP32 GATEWAY
 * ============================================================================
 * Portal cautivo y panel de administración web embebido en memoria Flash (PROGMEM).
 * No requiere conexión a Internet ni CDNs externos; 100% autónomo.
 */

const char PAGINA_CONFIGURACION[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SISCO-EDU | Gateway LoRa</title>
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
    .container { width: 100%; max-width: 640px; }
    .header { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--panel-border); }
    .header h1 { font-size: 1.5rem; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
    .header p { color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem; }
    .badge-status { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin-top: 0.75rem; }
    .badge-online { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
    .badge-ap { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
    
    .card-auto-ip { background: linear-gradient(135deg, rgba(79, 70, 229, 0.25) 0%, rgba(6, 182, 212, 0.2) 100%); border: 1px solid rgba(99, 102, 241, 0.4); border-radius: 1rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
    .card-auto-ip .title { font-size: 0.9rem; font-weight: 700; color: #a5b4fc; display: flex; align-items: center; gap: 0.5rem; }
    .card-auto-ip .desc { font-size: 0.8rem; color: #cbd5e1; line-height: 1.4; }
    .card-auto-ip .ip-chip { background: #0f172a; border: 1px solid #4338ca; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-family: monospace; font-weight: 700; color: #38bdf8; }
    
    .panel { background: var(--panel); border: 1px solid var(--panel-border); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .panel-header { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 0.5rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group:last-child { margin-bottom: 0; }
    label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; }
    .input-wrap { position: relative; display: flex; align-items: center; }
    input[type="text"], input[type="password"], input[type="number"], select {
      width: 100%; background: #0f172a; border: 1px solid var(--panel-border); border-radius: 0.6rem; padding: 0.65rem 0.85rem; color: #fff; font-size: 0.9rem; transition: border-color 0.2s;
    }
    input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3); }
    .toggle-pass { position: absolute; right: 0.75rem; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; padding: 0.25rem; }
    .hint { display: block; font-size: 0.72rem; color: #64748b; margin-top: 0.35rem; line-height: 1.3; }
    
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 600; font-size: 0.9rem; padding: 0.75rem 1.25rem; border-radius: 0.6rem; border: none; cursor: pointer; transition: all 0.2s; width: 100%; text-decoration: none; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-secondary { background: #334155; color: #cbd5e1; }
    .btn-secondary:hover { background: #475569; color: #fff; }
    .btn-auto { background: #4f46e5; color: #fff; font-size: 0.8rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; width: auto; align-self: flex-start; }
    .btn-auto:hover { background: #4338ca; }
    .btn-outline { background: transparent; border: 1px solid var(--panel-border); color: var(--text-muted); font-size: 0.8rem; padding: 0.4rem 0.75rem; width: auto; }
    .btn-outline:hover { color: #fff; border-color: #64748b; }
    
    .test-box { margin-top: 0.75rem; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; font-family: monospace; display: none; }
    .test-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #34d399; }
    .test-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; }
    .test-loading { background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.4); color: #38bdf8; }

    .toast { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); background: #1e293b; border: 1px solid #4f46e5; color: #fff; padding: 0.65rem 1.25rem; border-radius: 9999px; font-size: 0.85rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5); opacity: 0; pointer-events: none; transition: opacity 0.3s; z-index: 100; }
    .toast.show { opacity: 1; }

    .info-bar { display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; padding-top: 0.5rem; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>📡 SISCO-EDU</h1>
      <p>Configuración Remota del Nodo Gateway LoRa / Wi-Fi</p>
      <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:0.5rem;">
        <div class="badge-status %MODO_CLASE%">%MODO_TEXTO%</div>
        <a href="/logout" class="badge-status" style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.4);text-decoration:none;">🔒 Salir</a>
      </div>
    </div>

    <!-- TARJETA AUTO-DETECCIÓN INTELIGENTE DE IP DEL SERVIDOR -->
    <div class="card-auto-ip" id="auto-ip-card" style="display: %AUTO_IP_DISPLAY%;">
      <div class="title">
        <span>⚡</span> Servidor Detectado Autom&aacute;ticamente
      </div>
      <div class="desc">
        Est&aacute;s conectado desde el equipo con IP <span class="ip-chip" id="chip-client-ip">%CLIENT_IP%</span>. Si esta máquina aloja el servidor backend SISCO-EDU (XAMPP / Apache), puedes auto-asignarla sin escribirla manualmente:
      </div>
      <button type="button" class="btn btn-auto" onclick="usarIpCliente('%CLIENT_IP%')">
        👉 Usar http://%CLIENT_IP%/sisco-edu/
      </button>
    </div>

    <form method="POST" action="/guardar">
      
      <!-- PANEL 1: SERVIDOR CENTRAL BACKEND -->
      <div class="panel">
        <div class="panel-header">
          <span>🖥️</span> Servidor Central SISCO-EDU
        </div>

        <div class="form-group">
          <label for="server_url">URL o IP del Servidor Web</label>
          <div class="input-wrap">
            <input type="text" id="server_url" name="server_url" value="%SERVER_URL%" placeholder="http://192.168.100.59/sisco-edu/" required>
          </div>
          <small class="hint">
            Puedes escribir únicamente la IP (ej. <code>192.168.100.59</code>) o la URL completa terminada en <code>/sisco-edu/</code>.
          </small>
        </div>

        <div class="form-group">
          <label for="api_key">Clave de Seguridad API (X-GATEWAY-KEY)</label>
          <div class="input-wrap">
            <input type="password" id="api_key" name="api_key" value="%API_KEY%" required>
            <button type="button" class="toggle-pass" onclick="togglePassword('api_key')">👁️</button>
          </div>
          <small class="hint">Token secreto de enlace HTTP para autorizar sincronizaciones y marcaciones.</small>
        </div>

        <div style="margin-top: 1rem;">
          <button type="button" class="btn btn-secondary" onclick="probarServidor()">
            🔄 Probar Conexi&oacute;n con Servidor
          </button>
          <div id="test-result" class="test-box"></div>
        </div>
      </div>

      <!-- PANEL 2: RED WI-FI -->
      <div class="panel">
        <div class="panel-header" style="justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span>📶</span> Red Wi-Fi (Modo Estación)
          </div>
          <button type="button" class="btn btn-outline" onclick="escanearWifi()">
            🔍 Escanear Redes
          </button>
        </div>

        <div class="form-group" id="div-select-wifi" style="display: none;">
          <label for="select-wifi">Redes Wi-Fi Detectadas</label>
          <select id="select-wifi" onchange="seleccionarRedWifi(this.value)">
            <option value="">-- Selecciona una red detectada --</option>
          </select>
        </div>

        <div class="form-group">
          <label for="ssid">Nombre de Red Wi-Fi (SSID)</label>
          <div class="input-wrap">
            <input type="text" id="ssid" name="ssid" value="%SSID%" placeholder="Mi_Red_WiFi" required>
          </div>
        </div>

        <div class="form-group">
          <label for="pass">Contraseña Wi-Fi</label>
          <div class="input-wrap">
            <input type="password" id="pass" name="pass" value="%PASS%" placeholder="Contraseña Wi-Fi">
            <button type="button" class="toggle-pass" onclick="togglePassword('pass')">👁️</button>
          </div>
          <small class="hint">Deja en blanco si la red Wi-Fi no tiene contraseña.</small>
        </div>
      </div>

      <!-- PANEL 3: RADIO LoRa RYLR998 -->
      <div class="panel">
        <div class="panel-header">
          <span>📻</span> Par&aacute;metros de Radiofrecuencia LoRa
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label for="lora_net_id">LoRa Network ID</label>
            <input type="number" id="lora_net_id" name="lora_net_id" value="%LORA_NET%" min="1" max="255" required>
            <small class="hint">Red compartida con aulas (def: 18).</small>
          </div>
          <div class="form-group">
            <label for="lora_addr">LoRa Gateway Addr</label>
            <input type="number" id="lora_addr" name="lora_addr" value="%LORA_ADDR%" min="1" max="65535" required>
            <small class="hint">Dirección de este Gateway (def: 100).</small>
          </div>
        </div>
      </div>

      <!-- BOTÓN PRINCIPAL -->
      <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary" style="padding: 1rem; font-size: 1rem;">
          💾 Guardar Par&aacute;metros y Reiniciar Gateway
        </button>
        <button type="button" class="btn btn-secondary" onclick="reiniciarGateway()">
          🔁 Reiniciar Dispositivo
        </button>
      </div>

      <div class="info-bar" style="margin-top: 1.5rem;">
        <span>MAC: %MAC%</span>
        <span>Uptime: %UPTIME%</span>
        <span>Firmware: v2.4-NVS</span>
      </div>
    </form>
  </div>

  <div id="toast" class="toast"></div>

  <script>
    function togglePassword(id) {
      const input = document.getElementById(id);
      if (input) {
        input.type = input.type === 'password' ? 'text' : 'password';
      }
    }

    function mostrarToast(mensaje) {
      const toast = document.getElementById('toast');
      toast.textContent = mensaje;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function usarIpCliente(ip) {
      const input = document.getElementById('server_url');
      input.value = 'http://' + ip + '/sisco-edu/';
      mostrarToast('Servidor asignado: ' + input.value);
    }

    async function probarServidor() {
      const box = document.getElementById('test-result');
      box.style.display = 'block';
      box.className = 'test-box test-loading';
      box.innerHTML = '⏳ Conectando con el servidor backend configurado...';

      const urlServidor = encodeURIComponent(document.getElementById('server_url').value.trim());
      const apiKey = encodeURIComponent(document.getElementById('api_key').value.trim());

      try {
        const resp = await fetch('/test_server?url=' + urlServidor + '&key=' + apiKey);
        const data = await resp.json();

        if (data.status === 'success') {
          box.className = 'test-box test-success';
          box.innerHTML = '🟢 <strong>ONLINE (' + data.latencia_ms + ' ms):</strong> ' + data.message;
        } else {
          box.className = 'test-box test-error';
          box.innerHTML = '🔴 <strong>ERROR:</strong> ' + data.message;
        }
      } catch (e) {
        box.className = 'test-box test-error';
        box.innerHTML = '🔴 <strong>FALLO DE COMUNICACIÓN:</strong> ' + e.message;
      }
    }

    async function escanearWifi() {
      mostrarToast('Escaneando redes Wi-Fi...');
      const divSelect = document.getElementById('div-select-wifi');
      const select = document.getElementById('select-wifi');
      select.innerHTML = '<option value="">⏳ Escaneando redes Wi-Fi...</option>';
      divSelect.style.display = 'block';

      try {
        const resp = await fetch('/scan_wifi');
        const redes = await resp.json();

        select.innerHTML = '<option value="">-- Selecciona una red Wi-Fi (' + redes.length + ' encontradas) --</option>';
        redes.forEach(red => {
          const opt = document.createElement('option');
          opt.value = red.ssid;
          opt.textContent = red.ssid + ' (' + red.rssi + ' dBm' + (red.secure ? ' 🔒' : '') + ')';
          select.appendChild(opt);
        });
        mostrarToast('Se encontraron ' + redes.length + ' redes');
      } catch (e) {
        select.innerHTML = '<option value="">Error al escanear redes</option>';
        mostrarToast('Error al escanear: ' + e.message);
      }
    }

    function seleccionarRedWifi(ssid) {
      if (ssid) {
        document.getElementById('ssid').value = ssid;
        document.getElementById('pass').focus();
        mostrarToast('Red seleccionada: ' + ssid);
      }
    }

    function reiniciarGateway() {
      if (confirm('¿Deseas reiniciar el Gateway LoRa ahora?')) {
        fetch('/reiniciar').then(() => {
          alert('El Gateway se está reiniciando...');
          setTimeout(() => location.reload(), 4000);
        });
      }
    }
  </script>
</body>
</html>
)rawliteral";

const char PAGINA_GUARDADO[] PROGMEM = R"rawliteral(<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guardado | SISCO-EDU</title>
  <style>
    body {
      background: #0f172a; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; padding: 1rem;
    }
    .box {
      background: #1e293b; padding: 2.5rem; border-radius: 1.25rem; border: 1px solid #334155; max-width: 440px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.6);
    }
    .spinner { font-size: 3rem; animation: spin 1.5s infinite linear; display: inline-block; margin-bottom: 1rem; }
    h2 { font-size: 1.4rem; color: #34d399; margin-bottom: 0.75rem; }
    p { color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1rem; }
    .redirect-msg { font-size: 0.8rem; color: #94a3b8; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  </style>
  <script>
    setTimeout(function() {
      window.location.href = '/';
    }, 6000);
  </script>
</head>
<body>
  <div class="box">
    <div class="spinner">⚙️</div>
    <h2>¡Configuraci&oacute;n Guardada!</h2>
    <p>Los par&aacute;metros se han guardado permanentemente en la memoria NVS del ESP32.</p>
    <p>El Gateway se est&aacute; reiniciando para conectar con la nueva configuraci&oacute;n...</p>
    <p class="redirect-msg">Reconectando a la interfaz en unos segundos...</p>
  </div>
</body>
</html>
)rawliteral";
