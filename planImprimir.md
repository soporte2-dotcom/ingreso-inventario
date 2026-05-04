# 🧾 Plan de Implementación — Botón Imprimir en Módulo Salidas

## 🎯 Objetivo
Permitir a los usuarios imprimir un documento de orden de salida inmediatamente después de guardarlo o al visualizar uno existente en el módulo Salidas.

## ⚠️ Contexto importante
- El sistema está desarrollado en PHP + jQuery (no usar frameworks modernos).
- Existe una referencia de diseño: exitOrdersNewPrintReport.js.
- No usar Tailwind CSS.
- Se puede usar Bootstrap solo si ya está disponible en el proyecto.
- Evitar cambios que afecten el funcionamiento actual de la aplicación.

## 🔧 Cambios requeridos

### 1. Frontend — Vista (index.php)
Agregar un botón con las siguientes características:
- Texto: "Imprimir"
- ID: btnprint
- Ubicación: misma fila de botones existentes (Lote, Etapas, etc.)
- Estado inicial: oculto usando style="display: none;"

### 2. Frontend — Lógica (salidas.js)

#### a) Mostrar botón
Agregar "btnprint" al array elementosMostrar dentro de la función configurarInterfazParaDocumentoExistente.

#### b) Función de impresión
Implementar la función imprimirDocumento() con el siguiente comportamiento:
- Obtener los datos del documento mediante llamadas AJAX al controlador existente.
- Construir dinámicamente el HTML del reporte usando template literals.
- Adaptar el diseño tomando como base exitOrdersNewPrintReport.js.
- Usar Bootstrap únicamente si ya está disponible en el proyecto.
- Formatear correctamente los datos como fechas, moneda y demás información relevante.

#### c) Evento
Registrar el evento de clic para el botón:
$('#btnprint').on('click', imprimirDocumento);

## 🧪 Plan de verificación

1. Crear una nueva Salida:
   - Verificar que aparece el botón "Imprimir".

2. Hacer clic en "Imprimir":
   - Validar que el documento generado contiene la información correcta y con un formato adecuado.

3. Abrir una Salida existente (por URL):
   - Verificar que el botón esté visible.
   - Confirmar que la impresión funciona correctamente.

## ✅ Resultado esperado
- El usuario puede imprimir órdenes de salida directamente desde la interfaz.
- El documento generado es claro, correcto y consistente con el sistema.
- La solución no afecta el funcionamiento actual de la aplicación.