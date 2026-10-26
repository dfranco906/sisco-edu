# Identidad Visual y Logotipo: SISCO-EDU

Este documento detalla el diseño, la justificación semiótica, las especificaciones técnicas y la suite de archivos creados para la nueva identidad de marca de **SISCO-EDU**.

---

## ✒️ 1. Concepto y Semiótica del Isotipo: *La Huella-Pluma*

El símbolo sintetiza los dos pilares estratégicos de la plataforma en un trazo unificado y continuo:

1. **La Pluma de Ave / Estilográfica (Educación y Sabiduría):**
   * Representa la pedagogía, la escritura, la docencia, el plan curricular y el desarrollo académico continuo de los estudiantes.
   * Su orientación diagonal dinámica proyecta avance, agilidad y crecimiento educativo.
2. **Las Crestas Papilares de la Huella Dactilar (Biometría DY50 e Identidad):**
   * Las barbas y el raquis de la pluma están constituidos por las crestas friccionales de una huella dactilar humana.
   * Evoca la autenticación en tiempo real, la seguridad y el control de asistencia automatizado.
   * En el bucle central superior, el trazo traza fluidamente la curva en **"S"** de **SISCO**.

![Emblema Badge SISCO-EDU](/C:/Users/dfran/.gemini/antigravity/brain/0f6ba5db-eea7-433e-b2c7-830ddb31bf1b/logo-icon.png)

---

## 🎨 2. Paleta de Color Institucional

| Tono | Hexadecimal | Aplicación | Significado |
| :--- | :--- | :--- | :--- |
| **Azul Cobalto** | `#2563EB` | Base de gradiente primario | Confianza institucional y robustez técnica |
| **Índigo Eléctrico** | `#4F46E5` | Centro del isotipo y acentos | Tecnología, biometría y software inteligente |
| **Cian Tecnológico** | `#06B6D4` / `#38BDF8` | Destellos y modo oscuro | Conectividad LoRa, IoT en vivo y modernidad |
| **Pizarra Nocturna** | `#0F172A` / `#1E1B4B` | Fondo de Badge y Sidebar | Elegancia, contraste de alta visibilidad |

---

## 📂 3. Suite de Archivos Generados

Todos los recursos se encuentran disponibles en [`public/img/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/public/img/) y [`public/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/public/):

```
public/
├── favicon.ico                  # Multi-resolución (16x16, 32x32, 48x48) para navegadores
├── favicon.svg                  # Favicon vectorial nítido con soporte de tema claro/oscuro
├── favicon-16x16.png            # Favicon para resoluciones estándar
├── favicon-32x32.png            # Favicon para pantallas Retina / pestañas de alta densidad
├── favicon-96x96.png            # Favicon moderno para Chrome / Edge
├── apple-touch-icon.png         # Icono 180x180 para dispositivos móviles y iOS
├── site.webmanifest             # Manifiesto PWA para instalación web progresiva
└── img/
    ├── logo-icon.svg            # Isotipo vectorial transparente con gradiente corporativo
    ├── logo-badge.svg           # Emblema dentro de Squircle (superelipse) con efecto glow
    ├── logo-icon.png            # Rasterizado en alta resolución (512x512)
    ├── logo-feather-transparent.png # Pluma biométrica transparente (512x512)
    ├── logo.svg                 # Logotipo horizontal completo (Isotipo + "SISCO-EDU" + Descriptor)
    └── logo-dark.svg            # Logotipo horizontal optimizado para fondos oscuros
```

---

## 🖥️ 4. Integración en la Plataforma

1. **Favicon Universal en Todas las Vistas:**
   * Enlazado en [`mvc/views/layouts/header.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/mvc/views/layouts/header.php) para todas las pantallas autenticadas (Dashboard, Coordinación, Administración, etc.).
   * Enlazado en [`mvc/views/auth/login.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/mvc/views/auth/login.php) para la pantalla de bienvenida.
2. **Menú Lateral (Sidebar):**
   * **Modo Expandido:** Muestra el isotipo enmarcado en squircle junto al nombre de la institución y el subtítulo *"Gestión Académica & IoT"*.
   * **Modo Colapsado:** El badge mini `w-9 h-9` muestra el emblema de la pluma biométrica perfectamente alineado con los iconos del menú.
3. **Pantalla de Inicio de Sesión (Login):**
   * Presenta el emblema en tamaño `w-20 h-20` con aura degradada azul/índigo sobre el formulario.
