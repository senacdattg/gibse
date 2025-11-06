# Configuración DNS en Hostinger para gibse.dataguaviare.com.co

## ¿Qué es un Registro A?

Un **Registro A** (Address) es un tipo de registro DNS que apunta un dominio o subdominio a una dirección IP. En tu caso, necesitas apuntar el subdominio `gibse.dataguaviare.com.co` a la IP de tu VPS de Hostinger.

## Paso 1: Obtener la IP de tu VPS

Primero necesitas la IP pública de tu VPS de Hostinger:

1. **Desde el panel de Hostinger:**
   - Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
   - Ve a **VPS** → Selecciona tu VPS
   - La IP está visible en el panel principal

2. **Desde el VPS (si ya tienes acceso SSH):**
   ```bash
   curl ifconfig.me
   # O
   hostname -I
   ```

**Ejemplo de IP:** `185.123.45.67` (tu IP será diferente)

## Paso 2: Acceder a la Configuración DNS en Hostinger

### Opción A: Si el dominio está gestionado en Hostinger

1. Inicia sesión en [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Ve a **Dominios** → Selecciona `dataguaviare.com.co`
3. Busca la sección **Zona DNS** o **DNS Zone**
4. Haz clic en **Gestionar** o **Editar**

### Opción B: Si el dominio está en otro proveedor

Si `dataguaviare.com.co` está gestionado en otro proveedor (GoDaddy, Namecheap, etc.), debes configurar el DNS allí, no en Hostinger.

## Paso 3: Crear el Registro A para el Subdominio

En la sección de **Zona DNS**, busca el botón **Agregar registro** o **Add Record**.

### Configuración del Registro A:

| Campo | Valor | Descripción |
|-------|-------|-------------|
| **Tipo** | `A` | Tipo de registro DNS |
| **Nombre/Host** | `gibse` | Solo el subdominio (sin el dominio completo) |
| **Puntos a/Value** | `185.123.45.67` | La IP de tu VPS (reemplaza con tu IP real) |
| **TTL** | `3600` o `Auto` | Tiempo de vida del registro (1 hora) |

### ⚠️ IMPORTANTE:

- **Nombre:** Solo escribe `gibse` (NO escribas `gibse.dataguaviare.com.co`)
- **IP:** Debe ser la IP pública de tu VPS
- **TTL:** Puedes dejar el valor por defecto o usar 3600 segundos

### Ejemplo Visual:

```
┌─────────────────────────────────────────┐
│ Tipo: A                                  │
│ Nombre: gibse                            │
│ Puntos a: 185.123.45.67                 │
│ TTL: 3600                                │
│                                          │
│ [Guardar] [Cancelar]                    │
└─────────────────────────────────────────┘
```

## Paso 4: Guardar y Esperar la Propagación

1. Haz clic en **Guardar** o **Add Record**
2. **Espera la propagación DNS:**
   - Tiempo mínimo: 5-10 minutos
   - Tiempo típico: 15-30 minutos
   - Tiempo máximo: 24-48 horas (raro)

## Paso 5: Verificar que Funciona

### Opción 1: Desde tu computadora

```bash
# Windows (PowerShell)
nslookup gibse.dataguaviare.com.co

# Linux/Mac
dig gibse.dataguaviare.com.co
# O
host gibse.dataguaviare.com.co
```

**Resultado esperado:**
```
gibse.dataguaviare.com.co tiene la dirección 185.123.45.67
```

### Opción 2: Ping

```bash
ping gibse.dataguaviare.com.co
```

Debería mostrar la IP de tu VPS.

### Opción 3: Navegador

Abre en tu navegador:
```
http://gibse.dataguaviare.com.co
```

Si el DNS está configurado correctamente y el servidor está funcionando, deberías ver tu sitio.

## Solución de Problemas

### El DNS no resuelve después de 30 minutos

1. **Verifica que el registro esté correcto:**
   - Nombre: Solo `gibse` (sin el dominio)
   - IP: Correcta y sin espacios
   - Tipo: `A` (no AAAA, CNAME, etc.)

2. **Limpia la caché DNS:**
   ```bash
   # Windows
   ipconfig /flushdns
   
   # Linux
   sudo systemd-resolve --flush-caches
   
   # Mac
   sudo dscacheutil -flushcache
   ```

3. **Verifica desde otro lugar:**
   - Usa [whatsmydns.net](https://www.whatsmydns.net)
   - Busca `gibse.dataguaviare.com.co`
   - Verifica que apunte a tu IP

### El registro A ya existe

Si ya existe un registro A para `gibse`:
1. **Edítalo** en lugar de crear uno nuevo
2. Cambia la IP al valor de tu VPS
3. Guarda los cambios

### Error: "El registro ya existe"

- Solo puede haber **un registro A** por subdominio
- Si ya existe, **edítalo** en lugar de crear uno nuevo
- Elimina el registro antiguo si es necesario

### El dominio está en otro proveedor

Si `dataguaviare.com.co` está gestionado en otro proveedor (no Hostinger):

1. **Accede al panel de ese proveedor**
2. **Ve a la configuración DNS**
3. **Crea el registro A allí** (no en Hostinger)
4. Los pasos son los mismos, solo cambia el panel

## Estructura del DNS

```
dataguaviare.com.co (dominio principal)
    │
    ├── @ (registro A para el dominio principal)
    │   └── → IP del hosting principal
    │
    └── gibse (registro A para el subdominio) ← ESTE ES EL QUE CREAS
        └── → IP de tu VPS (185.123.45.67)
```

## Resumen Rápido

1. ✅ Obtén la IP de tu VPS de Hostinger
2. ✅ Ve a Dominios → dataguaviare.com.co → Zona DNS
3. ✅ Crea un registro A:
   - Nombre: `gibse`
   - Puntos a: `[IP de tu VPS]`
   - TTL: `3600`
4. ✅ Guarda y espera 15-30 minutos
5. ✅ Verifica con `nslookup gibse.dataguaviare.com.co`

## Después de Configurar el DNS

Una vez que el DNS esté funcionando:

1. **Configura SSL (HTTPS):**
   ```bash
   sudo certbot --nginx -d gibse.dataguaviare.com.co
   ```

2. **Verifica que el sitio funciona:**
   - HTTP: `http://gibse.dataguaviare.com.co`
   - HTTPS: `https://gibse.dataguaviare.com.co`

**📖 Siguiente paso:** Continúa con la [GUIA_DESPLIEGUE.md](GUIA_DESPLIEGUE.md) para completar la configuración del servidor.

## Preguntas Frecuentes

### ¿Puedo usar CNAME en lugar de A?

Sí, pero es mejor usar A directamente para subdominios que apuntan a IPs. CNAME es útil cuando apuntas a otro dominio.

### ¿Cuánto tiempo tarda en propagarse?

- Mínimo: 5-10 minutos
- Típico: 15-30 minutos
- Máximo: 24-48 horas (muy raro)

### ¿Necesito configurar algo más?

Solo el registro A es necesario. El resto (SSL, servidor web) se configura después.

### ¿Qué pasa si cambio la IP del VPS?

Solo necesitas **editar** el registro A existente y cambiar la IP. La propagación será más rápida (5-15 minutos).

