var tabla;
var usu_id = $('#user_id').val();
var rol_id = $('#rol_id').val();
var nitEspejo = ''; // NIT que fue auto-llenado en Enviar A desde Facturar A

let editingRow = null;
let originalData = {};

// Configuración centralizada
const CONFIG = {
    baseUrl: "../../controller/",
    endpoints: {
        permisos: "permisos.php?op=combo_salidas_permisos",
        permisos_tipos_originales: "permisos.php?op=combo_tipos_doc_originales",
        tipodoctos: {
            consecutivos: "tipodoctos.php?op=consecutivos"
        },
        terceros: {
            combo_dir: "terceros.php?op=combo_dir",
            telefono_dir: "terceros.php?op=telefono_dir"
        },
        salidas: {
            insert_doc_salida: "../../controller/salidas.php?op=insert_doc_salida",
            insert_doc_manual: "../../controller/salidas.php?op=insert_doc_manual",
            get_farm_info: "../../controller/salidas.php?op=get_farm_info",
            guardar_salida: "salidas.php?op=guardar_salida",
            update_lote_salida: "../../controller/salidas.php?op=update_lote_salida",
            agregar_linea_manual: "../../controller/salidas.php?op=agregar_linea_manual",
            get_info_producto: "../../controller/salidas.php?op=get_info_producto",
            combo_lotes: "salidas.php?op=combo_lotes",
            combo_transportador: "../../controller/documento.php?op=combo_transportador",
            combo_vehiculo: "../../controller/documento.php?op=combo_vehiculo",
            validar_excel_salidas: "../../controller/salidas.php?op=validar_excel_salidas",
            confirmar_excel_salidas: "../../controller/salidas.php?op=confirmar_excel_salidas",
            update_notas_etapa: "../../controller/salidas.php?op=update_notas_etapa",
            validar_os: "../../controller/salidas.php?op=validar_os",
            reiniciar_doc_desde_os: "../../controller/salidas.php?op=reiniciar_doc_desde_os",
            preview_doc_devolucion:    "../../controller/salidas.php?op=preview_doc_devolucion",
            get_lineas_devolucion:     "../../controller/salidas.php?op=get_lineas_devolucion",
            insert_devolucion_manual:  "../../controller/salidas.php?op=insert_devolucion_manual"
        },
        etapas: {
            listar_activas: "../../controller/etapas.php?op=listar_activas"
        },
        conceptosDevolucion: {
            listar_activos: "../../controller/conceptosdevolucion.php?op=listar_activos"
        },
        conceptosDotacion: {
            listar_activos: "../../controller/conceptosdotacion.php?op=listar_activos"
        },
        documento: {
            insert_doc_entrada: "documento.php?op=insert_doc_entrada",
            asignar_selecc: "documento.php?op=asignar_selecc",
            update_prod_doc: "documento.php?op=update_prod_doc",
            guardar_entrada: "documento.php?op=guardar_entrada",
            guardar_doc: "documento.php?op=guardar_doc",
            mostrar_entrada: "documento.php?op=mostrar_entrada",
            listar_detalle_entrada: "documento.php?op=listar_detalle_entrada",
            total_entrada: "documento.php?op=total_entrada",
            totales: "documento.php?op=totales",
            total_cantidad: "documento.php?op=total_cantidad",
            mostrarXproducto: "documento.php?op=mostrarXproducto",
            duplicar_linea: "documento.php?op=duplicar_linea",
            eliminar: "documento.php?op=eliminar",
            eliminar_masivo: "documento.php?op=eliminar_masivo",
            actualizar_producto_linea: "documento.php?op=actualizar_producto_linea"
        }
    }
};

function init() {
    // Inicialización si es necesaria
}

// La pantalla se recarga sola en varios flujos (reiniciar desde la OS, volver con el
// botón Atrás). Esas recargas las dispara el sistema, NO son el usuario abandonando el
// documento, así que el borrador tiene que sobrevivir. Sin esta marca, el listener de
// 'pagehide' de abajo alcanzaba a borrarlo antes de que la página volviera a cargar.
function recargarConservandoBorrador() {
    window.__conservarBorrador = true;
    window.location.reload();
}

// Forzar reload al volver con el botón Atrás del navegador (bfcache)
window.addEventListener('pageshow', function(e) {
    if (e.persisted) recargarConservandoBorrador();
});

// ─── Ciclo de vida del BORRADOR al salir de la pantalla ──────────────────────
// Regla de negocio: abandonar un borrador = se pierde. Lo que cambió es que ya no se
// pierde en silencio ni por una recarga que dispara el propio sistema:
//   1. beforeunload  -> avisa al usuario si hay líneas cargadas, y él decide.
//   2. pagehide      -> descarta, solo si el usuario ya confirmó que se va.
//   3. recargarConservandoBorrador() -> para las recargas internas de la aplicación,
//      que no son un abandono y deben conservar el documento.

// ¿Estamos en un borrador sin guardar? (la URL trae el número negativo)
function esBorradorSinGuardar() {
    if (window.__borradorGuardado) return false;
    var cons = getUrlParameter('consecutivo');
    return !!(getUrlParameter('tipo') && cons && parseInt(cons, 10) < 0);
}

// ¿El borrador tiene trabajo que se perdería? Un borrador vacío no vale un aviso.
function borradorTieneLineas() {
    try {
        return $.fn.DataTable.isDataTable('#tb-doc') &&
               $('#tb-doc').DataTable().rows().count() > 0;
    } catch (e) {
        return false;
    }
}

// Aviso antes de salir: el navegador muestra su diálogo nativo ("los cambios podrían no
// guardarse"). Es la protección clave -- antes, cerrar la pestaña o presionar F5 por
// reflejo borraba el documento en el acto, sin decir nada. Solo se avisa si hay líneas
// cargadas y la salida no la disparó la propia aplicación.
window.addEventListener('beforeunload', function(e) {
    if (window.__conservarBorrador) return;
    if (!esBorradorSinGuardar() || !borradorTieneLineas()) return;
    e.preventDefault();
    e.returnValue = '';   // los navegadores modernos muestran su propio texto
    return '';
});

// Descarte del BORRADOR al abandonar la pantalla. Solo llega aquí si el usuario ya
// confirmó el aviso de arriba (o si el borrador estaba vacío, donde no hay nada que
// perder). El endpoint solo elimina filas con Numero_Documento negativo y exportado='N',
// así que NUNCA toca un documento ya guardado. Los que se escapen por un cierre abrupto
// los recoge purgar_borradores_salida a las 12 horas.
window.addEventListener('pagehide', function() {
    // __conservarBorrador: la recarga la disparó la propia aplicación (ver
    // recargarConservandoBorrador), no el usuario cerrando o abandonando la pantalla.
    if (window.__borradorGuardado || window.__conservarBorrador) return;
    var cons = getUrlParameter('consecutivo');
    var tp   = getUrlParameter('tipo');
    if (tp && cons && parseInt(cons, 10) < 0 && navigator.sendBeacon) {
        var fd = new FormData();
        fd.append('tipo', tp);
        fd.append('numdoc', cons);
        navigator.sendBeacon('../../controller/salidas.php?op=descartar_borrador', fd);
    }
});

$(document).ready(function() {
    inicializarCombos();
    inicializarEventos();
    
    // Lista de tipos que requieren OS obligatoriamente
    const tiposRestringidos = ['215', '213', '914', '938', '947'];
    // Usuarios con permiso 'traslado_sin_os' pueden usar 215 y 938 también de forma manual
    if (window.permiteTraslado) {
        ['215', '938'].forEach(function(t) {
            const i = tiposRestringidos.indexOf(t);
            if (i !== -1) tiposRestringidos.splice(i, 1);
        });
    }
    window.tiposRestringidos = tiposRestringidos;

    if (window.permiteLoteManual) {
        $("#chkLoteManualWrap").show();
    }

    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    if(tipo && consecutivo){
        listardetalle(tipo, consecutivo);

        // Al volver tras guardar un borrador con "Imprimir", se recarga en el documento ya
        // numerado con ?print=1: se dispara la impresión una vez cargados los datos.
        if (getUrlParameter('print') === '1') {
            setTimeout(function() { imprimirDocumento(); }, 1500);
        }
    }
});

// FUNCIONES DE UTILIDAD
function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}

function validarCampoRequerido(valor, nombreCampo) {
    if (!valor || valor.trim() === '') {
        swal("Advertencia!", `El campo ${nombreCampo} es obligatorio`, "warning");
        return false;
    }
    return true;
}

function mostrarFeedbackExitoso(mensaje = "Cambio guardado correctamente") {
    const feedback = $(`<div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>✅ Éxito!</strong> ${mensaje}
    </div>`);
    
    $('body').append(feedback);
    
    setTimeout(() => {
        feedback.alert('close');
    }, 3000);
}

// FUNCIONES DE INICIALIZACIÓN
function inicializarDatepicker(selector, hiddenSelector, initialIso) {
    $(selector).datepicker({
        dateFormat: "dd/mm/yy",
        changeMonth: true,
        changeYear: true,
        onSelect: function(dateText) {
            // dateText viene en dd/mm/yyyy — convertir a YYYY-MM-DD para el hidden
            var parts = dateText.split("/");
            var iso = parts[2] + "-" + parts[1] + "-" + parts[0];
            if (hiddenSelector) $(hiddenSelector).val(iso);
        }
    });

    // Establecer valor inicial
    var initial = initialIso ? new Date(initialIso + "T00:00:00") : new Date();
    $(selector).datepicker("setDate", initial);
    if (hiddenSelector) {
        var d = initial;
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        $(hiddenSelector).val(d.getFullYear() + "-" + mm + "-" + dd);
    }
}

function inicializarCombos() {
    $.post(CONFIG.baseUrl + CONFIG.endpoints.permisos, function(data) {
        $('#idTipo').html(data);
        window.originalTipoOptions = data;
    });

    const isoHoy = $('#fecha_factura_iso').val() || moment().format('YYYY-MM-DD');
    inicializarDatepicker('#fecha_factura', '#fecha_factura_iso', isoHoy);
    inicializarDatepicker('#fecha_factura2', '#fecha_factura2_iso', null);
    inicializarComboLotes();
}

function inicializarComboLotes() {
    $.post(CONFIG.baseUrl + CONFIG.endpoints.salidas.combo_lotes, function(data) {
        $("#lote").html(data);
        $("#lote1").html(data);
    });
    $.post(CONFIG.endpoints.salidas.combo_transportador, function(data) {
        $('#idTransportador').html(data);
    });
    $.post(CONFIG.endpoints.salidas.combo_vehiculo, function(data) {
        $('#idVehiculo').html(data);
    });
}

function inicializarEventos() {
    // Evento para cambio de tipo de documento
    $("#idTipo").change(function() {
        const idTipo = $(this).val();
        const textoTipo = $(this).find('option:selected').text().trim();
        const esDev = textoTipo.startsWith('Dev');

        if (esDev) {
            // Modo devolución: ocultar docref, mostrar campos devolución
            $('#row_docref').hide();
            $('#docref').val('3'); // sin trigger para no llamar showInp()
            // Asegurar que numero esté visible
            document.getElementById("txt_numero").style.display = "inline-block";
            document.getElementById("numero").style.display = "inline-block";
            document.getElementById("div_fecha_factura").style.display = "none";
            // Ocultar campos manual si estaban visibles
            ['hr1','txt_nit1','nit1','txt_nombre1','nombre1','txt_direccion1','direccion1',
             'txt_telefono1','telefono1','hr2','txt_nit2','nit2','txt_nombre2','nombre2',
             'txt_direccion2','direccion2'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            // Resetear modo manual
            $('#chk_dev_manual').prop('checked', false);
            $('#col_tipo_doc_orig').show();
            $('#div_concepto_dev_manual').hide();
            // Mostrar toggle manual solo si tiene permiso
            if (window.permiteDevolucionManual) {
                $('#div_toggle_dev_manual').show();
            } else {
                $('#div_toggle_dev_manual').hide();
            }
            // Mostrar div devolución y cargar tipos originales con permisos del usuario
            $('#div_devolucion').show();
            $.post(CONFIG.baseUrl + CONFIG.endpoints.permisos_tipos_originales, function(data) {
                $('#tipoDocOrig').html(data);
            });
        } else {
            // Modo normal: mostrar docref, ocultar devolución
            $('#row_docref').show();
            $('#docref').val('0');
            showInp();
            $('#div_devolucion').hide();
            $('#tipoDocOrig').html('<option value="" disabled selected>Seleccione tipo...</option>');
            // Verificación de tipos restringidos (requieren OS)
            if (window.tiposRestringidos.includes(idTipo)) {
                $("#docref").val("0").change();
                $("#docref").prop('disabled', true);
                swal("Aviso!", "Este tipo de documento requiere obligatoriamente una Orden de Salida (OS).", "info");
            } else {
                $("#docref").prop('disabled', false);
            }
        }

        $.post(CONFIG.baseUrl + CONFIG.endpoints.tipodoctos.consecutivos, { idTipo }, function(data) {
            data = JSON.parse(data);
            // Consecutivo diferido: este número es solo TENTATIVO. El definitivo se asigna al Guardar
            // (evita huecos si el usuario abandona o si otro usuario guarda primero).
            $("#consecutivo").val(data.consecutivo + ' (tentativo)');
        });

        // Consultar información de la granja para auto-llenado
        $.post(CONFIG.endpoints.salidas.get_farm_info, { idTipo: idTipo }, function(data) {
            data = JSON.parse(data);
            if (data.status === "success") {
                const nit = data.nitCompany;
                const dir = String(data.dayEntryPrebail).trim();

                // Llenar nit1 y nit2
                $('#nit1').val(nit);
                $('#nit2').val(nit);

                // Cargar y pre-seleccionar direccion1
                $("#direccion1").html('<option value="" disabled selected>Seleccione...</option>');
                $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: nit }, function(html) {
                    $("#direccion1").html(html);
                    $("#direccion1 option").each(function() {
                        if ($(this).val().split(',')[0].trim() == dir) {
                            $(this).prop('selected', true);
                            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir,
                                   { direccion: $(this).val() }, function(tdata) {
                                tdata = JSON.parse(tdata);
                                $("#telefono1").val(tdata.telefono_1);
                            });
                            return false;
                        }
                    });
                });

                // Cargar y pre-seleccionar direccion2
                $("#direccion2").html('<option value="" disabled selected>Seleccione...</option>');
                $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: nit }, function(html) {
                    $("#direccion2").html(html);
                    $("#direccion2 option").each(function() {
                        if ($(this).val().split(',')[0].trim() == dir) {
                            $(this).prop('selected', true);
                            return false;
                        }
                    });
                });

                // Obtener nombre del tercero
                $.ajax({
                    url: CONFIG.baseUrl + "terceros.php?op=terceroxnit",
                    type: "GET",
                    data: { term: nit },
                    dataType: "json",
                    success: function(items) {
                        if (items && items.length > 0) {
                            const match = items.find(function(i) {
                                return String(i.value).trim() === String(nit).trim();
                            }) || items[0];
                            if (match) {
                                $('#nombre1').val(match.nombre);
                                $('#nombre2').val(match.nombre);
                            }
                        }
                    }
                });
            }
        });
    });

    // Eventos para Facturar A (nit1)
    $("#nit1").change(function() {
        const nit = $(this).val();
        $("#direccion1").html('<option value="" disabled selected>Seleccione...</option>');
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion1").html(data);
        });
    });

    // Espejo Facturar A → Enviar A al seleccionar del autocomplete
    // Se usa autocompleteselect para tener acceso al nombre antes de que se escriba en el campo
    $("#nit1").on("autocompleteselect", function(event, ui) {
        const nit2Actual = $("#nit2").val();
        // Espejear si Enviar A está vacío o sigue con el NIT que fue auto-llenado anteriormente
        if (!nit2Actual || nit2Actual === nitEspejo) {
            nitEspejo = ui.item.value;
            $("#nit2").val(ui.item.value);
            $("#nombre2").val(ui.item.nombre);
            $("#direccion2").html('<option value="" disabled selected>Seleccione...</option>');
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: ui.item.value }, function(data) {
                $("#direccion2").html(data);
            });
        }
    });

    // Si el usuario cambia nit2 manualmente, detener el espejo
    $("#nit2").on("autocompleteselect", function() {
        nitEspejo = '';
    });

    $("#direccion1").change(function() {
        const direccion = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion }, function(data) {
            data = JSON.parse(data);
            $("#telefono1").val(data.telefono_1);
        });

        // Si Enviar A tiene el mismo NIT que Facturar A, copiar la dirección seleccionada
        if ($("#nit2").val() === $("#nit1").val()) {
            $("#direccion2").val($(this).val());
        }
    });

    // Eventos para Enviar A (nit2)
    $("#nit2").change(function() {
        const nit = $(this).val();
        $("#direccion2").html('<option value="" disabled selected>Seleccione...</option>');
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion2").html(data);
        });
    });

    // Eventos para terceros
    $("#nit3").change(function() {
        const nit = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion3").html(data);
        });
    });

    $("#direccion3").change(function() {
        const direccion = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion }, function(data) {
            data = JSON.parse(data);
            $("#telefono3").val(data.telefono_1);
        });
    });


}

// modo: 'bloqueo' (OS finalizada, no se puede crear) | 'confirmacion' (OS pendiente, pide confirmación)
// onCrear: callback ejecutado al presionar Crear en modo 'confirmacion'
function mostrarModalEstadoOS(numero, resp, modo, onCrear) {
    modo = modo || 'bloqueo';

    var filas = '';
    resp.documentos.forEach(function(doc) {
        var clazz = doc.estado === 'Guardado' ? 'label-success' : 'label-default';
        var badge = '<span class="label ' + clazz + '">' + doc.estado + '</span>';
        filas += '<tr>'
               + '<td>' + doc.tipo + '</td>'
               + '<td>' + doc.numero + '</td>'
               + '<td>' + (doc.fecha || '') + '</td>'
               + '<td>' + badge + '</td>'
               + '</tr>';
    });

    var esFinalizado = resp.status === 'finalizado';
    var msgClass  = esFinalizado ? 'alert-warning' : 'alert-info';
    var msgIcono  = esFinalizado ? 'fa-exclamation-triangle' : 'fa-info-circle';
    var msgTexto  = esFinalizado
        ? 'Esta Orden de Salida <strong>' + numero + '</strong> ya tiene movimientos registrados y se encuentra <strong>completamente procesada</strong>. No es posible crear nuevos documentos.'
        : 'La Orden de Salida <strong>' + numero + '</strong> ya tiene movimientos registrados. Solo se procesarán las cantidades pendientes.';

    $('#os-info-mensaje')
        .removeClass('alert-warning alert-info')
        .addClass(msgClass)
        .html('<i class="fa ' + msgIcono + '"></i> ' + msgTexto);

    $('#os-info-numero').text(numero);
    $('#os-info-pendientes').text(resp.lineas_pendientes);
    $('#os-info-despachado').text(resp.total_despachado);
    $('#os-info-ordenado').text(resp.total_ordenado);

    if (esFinalizado) {
        $('#os-info-pendientes-wrap').hide();
    } else {
        $('#os-info-pendientes-wrap').show();
    }

    $('#os-info-tabla').html(filas);

    // Configurar botones según modo
    if (modo === 'confirmacion') {
        $('#os-btn-entendido').hide();
        $('#os-btn-cancelar').show();
        $('#os-btn-crear').show().off('click').on('click', function() {
            $('#modalEstadoOS').modal('hide');
            if (typeof onCrear === 'function') onCrear();
        });
    } else {
        // 'info' o 'bloqueo': solo botón Entendido
        $('#os-btn-entendido').show();
        $('#os-btn-cancelar').hide();
        $('#os-btn-crear').hide().off('click');
    }

    $('#modalEstadoOS').modal('show');
}

// ─── DEVOLUCIÓN MANUAL ───────────────────────────────────────────────────────

$(document).on('change', '#chk_dev_manual', function() {
    const esManual = $(this).is(':checked');
    const camposNit = ['hr1','txt_nit1','nit1','txt_nombre1','nombre1','txt_direccion1','direccion1',
                       'txt_telefono1','telefono1','hr2','txt_nit2','nit2','txt_nombre2','nombre2',
                       'txt_direccion2','direccion2'];

    // Cambiar visual del label según estado
    if (esManual) {
        $('#lbl_dev_manual').css({
            'border': '2px solid #f0ad4e',
            'background': '#fff8ec',
            'border-radius': '6px'
        });
        $('#col_tipo_doc_orig').hide();
        document.getElementById("txt_numero").style.display = "none";
        document.getElementById("numero").style.display = "none";
        camposNit.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'inline-block';
        });
        $('#div_concepto_dev_manual').show();
        cargarConceptosDevManual();
    } else {
        $('#lbl_dev_manual').css({
            'border': '2px dashed #ccc',
            'background': '#fafafa',
            'border-radius': '6px'
        });
        $('#col_tipo_doc_orig').show();
        document.getElementById("txt_numero").style.display = "inline-block";
        document.getElementById("numero").style.display = "inline-block";
        camposNit.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        $('#div_concepto_dev_manual').hide();
    }
});

function cargarConceptosDevManual() {
    $('#conceptoDevManual').html('<option value="" disabled selected>Cargando...</option>');
    $.ajax({
        url: CONFIG.endpoints.conceptosDevolucion.listar_activos,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            var opts = '<option value="" disabled selected>Seleccione concepto...</option>';
            if (Array.isArray(data) && data.length > 0) {
                $.each(data, function(i, c) {
                    opts += '<option value="' + c.id + '">' + c.nombre + '</option>';
                });
            }
            $('#conceptoDevManual').html(opts);
        },
        error: function() {
            $('#conceptoDevManual').html('<option value="" disabled selected>Error al cargar</option>');
        }
    });
}

// ─── PREVIEW DEVOLUCIÓN ──────────────────────────────────────────────────────
var devPreviewOk = false;
var lineasDevolucionSeleccionadas = null;

function consultarPreviewDevolucion() {
    var numero  = $.trim($('#numero').val());
    var tiporef = $('#tipoDocOrig').val();

    $('#div_preview_devolucion').hide();
    $('#preview_devolucion_content').html('');
    devPreviewOk = false;

    if (!numero || !tiporef) return;

    $('#preview_devolucion_content').html(
        '<div class="alert alert-default" style="padding:6px 10px;font-size:12px">' +
        '<i class="fa fa-spinner fa-spin"></i> Buscando documento...</div>'
    );
    $('#div_preview_devolucion').show();

    $.post(CONFIG.endpoints.salidas.preview_doc_devolucion, { numero: numero, tiporef: tiporef }, function(data) {
        var html = '';
        if (data.status === 'not_found') {
            html = '<div class="alert alert-danger" style="padding:8px 12px;font-size:12px;margin:0">' +
                   '<i class="fa fa-times-circle"></i> <strong>No encontrado:</strong> ' +
                   'No existe un documento <em>' + ($('#tipoDocOrig option:selected').text().trim() || tiporef) + '</em>' +
                   ' con el número <strong>' + numero + '</strong>. Verifique el número ingresado.' +
                   '</div>';
            devPreviewOk = false;
        } else if (data.status === 'found') {
            var estadoLabel = data.exportado === 'S'
                ? '<span class="label label-success">Exportado</span>'
                : '<span class="label label-warning">Pendiente</span>';
            var devLabel = data.tiene_devolucion > 0
                ? '<span class="label label-danger" title="Ya existe una devolución para este documento">Devolución existente</span>'
                : '';
            html = '<div class="alert alert-info" style="padding:8px 12px;font-size:12px;margin:0">' +
                   '<i class="fa fa-check-circle"></i> <strong>Documento encontrado:</strong>&nbsp;' +
                   data.tipo + ' N° <strong>' + data.numero + '</strong> &mdash; ' +
                   data.empresa + ' &mdash; ' +
                   '<strong>' + data.fecha + '</strong> &mdash; ' +
                   estadoLabel + ' ' + devLabel +
                   '</div>';
            devPreviewOk = true;
        } else {
            html = '<div class="alert alert-warning" style="padding:8px 12px;font-size:12px;margin:0">' +
                   '<i class="fa fa-exclamation-triangle"></i> Error al consultar el documento.' +
                   '</div>';
            devPreviewOk = false;
        }
        $('#preview_devolucion_content').html(html);
        $('#div_preview_devolucion').show();
    }, 'json').fail(function() {
        $('#preview_devolucion_content').html(
            '<div class="alert alert-warning" style="padding:8px 12px;font-size:12px;margin:0">' +
            '<i class="fa fa-exclamation-triangle"></i> Error de conexión al consultar.</div>'
        );
        $('#div_preview_devolucion').show();
        devPreviewOk = false;
    });
}

function limpiarPreviewDevolucion() {
    devPreviewOk = false;
    $('#div_preview_devolucion').hide();
    $('#preview_devolucion_content').html('');
}

// FUNCIONES DE GESTIÓN DE DOCUMENTOS
function crearDocumento() {
    const tipo = document.getElementById("idTipo").value;
    const consecutivo = document.getElementById("consecutivo").value;
    const docref = $("#docref").val();
    const textoTipoSel = $("#idTipo option:selected").text().trim();
    const esDev = textoTipoSel.startsWith('Dev');

    if (!validarCampoRequerido(tipo, "Tipo de Documento") ||
        !validarCampoRequerido(consecutivo, "Consecutivo")) {
        return false;
    }

    // Caso Manual
    if (!esDev && docref == "2") {
        const nit1 = $("#nit1").val();
        const dir1 = $("#direccion1").val();
        const nit2 = $("#nit2").val();
        const dir2 = $("#direccion2").val();
        const fechaFactura = $("#fecha_factura_iso").val();
        if (!fechaFactura) {
            swal("Advertencia!", "La fecha 'Facturado el' no es válida", "warning");
            return false;
        }

        if (!validarCampoRequerido(nit1, "NIT Facturar A") ||
            !validarCampoRequerido(dir1, "Dirección Facturar A") ||
            !validarCampoRequerido(nit2, "NIT Enviar A") ||
            !validarCampoRequerido(dir2, "Dirección Enviar A")) {
            return false;
        }

        $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });

        $.ajax({
            url: CONFIG.endpoints.salidas.insert_doc_manual,
            type: "POST",
            data: { idTipo: tipo, nit1: nit1, dir1: dir1, nit2: nit2, dir2: dir2, fecha_factura: fechaFactura },
            dataType: "json",
            success: function(response) {
                $.unblockUI();
                if (response.status === "success") {
                    swal({ title: "Correcto!", text: response.message, type: "success" }, function() {
                        window.location.href = 'index.php?tipo=' + response.tipo + '&consecutivo=' + response.consecutivo;
                    });
                } else {
                    swal("Error!", response.message, "error");
                    $("#btncrear").prop('disabled', false);
                }
            },
            error: function() {
                $.unblockUI();
                swal("Error!", "Ha ocurrido un error al procesar la solicitud.", "error");
                $("#btncrear").prop('disabled', false);
            }
        });

        $("#btncrear").prop('disabled', true);
        return false;
    }

    // Devolución Manual (sin documento de referencia)
    if (esDev && $('#chk_dev_manual').is(':checked')) {
        const nit1 = $('#nit1').val();
        const dir1 = $('#direccion1').val();
        const nit2 = $('#nit2').val();
        const dir2 = $('#direccion2').val();
        const idConcepto     = $('#conceptoDevManual').val();
        const nombreConcepto = $('#conceptoDevManual option:selected').text().trim();

        if (!validarCampoRequerido(nit1,          'NIT Facturar A')          ||
            !validarCampoRequerido(dir1,          'Dirección Facturar A')    ||
            !validarCampoRequerido(nit2,          'NIT Enviar A')            ||
            !validarCampoRequerido(dir2,          'Dirección Enviar A')      ||
            !validarCampoRequerido(idConcepto,    'Concepto de Devolución')) {
            return false;
        }

        $.blockUI({ message: '<h2>Creando devolución manual, por favor espere...</h2>' });
        $.ajax({
            url:         CONFIG.endpoints.salidas.insert_devolucion_manual,
            type:        'POST',
            data:        { idTipo: tipo, nit1: nit1, dir1: dir1, nit2: nit2, dir2: dir2,
                           idConcepto: idConcepto, nombreConcepto: nombreConcepto },
            dataType:    'json',
            success: function(response) {
                $.unblockUI();
                if (response.status === 'success') {
                    swal({ title: 'Devolución creada', text: response.message, type: 'success' }, function() {
                        window.location.href = 'index.php?tipo=' + response.tipo + '&consecutivo=' + response.consecutivo;
                    });
                } else {
                    swal('Error!', response.message, 'error');
                    $('#btncrear').prop('disabled', false);
                }
            },
            error: function() {
                $.unblockUI();
                swal('Error!', 'Ha ocurrido un error al procesar la solicitud.', 'error');
                $('#btncrear').prop('disabled', false);
            }
        });

        $('#btncrear').prop('disabled', true);
        return false;
    }

    // Devolución con documento de referencia
    if (esDev) {
        const tipoDocOrig = $("#tipoDocOrig").val();
        const tipoDocOrigTexto = $("#tipoDocOrig option:selected").text().trim();
        const numeroDev = document.getElementById("numero").value;

        if (!validarCampoRequerido(tipoDocOrig, "Tipo Documento Original") ||
            !validarCampoRequerido(numeroDev, "Número del documento a devolver")) {
            return false;
        }

        // Consultar el documento antes de mostrar el modal de confirmación
        $.post(CONFIG.endpoints.salidas.preview_doc_devolucion,
               { numero: numeroDev, tiporef: tipoDocOrig },
        function(data) {
            var htmlModal, puedeConfirmar;

            if (data.status === 'not_found') {
                swal("Documento no encontrado",
                     "No existe un documento de tipo '" + tipoDocOrigTexto + "' con el número " + numeroDev + ".\n\nVerifique que el número corresponde al traslado, no a la Orden de Salida.",
                     "error");
                return;
            }

            if (data.status === 'found') {
                if (data.exportado !== 'S') {
                    swal('No permitido',
                         'El documento ' + ($('#tipoDocOrig option:selected').text().trim() || tipoDocOrig) +
                         ' N° ' + numeroDev + ' aún no está guardado (exportado).\n\nSolo se pueden generar devoluciones sobre documentos ya exportados.',
                         'error');
                    return;
                }

                var estadoHtml = '<span style="color:#27ae60;font-weight:bold">&#10003; Exportado</span>';

                var advertencia = data.tiene_devolucion > 0
                    ? '<div style="margin-top:10px;padding:7px 10px;background:#fff3cd;border-left:4px solid #f39c12;border-radius:3px;font-size:12px;color:#856404">' +
                      '<strong>&#9888; Advertencia:</strong> Ya existe una devolución registrada para este documento.' +
                      '</div>'
                    : '';

                htmlModal =
                    '<div style="text-align:left;font-size:13px">' +
                    '<table style="width:100%;border-collapse:collapse">' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0;white-space:nowrap">Tipo</td>' +
                        '<td style="font-weight:bold">' + data.tipo + '</td></tr>' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0">Número</td>' +
                        '<td style="font-weight:bold">' + data.numero + '</td></tr>' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0">Empresa</td>' +
                        '<td>' + data.empresa + '</td></tr>' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0">NIT</td>' +
                        '<td>' + data.nit + '</td></tr>' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0">Fecha</td>' +
                        '<td>' + data.fecha + '</td></tr>' +
                    '<tr><td style="color:#888;padding:3px 8px 3px 0">Estado</td>' +
                        '<td>' + estadoHtml + '</td></tr>' +
                    '</table>' +
                    advertencia +
                    '<hr style="margin:10px 0;border-color:#ddd">' +
                    '<p style="font-size:11px;color:#888;margin:0">Esta acción es definitiva e irreversible.</p>' +
                    '</div>';
                puedeConfirmar = true;
            } else {
                // Error de conexión: mostrar confirmación básica de todas formas
                htmlModal =
                    '<div style="text-align:left;font-size:13px">' +
                    '<p>' + tipoDocOrigTexto + ' &nbsp;<strong>N° ' + numeroDev + '</strong></p>' +
                    '<hr style="margin:10px 0;border-color:#ddd">' +
                    '<p style="font-size:11px;color:#888;margin:0">Esta acción es definitiva e irreversible.</p>' +
                    '</div>';
                puedeConfirmar = true;
            }

            swal({
                title: "Confirmar Devolución",
                text: htmlModal,
                html: true,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                closeOnConfirm: true
            }, function(confirmed) {
                if (!confirmed) return;
                document.getElementById("tipoDocRef").value = tipoDocOrig;
                lineasDevolucionSeleccionadas = null;

                // Pre-check: verificar disponibilidad antes de continuar
                $.ajax({
                    url: CONFIG.endpoints.salidas.get_lineas_devolucion,
                    type: 'POST',
                    dataType: 'json',
                    data: { tipo: tipoDocOrig, numero: numeroDev },
                    success: function(preCheck) {
                        var disponiblesPC = [];
                        var totalItemsPC  = 0;

                        if (preCheck.status === 'success' && preCheck.lineas && preCheck.lineas.length > 0) {
                            totalItemsPC = preCheck.lineas.length;
                            $.each(preCheck.lineas, function(i, l) {
                                var cantDisp = parseFloat(l.cantidad_disponible);
                                if (cantDisp > 0) disponiblesPC.push({ seq: l.seq, cantidad: cantDisp });
                            });
                        }

                        // Bloquear si ya no hay nada que devolver
                        if (totalItemsPC > 0 && disponiblesPC.length === 0) {
                            $('#btncrear').prop('disabled', false);
                            setTimeout(function() {
                                swal('Sin disponibilidad',
                                     'Este documento ya fue devuelto en su totalidad.\nNo hay cantidades pendientes de devolución.',
                                     'error');
                            }, 300);
                            return;
                        }

                        var itemsAgotados = totalItemsPC - disponiblesPC.length;
                        var infoAgotados  = itemsAgotados > 0
                            ? '\n(' + itemsAgotados + ' ítem(s) ya devuelto(s) completamente)'
                            : '';

                        if (window.permiteDevolucionParcial) {
                            // setTimeout necesario: SweetAlert v1 no permite abrir un segundo swal
                            // dentro del callback del primero sin que el DOM se limpie primero
                            setTimeout(function() {
                                swal({
                                    title: "Tipo de Devolución",
                                    text: "¿Desea hacer una devolución parcial (seleccionar ítems) o completa (todos los ítems disponibles)?" + infoAgotados,
                                    type: "info",
                                    showCancelButton: true,
                                    confirmButtonColor: "#6f42c1",
                                    confirmButtonText: "Parcial",
                                    cancelButtonText: "Completa",
                                    closeOnConfirm: true,
                                    closeOnCancel: true
                                }, function(esParcial) {
                                    if (esParcial === true) {
                                        abrirModalItemsDevolucion(numeroDev, tipoDocOrig);
                                    } else if (esParcial === false) {
                                        // Completa: usar disponibles ya calculados
                                        lineasDevolucionSeleccionadas = disponiblesPC.length > 0 ? disponiblesPC : null;
                                        abrirModalConceptoDevolucion();
                                    }
                                });
                            }, 300);
                        } else {
                            // Sin permiso parcial: usar disponibles directamente
                            lineasDevolucionSeleccionadas = disponiblesPC.length > 0 ? disponiblesPC : null;
                            abrirModalConceptoDevolucion();
                        }
                    },
                    error: function() {
                        // Si falla el pre-check continuar con flujo normal
                        if (window.permiteDevolucionParcial) {
                            setTimeout(function() {
                                swal({
                                    title: "Tipo de Devolución",
                                    text: "¿Desea hacer una devolución parcial (seleccionar ítems) o completa (todos los ítems)?",
                                    type: "info",
                                    showCancelButton: true,
                                    confirmButtonColor: "#6f42c1",
                                    confirmButtonText: "Parcial",
                                    cancelButtonText: "Completa",
                                    closeOnConfirm: true,
                                    closeOnCancel: true
                                }, function(esParcial) {
                                    if (esParcial === true) {
                                        abrirModalItemsDevolucion(numeroDev, tipoDocOrig);
                                    } else if (esParcial === false) {
                                        lineasDevolucionSeleccionadas = null;
                                        abrirModalConceptoDevolucion();
                                    }
                                });
                            }, 300);
                        } else {
                            abrirModalConceptoDevolucion();
                        }
                    }
                });
            });
        }, 'json').fail(function() {
            // Si falla la consulta, mostrar confirmación básica
            swal({
                title: "Confirmar Devolución",
                text: "Está a punto de generar una devolución total al documento:\n\n" +
                      tipoDocOrigTexto + "  N° " + numeroDev + "\n\n" +
                      "Esta acción es definitiva e irreversible. ¿Desea continuar?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                closeOnConfirm: true
            }, function(confirmed) {
                if (!confirmed) return;
                document.getElementById("tipoDocRef").value = tipoDocOrig;
                abrirModalConceptoDevolucion();
            });
        });

        return false;
    }

    // OS / Traslado
    const numero = document.getElementById("numero").value;
    if (!validarCampoRequerido(numero, "Número")) {
        return false;
    }

    // Validación extra para tipos restringidos (excluir devoluciones)
    if (window.tiposRestringidos.includes(tipo) && !esDev) {
        if (docref != "0") {
            swal("Error!", "Para este tipo de documento la base debe ser una Orden de Salida (OS)", "error");
            return false;
        }
    }

    const formData = new FormData($("#doc_form")[0]);

    // Si es OS, validar estado antes de proceder
    if (docref == "0") {
        $.post(CONFIG.endpoints.salidas.validar_os, { numero: numero }, function(resp) {
            if (resp.status === 'no_existe') {
                // OS no existe, el backend dará el error correspondiente
                ejecutarCreacionOSDoc(formData);
                return;
            }
            if (!resp.documentos || resp.documentos.length === 0) {
                // OS sin documentos previos, flujo normal
                ejecutarCreacionOSDoc(formData);
                return;
            }
            if (resp.status === 'finalizado') {
                // Bloqueado, solo mostrar información
                mostrarModalEstadoOS(numero, resp, 'bloqueo');
                $("#btncrear").prop('disabled', false);
            } else {
                // Pendiente, pedir confirmación desde la modal
                mostrarModalEstadoOS(numero, resp, 'confirmacion', function() {
                    ejecutarCreacionOSDoc(formData);
                });
                $("#btncrear").prop('disabled', false);
            }
        }, 'json').fail(function() {
            // Si falla la validación previa, intentar crear igual
            ejecutarCreacionOSDoc(formData);
        });

        $("#btncrear").prop('disabled', true);
        return false;
    }

    ejecutarCreacionOSDoc(formData);
    return false;
}

function ejecutarCreacionOSDoc(formData) {
    $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });
    $("#btncrear").prop('disabled', true);

    $.ajax({
        url: CONFIG.endpoints.salidas.insert_doc_salida,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(response) {
            $.unblockUI();
            if (response.status === "success") {
                swal({
                    title: "Correcto!",
                    text: response.message,
                    type: "success"
                }, function() {
                    window.location.href = 'index.php?tipo=' + response.tipo + '&consecutivo=' + response.consecutivo;
                });
            } else {
                swal("Error!", response.message, "error");
                $("#btncrear").prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            $.unblockUI();
            swal("Error!", "Ha ocurrido un error al procesar la solicitud. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
            $("#btncrear").prop('disabled', false);
        }
    });
}

$(document).on("change", "#chkLoteManual", function() {
    const manual = $(this).is(':checked');
    $('#lote1').toggle(!manual).prop('disabled', manual);
    $('#lote1_manual').toggle(manual).prop('disabled', !manual);
    if (manual) {
        $('#lote1_manual').val('').focus();
    } else {
        $('#lote1').val('');
    }
});

$('#lot').on('hidden.bs.modal', function() {
    $('#chkLoteManual').prop('checked', false);
    $('#lote1').show().prop('disabled', false).val('');
    $('#lote1_manual').hide().prop('disabled', true).val('');
});

function guardarLote() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const loteManual  = window.permiteLoteManual && $('#chkLoteManual').is(':checked');
    const lote        = loteManual ? $('#lote1_manual').val().trim() : $('#lote1').val();

    if (!lote) {
        swal("Advertencia!", "Debe ingresar un número de lote", "warning");
        return;
    }

    const seqsSeleccionados = [];
    document.querySelectorAll('#tb-doc tbody input[type=checkbox]:checked').forEach(function(cb) {
        const row = cb.closest('tr');
        if (row) seqsSeleccionados.push(row.cells[0].textContent.trim());
    });

    if (seqsSeleccionados.length === 0) {
        swal("Advertencia!", "Debe seleccionar al menos un producto para asignar el lote", "warning");
        return;
    }

    $.post(CONFIG.endpoints.salidas.update_lote_salida,
        { tipo: tipo, numdoc: consecutivo, lote1: lote, seqs: seqsSeleccionados.join(',') },
        function() {
            swal("Correcto!", "Lote asignado a " + seqsSeleccionados.length + " producto(s) seleccionado(s)", "success");
            $("#lot").modal('hide');
            $('#tb-doc').DataTable().ajax.reload();
            $('#lote1').val('');
        }
    );
}

function cargarComboEtapas() {
    $('#etapa_select').html('<option value="">Cargando...</option>');
    $.ajax({
        url: CONFIG.endpoints.etapas.listar_activas,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!Array.isArray(data) || data.length === 0) {
                $('#etapa_select').html('<option value="">-- Sin etapas activas --</option>');
                return;
            }
            var options = '<option value="">-- Seleccione una etapa --</option>';
            $.each(data, function(i, etapa) {
                options += '<option value="' + etapa.id + '" data-nombre="' + etapa.nombre + '">' + etapa.nombre + '</option>';
            });
            $('#etapa_select').html(options);
        },
        error: function(xhr, status, err) {
            $('#etapa_select').html('<option value="">-- Error al cargar --</option>');
            console.error('Error cargando etapas:', status, err, xhr.responseText);
        }
    });
}

function guardarEtapa() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const selected    = $('#etapa_select option:selected');
    const etapaId     = selected.val();
    const etapaNombre = selected.data('nombre');

    if (!etapaId) {
        swal("Advertencia!", "Debe seleccionar una etapa", "warning");
        return;
    }

    $.post(CONFIG.endpoints.salidas.update_notas_etapa,
        { tipo: tipo, numdoc: consecutivo, notas: etapaNombre },
        function(resp) {
            var r = typeof resp === 'string' ? JSON.parse(resp) : resp;
            if (r.status === 'success') {
                $('#notas').val(etapaNombre);
                swal("Correcto!", "Etapa \"" + etapaNombre + "\" asignada correctamente", "success");
                $("#modaletapas").modal('hide');
            } else {
                swal("Error!", r.message || "No se pudo guardar la etapa", "error");
            }
        }
    );
}

function editarProducto() {
    const idproducto = document.getElementById("idproducto").value;
    const cantidad = document.getElementById("cantidad").value;
    const valorUnitario = document.getElementById("Valor_Unitario").value;
    const lote = document.getElementById("lote").value;
    if (!validarCampoRequerido(idproducto, "Código de Producto") ||
        !validarCampoRequerido(cantidad, "Cantidad") ||
        !validarCampoRequerido(valorUnitario, "Valor Unitario") ||
        !validarCampoRequerido(lote, "Lote")) {
        return false;
    }
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(data) {
            if (data.status !== 'success') {
                swal("Cantidad no permitida", data.message || "Error al actualizar el producto.", "warning");
                return;
            }

            $('#modalagregar').modal('hide');

            swal({
                title: "Correcto!",
                text: "Registrado Correctamente",
                type: "success",
                closeOnConfirm: true
            }, function() {
                const tipo = getUrlParameter('tipo');
                const consecutivo = getUrlParameter('consecutivo');

                $('#tb-doc').DataTable().ajax.reload();
                $('#cantidad, #idproducto, #Valor_Unitario, #lote').val('');

                actualizarTodosLosTotales(tipo, consecutivo);
            });
        },
        error: function(xhr, status, error) {
            swal("Error!", "Ha ocurrido un error al actualizar el producto. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
        }
    });
}

function guardarDocumento() {
    if ($("#btnguardar").prop('disabled')) {
      swal("Advertencia!", "No se puede modificar un documento exportado", "warning");
      return false;
    }

    const totalLineas = tabla ? tabla.rows().count() : 0;
    if (totalLineas === 0) {
        swal("Advertencia!", "No se puede guardar un documento sin líneas de detalle. Agregue al menos un producto.", "warning");
        return false;
    }

    // Grupos de producto que corresponden a Dotación/EPP (Calzado, Prendas, Otros Dotación, EPP).
    // Si hay alguno en el detalle y el checkbox "Dotación y EPP" no está marcado, se advierte
    // antes de guardar en vez de guardar silenciosamente.
    const GRUPOS_DOTACION = [14, 10, 37, 12];
    const productosDotacion = [];
    tabla.rows().every(function(){
        const grupo = parseInt(this.data()[14], 10);
        if (GRUPOS_DOTACION.includes(grupo)) {
            productosDotacion.push(this.data()[2]); // nombre del producto
        }
    });

    if (productosDotacion.length > 0 && !$('#dotacion_epp').is(':checked')) {
        swal({
            title: "Productos de Dotación/EPP",
            text: "Hay productos de Dotación/EPP en el detalle y no ha marcado el checkbox \"Dotación y EPP\". ¿Desea marcarlo antes de guardar?",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-warning",
            confirmButtonText: "Sí, marcar",
            cancelButtonText: "No, guardar así",
            closeOnConfirm: true
        }, function(marcar){
            if (marcar) {
                // Marcar el checkbox dispara el flujo existente de selección de concepto
                // (modal #modalConceptoDotacion). Se deja que el usuario lo complete y
                // vuelva a presionar Guardar, en vez de continuar guardando en paralelo.
                $('#dotacion_epp').prop('checked', true).trigger('change');
                swal("Listo", "Selecciona el concepto de Dotación/EPP y luego presiona Guardar de nuevo.", "info");
                return;
            }
            continuarGuardarDocumento();
        });
        return;
    }

    continuarGuardarDocumento();
}

function continuarGuardarDocumento() {
    const sw = document.getElementById("sw").value;

    if (sw == 10 || sw == 2) {

      const nit1 = document.getElementById("nit1").value;
      const direccion1 = document.getElementById("direccion1").value;
      const nit2 = document.getElementById("nit2").value;
      const direccion2 = document.getElementById("direccion2").value;
      const traslfact1Val = document.getElementById("traslfact1").value;

      if (!validarCampoRequerido(nit1, "NIT Facturar A") ||
          !validarCampoRequerido(direccion1, "Dirección Facturar A") ||
          !validarCampoRequerido(nit2, "NIT Enviar A") ||
          !validarCampoRequerido(direccion2, "Dirección Enviar A") ||
          !validarCampoRequerido(traslfact1Val, "Despacho/Dia de Consumo")) {
          return false;
      }

      procesarGuardado(CONFIG.endpoints.salidas.guardar_salida);

    } else if (sw == 9) {
        const nit = document.getElementById("nit3").value;
        const direccion = document.getElementById("direccion3").value;
        const traslfact1 = document.getElementById("traslfact1").value;

        if (!validarCampoRequerido(nit, "NIT/Cédula") ||
            !validarCampoRequerido(direccion, "Dirección") ||
            !validarCampoRequerido(traslfact1, "Despacho/Dia de Consumo")) {
            return false;
        }

        procesarGuardado(CONFIG.endpoints.documento.guardar_entrada);
    } else {
        const traslfact1Otros = document.getElementById("traslfact1").value;
        if (!validarCampoRequerido(traslfact1Otros, "Despacho/Dia de Consumo")) {
            return false;
        }
        procesarGuardado(CONFIG.endpoints.documento.guardar_doc);
    }
}

function procesarGuardado(endpoint) {
    $.blockUI({ message: '<h2>Guardando por favor Espere...</h2>' });

    const formData = new FormData($("#doc_form")[0]);

    // El número/tipo del documento se toman SIEMPRE de la URL (fuente canónica), no del campo
    // visible: para un BORRADOR el consecutivo es negativo y el backend lo usa para renumerar.
    // Esto evita depender del valor mostrado en #numdoc (que puede decir "BORRADOR").
    formData.set('numdoc', getUrlParameter('consecutivo'));
    formData.set('tipo',   getUrlParameter('tipo'));

    $.ajax({
        url: CONFIG.baseUrl + endpoint,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos) {
            console.log(datos);

            // guardar_salida ahora responde JSON (consecutivo diferido). Las otras ramas
            // (guardar_entrada/guardar_doc) siguen respondiendo texto: si no parsea, es flujo heredado.
            var resp = null;
            try { resp = (typeof datos === 'object') ? datos : JSON.parse(datos); } catch (e) { resp = null; }

            if (resp && resp.status === 'error') {
                if (resp.code === 'ya_guardado') {
                    // El borrador ya fue guardado (p. ej. desde otra pestaña): no se duplicó ni se perdió número.
                    swal({ title: "Aviso", text: resp.message, type: "warning" }, function() {
                        window.location.href = 'index.php';
                    });
                } else {
                    swal("Error!", resp.message || "No se pudo guardar el documento.", "error");
                }
                return;
            }

            // Guardado exitoso: marcar para que el listener de 'pagehide' NO intente borrar el borrador
            // al navegar (además, tras renumerar ya es un documento positivo y el endpoint no lo tocaría).
            window.__borradorGuardado = true;

            // ¿El documento era un BORRADOR? (la URL trae el número negativo). Si lo era, el número
            // real viene en resp.consecutivo y hay que navegar a él (la URL negativa ya no existe en BD).
            var fueBorrador = parseInt(getUrlParameter('consecutivo'), 10) < 0;
            var tipoActual  = getUrlParameter('tipo');
            var numeroReal  = (resp && resp.consecutivo) ? resp.consecutivo : getUrlParameter('consecutivo');

            swal({
                title: "Correcto!",
                text: fueBorrador ? ("Documento guardado con el número " + numeroReal) : "Documento Registrado Correctamente",
                type: "success",
                showCancelButton: true,
                confirmButtonText: "Aceptar",
                confirmButtonClass: "btn-success",
                cancelButtonText: "Imprimir",
                cancelButtonClass: "btn-info"
            }, function(isConfirm) {
                if (isConfirm) {
                    window.location.href = 'index.php';
                } else if (fueBorrador) {
                    // Recargar en el documento ya numerado y disparar la impresión al cargar.
                    window.location.href = 'index.php?tipo=' + tipoActual + '&consecutivo=' + numeroReal + '&print=1';
                } else {
                    imprimirDocumento();
                    setTimeout(function() {
                        window.location.reload();
                    }, 800);
                }
            });
        },
        complete: function() {
            $.unblockUI();
        },
        error: function(xhr, status, error) {
            $.unblockUI();
            swal("Error!", "Ha ocurrido un error al guardar el documento. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
        }
    });
}

// FUNCIONES DE INTERFAZ
function showInp(){
    const val = document.getElementById("docref").value;

    // Campo Número — oculto solo para Manual (value=2)
    const displayNumero = val == "2" ? "none" : "inline-block";
    document.getElementById("txt_numero").style.display = displayNumero;
    document.getElementById("numero").style.display = displayNumero;

    // Campo Facturado el — solo visible para Manual (value=2)
    document.getElementById("div_fecha_factura").style.display = val == "2" ? "inline-block" : "none";

    // Secciones Facturar A / Enviar A — visibles en fase 1 solo para Manual
    const manualFields = [
        "hr1", "txt_nit1", "nit1", "txt_nombre1", "nombre1", "txt_direccion1", "direccion1",
        "txt_telefono1", "telefono1",
        "hr2", "txt_nit2", "nit2", "txt_nombre2", "nombre2", "txt_direccion2", "direccion2"
    ];
    const displayManual = val == "2" ? "inline-block" : "none";
    manualFields.forEach(function(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = displayManual;
    });

}

function configurarInterfazParaDocumentoExistente(data) {
    const elementosOcultar = [
        "idTipo", "consecutivo", "numero", "docref", "fecha",
        "txt_idTipo", "txt_consecutivo", "txt_numero", "txt_docref", "txt_fecha",
        "div_fecha_factura"
    ];
    
    elementosOcultar.forEach(id => {
        document.getElementById(id).style.display = "none";
        if (id === "idTipo" || id === "numero" || id === "docref") {
            document.getElementById(id).removeAttribute("required");
        }
    });

    document.getElementById("btncrear").style.display = "none";

    const elementosMostrar = [
        "txt_tipodoc", "txt_numdoc", "txt_fecha1", "txt_pedido1", "txt_nroOcTercero",
        "txt_traslfact1", "txt_fecha_factura2",
        "tipodoc", "numdoc", "fecha1", "pedido1", "nroOcTercero", "traslfact1", "fecha_factura2",
        "div_dotacion", "div_transportador", "div_vehiculo",
        "btnlot", "btnetapas", "btneliminarsel", "btnguardar", "btnprint"
    ];
    
    elementosMostrar.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });

    // Configuración específica según tipo de documento
    if(data.Tipo_Docto_Base_2 == 9){
        mostrarCamposEntrada();
    } else {
        mostrarCamposTraslado();
        if (data.Tipo_Docto_Base_2 == '2') {
            document.getElementById("btnagregar").style.display = "inline-block";
            document.getElementById("btnexcel").style.display   = "inline-block";
        }
    }

    if (data.tipo == '155') {
        document.getElementById("btnflete").style.display = "inline-block";
    }

    // Configurar estado de exportación
    configurarEstadoExportado(data.exportado);
}

function mostrarCamposEntrada() {
    const camposEntrada = [
        "nit1", "nombre1", "direccion1", "telefono1",
        "hr1", "hr2", "hr3",
        "txt_nit3", "txt_nombre3", "txt_direccion3", "txt_telefono3",
        "nit3", "nombre3", "direccion3", "telefono3", "btnlot", "btneliminarsel"
    ];
    
    camposEntrada.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });
    
    document.getElementById("traslfact1").disabled = false;
}

function mostrarCamposTraslado() {
    const camposTraslado = [
        "nit1", "nombre1", "direccion1", "telefono1",
        "nit2", "nombre2", "direccion2",
        "txt_nit1", "txt_nombre1", "txt_direccion1", "txt_telefono1",
        "txt_nit2", "txt_nombre2", "txt_direccion2",
        "hr1", "hr2", "hr3", "btnlot", "btneliminarsel"
    ];

    camposTraslado.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });

    document.getElementById("traslfact1").disabled = false;
}

function configurarEstadoExportado(exportado) {
    if(exportado === 'S') {
        $("#btnguardar, #btnlot, #btnetapas, #btneliminarsel").prop('disabled', true).addClass('btn-disabled');
        $("#btnguardar").html('Documento Exportado')
                       .attr('title', 'No se puede modificar un documento exportado');

        const camposEditables = ['nit1', 'nombre1', 'direccion1', 'telefono1',
                                 'nit2', 'nombre2', 'direccion2',
                                 'nit3', 'nombre3', 'direccion3', 'telefono3',
                                 'traslfact1', 'dotacion_epp', 'notas', 'fecha_factura2'];
        const el_btnagregar = document.getElementById("btnagregar");
        if (el_btnagregar) el_btnagregar.disabled = true;
        const el_btnexcel = document.getElementById("btnexcel");
        if (el_btnexcel) el_btnexcel.disabled = true;
        const el_btnflete = document.getElementById("btnflete");
        if (el_btnflete) el_btnflete.disabled = true;
        camposEditables.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.disabled = true;
        });

        $('#btnprint').show();
        $('#btnreiniciar').hide();
        window.documentoExportado = true;
    } else {
        window.documentoExportado = false;
        $('#btnprint').hide();
        $("#btnguardar").prop('disabled', false)
                       .removeClass('btn-disabled')
                       .html('Guardar')
                       .removeAttr('title');

        // Mostrar botón Reiniciar solo para documentos basados en OS (sw=10)
        if ($('#sw').val() == '10') {
            $('#btnreiniciar').show();
        } else {
            $('#btnreiniciar').hide();
        }
    }
}

// FUNCIONES DE GESTIÓN DE DATOS
function actualizarTodosLosTotales(tipo, consecutivo) {
    console.log('Actualizando todos los totales...');
    
    const operaciones = [
        {
            op: 'total_entrada', 
            elemento: '#total',
            propiedad: 'total',
            descripcion: 'Subtotal del documento'
        },
        {
            op: 'totales', 
            callback: function(data) {
                try {
                    const dataTotales = JSON.parse(data);
                    $('#valorTotal').text(dataTotales.valorTotal || '0');
                    $('#totalImpuesto').text(dataTotales.totalImpuesto || '0');
                    $('#totalDescuento').text(dataTotales.totalDescuento || '0');
                    console.log('Totales generales actualizados');
                } catch(e) {
                    console.error('Error parseando totales:', data);
                }
            },
            descripcion: 'Totales generales (IVA, descuentos, total)'
        },
        {
            op: 'total_cantidad', 
            elemento: '#totalCantidad',
            propiedad: 'totalCantidad',
            descripcion: 'Cantidad total de items'
        }
    ];
    
    let completadas = 0;
    const totalOperaciones = operaciones.length;
    
    operaciones.forEach(function(operacion) {
        $.post(`${CONFIG.baseUrl}documento.php?op=${operacion.op}`, 
               {tipo: tipo, consecutivo: consecutivo}, 
               function(data) {
                   completadas++;
                   
                   try {
                       if (operacion.callback) {
                           operacion.callback(data);
                       } else {
                           const resultado = JSON.parse(data);
                           const valor = resultado[operacion.propiedad] || '0';
                           $(operacion.elemento).text(valor);
                       }
                       console.log(`✅ ${operacion.descripcion} actualizado`);
                   } catch(e) {
                       console.error(`❌ Error en ${operacion.op}:`, data);
                   }
                   
                   if (completadas === totalOperaciones) {
                       console.log('🎉 Todos los totales han sido actualizados');
                   }
               }
        ).fail(function(xhr, status, error) {
            console.error(`❌ Error en petición ${operacion.op}:`, error);
            completadas++;
        });
    });
}

function listardetalle(tipo, consecutivo){
    // Cargar datos del documento
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.mostrar_entrada, 
           { tipo, consecutivo }, 
           function (data) {
        data = JSON.parse(data);
        console.log(data);

        // Un documento anulado se bloquea exactamente igual que uno ya exportado:
        // se reutiliza toda la lógica existente basada en data.exportado en vez de duplicarla.
        if (data.anulado === 'S') {
            data.exportado = 'S';
            $('#avisoAnulado').show();
        } else {
            $('#avisoAnulado').hide();
        }

        // Llenar campos del formulario
        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#prefijo_doc').val(data.Prefijo || '');
        // Consecutivo diferido: un Numero_documento negativo es un BORRADOR (aún sin numerar).
        // IMPORTANTE: #numdoc SIEMPRE conserva el número real (negativo para borrador) porque ese
        // campo se envía en el formulario al Guardar y lo leen otras acciones. Para el usuario se
        // muestra "BORRADOR" en un campo aparte (#numdoc_display), no tocando el valor de #numdoc.
        var esBorrador = parseInt(data.Numero_documento, 10) < 0;
        $('#numdoc').val(data.Numero_documento);
        $('#avisoBorrador').toggle(esBorrador);
        $('#pedido1').val(data.Numero_Docto_Base_2);
        $('#nroOcTercero').val(data.NroOcTercero || '');
        $('#traslfact1').val(data.Numero_Docto_Base);
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#telefono1').val(data.telefono_1);
        if(data.nit_Cedula) {
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: data.nit_Cedula }, function(html) {
                $('#direccion1').html(html);
                $('#direccion1 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(data.codigo_direccion).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        var nit2Usar  = data.nit_Cedula_2  || data.nit_Cedula;
        var nom2Usar  = data.nombre2       || data.Nombre_Cliente;
        var dir2Usar  = data.codigo_direccion_2 || data.codigo_direccion;
        $('#nit2').val(nit2Usar);
        $('#nombre2').val(nom2Usar);
        if(nit2Usar) {
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: nit2Usar }, function(html) {
                $('#direccion2').html(html);
                $('#direccion2 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(dir2Usar).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        // Cuando Tipo_Docto_Base_2==9 se muestran nit3/nombre3/direccion3 (mostrarCamposEntrada),
        // así que también hay que llenar esos campos con los mismos datos de "Enviar A".
        if (String(data.Tipo_Docto_Base_2) === '9' && nit2Usar) {
            $('#nit3').val(nit2Usar);
            $('#nombre3').val(nom2Usar);
            $('#telefono3').val(data.telefono_1 || '');
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: nit2Usar }, function(html) {
                $('#direccion3').html(html);
                $('#direccion3 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(dir2Usar).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        $('#notas').html(data.notas);

        // Motivo de devolución (solo devoluciones tienen RespuestaCorrectaDian)
        if (data.RespuestaCorrectaDian) {
            $('#motivo_devolucion').text(data.RespuestaCorrectaDian);
            $('#div_motivo_devolucion').show();
        } else {
            $('#div_motivo_devolucion').hide();
        }

        $('#sw').val(data.Tipo_Docto_Base_2);
        $('#nombre_bodega').val(data.NombreBodega || '');
        $('#nombre_vendedor').val(data.NombreVendedor || '');
        $('#ciudad_doc').val(data.ciudad || '');
        $('#dotacion_epp').prop('checked', data.IdVendedor == 12);
        if (data.IdTransportador) $('#idTransportador').val(data.IdTransportador);
        if (data.IdVehiculo)      $('#idVehiculo').val(data.IdVehiculo);
        if (data.Fecha_Hora_Factura) {
            var fechaDoc = new Date(data.Fecha_Hora_Factura + "T00:00:00");
            if (!isNaN(fechaDoc)) {
                $('#fecha_factura2').datepicker("setDate", fechaDoc);
                var mm2 = String(fechaDoc.getMonth() + 1).padStart(2, '0');
                var dd2 = String(fechaDoc.getDate()).padStart(2, '0');
                $('#fecha_factura2_iso').val(fechaDoc.getFullYear() + "-" + mm2 + "-" + dd2);
            }
        }

        if(data !== null){
            configurarInterfazParaDocumentoExistente(data);
        }

        // Para un BORRADOR se oculta el número real (#numdoc) y se muestra el rótulo "BORRADOR"
        // (#numdoc_display). Se hace DESPUÉS de configurar la interfaz, que muestra #numdoc.
        if (esBorrador) {
            $('#numdoc').hide();
            $('#numdoc_display').show();
        } else {
            $('#numdoc_display').hide();
        }

    });

    actualizarTodosLosTotales(tipo, consecutivo);

    //Limpiar cualquier estado de edición pendiente al cargar la tabla
    if (editingRow) {
        cancelarEdicionNativa();
    }
        
    // Configurar DataTable
    tabla = $('#tb-doc').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "paging": false,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": false,
        lengthChange: false,
        colReorder: false,
        buttons: [],
        "ajax": {
            url: CONFIG.baseUrl + CONFIG.endpoints.documento.listar_detalle_entrada,
            type: "post",
            dataType: "json",
            data: { tipo, consecutivo },
            error: function(consecutivo) {
                console.log(consecutivo.responseText);
            }
        },
        "columnDefs": [
            { "targets": [14], "visible": false, "searchable": false } // Grupo de producto (oculta, usada para validar Dotación/EPP)
        ],
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 70,
        "autoWidth": false,
        "createdRow": function(row, data, dataIndex) {
            $('td', row).eq(4).addClass('editable-cell'); // Cantidad
            $('td', row).eq(8).addClass('editable-cell'); // Lote  (desplazado por %IVA en col 6)
            $('td', row).eq(10).addClass('editable-cell'); // Nota (desplazado por %IVA en col 6)
        },
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();

    // Agregar eventos de edición
    agregarEventosEdicionInline();

    setTimeout(() => {
        agregarEventosEdicionInline();
    }, 500);
}

// FUNCIONES DE EDICIÓN INLINE (MANTENIENDO LA VERSIÓN NATIVA ORIGINAL)
function agregarEventosEdicionInline() {
    console.log('Agregando eventos de edición inline (nativo)...');
    
    // SOLUCIÓN: Usar event delegation para todos los eventos
    const table = document.getElementById('tb-doc');
    
    // Limpiar eventos anteriores para evitar duplicados
    table.removeEventListener('dblclick', manejarDobleClic);
    table.addEventListener('dblclick', manejarDobleClic);
    
    // Eventos para acciones con delegation
    document.removeEventListener('click', manejarAccionesTabla);
    document.addEventListener('click', manejarAccionesTabla);
}

// Función separada para manejar doble clic
function manejarDobleClic(e) {
    if (window.documentoExportado) return;
    if (e.target.classList.contains('editable-cell')) {
        iniciarEdicionNativa(e.target);
    }
}

// Función centralizada para manejar acciones en la tabla Documentos_Lin
function manejarAccionesTabla(e) {
    const btnEliminar = e.target.closest('.btn-eliminar');
    const btnDuplicar = e.target.closest('.btn-duplicar');
    const btnActualizarProducto = e.target.closest('.btn-actualizar-producto');

    if (btnEliminar) {
        e.preventDefault();
        const row = btnEliminar.closest('tr');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();

        console.log('🗑️ Eliminando producto:', producto);
        eliminar(tipo, consecutivo, producto, seq);
    } else if (btnDuplicar) {
        e.preventDefault();
        const row = btnDuplicar.closest('tr');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();

        console.log('📋 Duplicando producto:', producto);
        duplicarLinea(tipo, consecutivo, producto, seq);
    } else if (btnActualizarProducto && !btnActualizarProducto.disabled) {
        e.preventDefault();
        actualizarProductoLinea(btnActualizarProducto.closest('tr'));
    }
}

// Refresca %IVA y Valor de una línea tomando los datos actuales del producto en el
// catálogo (en vez de dejar que el usuario los digite a mano). Útil cuando el producto
// se corrige después (p. ej. le agregan IVA) y las líneas ya agregadas quedaron desactualizadas.
function actualizarProductoLinea(row) {
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const seq = row.cells[0].textContent.trim();
    const producto = row.cells[1].textContent.trim();
    const nombreProducto = row.cells[2].textContent.trim();

    swal({
        title: "¿Actualizar producto?",
        text: "Se sobreescribirán el %IVA y el Valor de \"" + nombreProducto + "\" con los datos actuales del producto en el catálogo.",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-primary",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (!isConfirm) return;

        $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.actualizar_producto_linea, {
            tipo: tipo, consecutivo: consecutivo, producto: producto, seq: seq
        }, function(response) {
            if (response.status !== 'success') {
                swal("No se pudo actualizar", response.message || "Error desconocido", "error");
                return;
            }
            row.cells[6].textContent = response.porcentajeImpuesto;
            row.cells[7].textContent = response.valorUnitario;
            swal({
                title: "¡Actualizado!",
                text: "El %IVA y el Valor se actualizaron desde el producto.",
                type: "success",
                confirmButtonClass: "btn-success"
            });
            actualizarTodosLosTotales(tipo, consecutivo);
        }, 'json').fail(function() {
            swal("Error", "Error de conexión. No se pudo actualizar el producto.", "error");
        });
    });
}

function iniciarEdicionNativa(cell) {
    console.log('🎬 Iniciando edición (nativo)...');
    
    // Cancelar edición anterior si existe
    if (editingRow) {
        console.log('🔄 Cancelando edición anterior...');
        cancelarEdicionNativa();
    }

    const row = cell.closest('tr');
    const cellIndex = cell.cellIndex;
    const currentValue = cell.textContent.trim();
    
    editingRow = row;
    originalData = {
        value: currentValue,
        cell: cell,
        index: cellIndex
    };

    row.classList.add('editing');
    
    let input;
    switch(cellIndex) {
        case 4: // Cantidad
        case 5: // % Desc
        case 7: // Valor  (col 6 = %IVA readonly, no editable)
            input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.step = cellIndex === 7 ? '0.01' : '1';
            break;
        case 9: // Fecha Vence
            input = document.createElement('input');
            input.type = 'date';
            var partsDate = currentValue.split('/');
            if (partsDate.length === 3) {
                input.value = partsDate[2] + '-' + partsDate[1] + '-' + partsDate[0];
            } else {
                input.value = currentValue;
            }
            break;
        case 10: // Nota - permitir vacío
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.placeholder = "Nota opcional...";
            break;
        default: // Lote y otros campos
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
    }
    
    input.className = 'edit-input';

    // 🔥 GUARDAR el valor original en un data attribute por si necesitamos restaurarlo
    cell.dataset.originalValue = currentValue;
    
    cell.innerHTML = '';
    cell.appendChild(input);
    input.focus();
    input.select();

    const actionsCell = row.cells[12];
    actionsCell.innerHTML = `
        <div class="edit-actions">
            <button type="button" class="btn btn-success btn-sm btn-action btn-guardar" title="Guardar cambios">
                <i class="fa fa-check"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-action btn-cancelar" title="Cancelar edición">
                <i class="fa fa-times"></i>
            </button>
            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                <i class="fa fa-copy"></i>
            </button>
            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `;

    // ASIGNAR EVENTOS DIRECTAMENTE a los botones de esta fila específica
    actionsCell.querySelector('.btn-guardar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('💾 Click en guardar');
        guardarEdicionNativa(row);
    });

    actionsCell.querySelector('.btn-cancelar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('❌ Click en cancelar');
        cancelarEdicionNativa();
    });

    actionsCell.querySelector('.btn-duplicar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('📋 Click en duplicar');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();
        duplicarLinea(tipo, consecutivo, producto, seq);
    });

    actionsCell.querySelector('.btn-eliminar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🗑️ Click en eliminar');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();
        eliminar(tipo, consecutivo, producto, seq);
    });

    // Enter para guardar, ESC para cancelar
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            console.log('↵ Enter presionado - Guardando');
            guardarEdicionNativa(row);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            console.log('⎋ ESC presionado - Cancelando');
            cancelarEdicionNativa();
        }
    });

    console.log('✅ Edición iniciada correctamente (nativo)');
}

function guardarEdicionNativa(row) {
    console.log('💾 Iniciando guardado (nativo)...');
    
    if (!editingRow || !originalData.cell) {
        console.error('❌ No hay edición activa');
        return;
    }

    const cell = originalData.cell;
    const input = cell.querySelector('.edit-input');
    const newValue = input.value.trim();
    const cellIndex = originalData.index;

    // Validación básica - EXCLUIR el campo de notas (índice 9)
    if (!newValue && cellIndex !== 9) { // El campo 9 es "Nota"
        swal("Advertencia!", "El campo no puede estar vacío", "warning");
        input.focus();
        return;
    }

    row.classList.add('saving');

    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const producto = row.cells[1].textContent.trim();
    const seq = row.cells[0].textContent.trim();

    console.log('📤 Enviando datos:', { tipo, consecutivo, producto, seq, campo: cellIndex, valor: newValue });

    const formData = new FormData();
    formData.append('tipo', tipo);
    formData.append('consecutivo', consecutivo);
    formData.append('producto', producto);
    formData.append('seq', seq); 
    
    switch(cellIndex) {
        case 4:  formData.append('cantidad',     newValue); break;
        case 5:  formData.append('descuento',    newValue); break;
        // case 6: %IVA — readonly, no se edita
        case 7:  formData.append('valor',        newValue); break;
        case 8:  formData.append('lote',         newValue); break;
        case 9:  formData.append('fecha_vence',  newValue); break;
        case 10: formData.append('nota',         newValue); break;
        case 11: formData.append('unidades',     newValue); break;
    }

    fetch(CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('📥 Respuesta del servidor:', data);

        if (data.status === 'success' || data.status === 'warning') {
            var displayValue = newValue || '';
            // Para fecha vence (col 9): el input type=date retorna YYYY-MM-DD, mostrar como DD/MM/YYYY
            if (cellIndex === 9 && newValue && newValue.indexOf('-') !== -1) {
                var dp = newValue.split('-');
                if (dp.length === 3) displayValue = dp[2] + '/' + dp[1] + '/' + dp[0];
            }
            cell.textContent = displayValue;

            row.classList.remove('editing', 'saving');
            limpiarEstadoEdicionNativa();

            if (data.status === 'warning') {
                swal("Tolerancia aplicada", data.message, "info");
            } else {
                mostrarFeedbackExitoso();
            }
            actualizarTodosLosTotales(tipo, consecutivo);

            console.log('✅ Cambio guardado exitosamente');

        } else {
            row.classList.remove('saving');
            swal("Cantidad no permitida", data.message || "No se pudo guardar el cambio en el servidor.", "warning");
            console.error("Error del servidor:", data);
            cancelarEdicionNativa();
        }
    })
    .catch(error => {
        row.classList.remove('saving');
        swal("Error!", "No se pudo guardar el cambio: " + error, "error");
        console.error("Error en la petición:", error);
        cancelarEdicionNativa();
    });
}

function limpiarEstadoEdicionNativa() {
    console.log('🧹 Limpiando estado de edición (nativo)...');
    
    if (editingRow) {
        const actionsCell = editingRow.cells[12];
        
        // 🔥 IMPORTANTE: Limpiar eventos antes de modificar el HTML
        const botonesAnteriores = actionsCell.querySelectorAll('button');
        botonesAnteriores.forEach(btn => {
            const clonBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(clonBtn, btn);
        });
        
        // Restaurar botones básicos
        actionsCell.innerHTML = `
            <div class="edit-actions">
                <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                    <i class="fa fa-copy"></i>
                </button>
                <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        `;
        
        const celdasEditables = editingRow.querySelectorAll('.editable-cell');
        celdasEditables.forEach(celda => {
            if (celda.querySelector('input')) {
                const input = celda.querySelector('input');
                celda.textContent = celda.dataset.originalValue || input.value || '';
            }
        });
        
        editingRow.classList.remove('editing', 'saving', 'saved');
    }
    
    editingRow = null;
    originalData = {};
    
    console.log('✅ Estado de edición limpiado (nativo)');
}

// función para reconectar eventos después de guardar
function reconectarEventosDespuesDeGuardar() {
    setTimeout(() => {
        console.log('🔄 Reconectando eventos después de guardar...');
        agregarEventosEdicionInline();
    }, 100);
}


function cancelarEdicionNativa() {
    if (editingRow && originalData.cell) {
        originalData.cell.textContent = originalData.value;
        editingRow.classList.remove('editing', 'saving');
        limpiarEstadoEdicionNativa();
    }
}

// FUNCIONES DE GESTIÓN DE REGISTROS
function editar(tipo, consecutivo, producto){
    window.modoAgregar = false;
    $('#mdltitulo').html('Editar Registro');   

    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.mostrarXproducto, 
           {tipo, consecutivo, producto}, 
           function (data) {
        data = JSON.parse(data);
        
        $('#idproducto').val(data.IdProducto);
        $('#Producto').val(data.Producto);
        $('#cantidad').val(data.Cantidad_Facturada);
        $('#Valor_Unitario').val(data.Valor_Unitario);
        $('#lote').val(data.Numero_Lote);
        $('#fecha_vence').val(data.Fecha_Vence);
        
        console.log("producto", data);
    });   

    $('#modalagregar').modal('show');
}

function duplicarLinea(tipo, consecutivo, producto, seq) {
    console.log('🔄 Duplicando línea:', { tipo, consecutivo, producto, seq });
    
    swal({
        title: "¿Duplicar línea?",
        text: "Esta acción creará una copia idéntica de esta línea de producto.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#17a2b8",
        confirmButtonText: "Sí, duplicar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false,
        showLoaderOnConfirm: true
    }, function(isConfirm) {
        if (isConfirm) {
            console.log('📤 Enviando petición de duplicación...');
            
            // 🔥 IMPORTANTE: Cancelar cualquier edición activa antes de duplicar
            if (editingRow) {
                cancelarEdicionNativa();
            }
            
            $.blockUI({ message: '<h2>Duplicando línea...</h2>' });
            
            $.ajax({
                url: CONFIG.baseUrl + CONFIG.endpoints.documento.duplicar_linea,
                type: "POST",
                data: {
                    tipo: tipo,
                    consecutivo: consecutivo,
                    producto: producto,
                    seq: seq
                },
                dataType: "json",
                success: function(response) {
                    console.log('📥 Respuesta del servidor:', response);
                    $.unblockUI();
                    
                    if (response.status === "success") {
                        swal({
                            title: "¡Correcto!",
                            text: response.message,
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // 🔥 CORRECIÓN: Recargar la tabla y reconectar eventos
                        $('#tb-doc').DataTable().ajax.reload(function() {
                            // Después de recargar, reconectar eventos
                            setTimeout(() => {
                                agregarEventosEdicionInline();
                            }, 300);
                        });
                        
                        actualizarTodosLosTotales(tipo, consecutivo);
                        
                    } else {
                        swal("Error!", response.message || "No se pudo duplicar la línea", "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error en petición:', error);
                    console.error('❌ Status:', status);
                    console.error('❌ Response:', xhr.responseText);
                    
                    $.unblockUI();
                    swal("Error!", "Error al duplicar la línea: " + error, "error");
                }
            });
        }
    });
}

function eliminar(tipo, consecutivo, producto, seq){
    const row = Array.from(document.querySelectorAll('#tb-doc tbody tr')).find(tr => {
        return tr.cells[0].textContent.trim() === seq && 
               tr.cells[1].textContent.trim() === producto;
    });
    
    let nombreProducto = '';
    let cantidad = '';
    let lote = '';
    
    if (row) {
        nombreProducto = row.cells[2].textContent.trim();
        cantidad = row.cells[4].textContent.trim();
        lote = row.cells[8].textContent.trim();
    }
    
    const mensaje = `
        <div style="text-align: left; padding: 10px;">
            <p><strong>Código:</strong> ${producto}</p>
            <p><strong>Producto:</strong> ${nombreProducto}</p>
            <p><strong>Cantidad:</strong> ${cantidad}</p>
            <p><strong>Lote:</strong> ${lote}</p>
            <p><strong>Seq:</strong> ${seq}</p>
            <br>
            <p style="color: #d9534f; font-weight: bold;">⚠️ Esta acción no se puede deshacer</p>
        </div>
    `;
    
    swal({
        title: "¿Eliminar este producto?",
        text: mensaje,
        html: true,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false
    },
    function(isConfirm) {
        if (isConfirm) {
            swal({
                title: "Eliminando...",
                text: "Por favor espere",
                type: "info",
                showConfirmButton: false
            });
            
            $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.eliminar, {
                tipo: tipo, 
                consecutivo: consecutivo, 
                producto: producto, 
                seq: seq
            }, function (data) {
                console.log(data);
                
                if (data.trim() === "success" || data.includes("success") || data.includes("Eliminado correctamente")) {
                    swal({
                        title: "¡Eliminado!",
                        text: "El producto ha sido eliminado correctamente",
                        type: "success",
                        confirmButtonClass: "btn-success"
                    }, function(){
                        // Recargar la tabla y totales después de confirmar
                        $('#tb-doc').DataTable().ajax.reload(null, false);
                        actualizarTodosLosTotales(tipo, consecutivo);
                        
                        // Si la tabla queda vacía, forzar un redibujado completo
                        setTimeout(() => {
                            if ($('#tb-doc').DataTable().rows().count() === 0) {
                                $('#tb-doc').DataTable().draw();
                            }
                        }, 500);
                    });
                } else {
                    swal({
                        title: "Error",
                        text: "No se pudo eliminar el producto. Intente nuevamente.",
                        type: "error",
                        confirmButtonClass: "btn-danger"
                    });
                }
            }).fail(function() {
                swal({
                    title: "Error",
                    text: "Error de conexión. No se pudo eliminar el producto.",
                    type: "error",
                    confirmButtonClass: "btn-danger"
                });
            });
        }
    });
}

function eliminarSeleccionados() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    const seleccionados = [];
    document.querySelectorAll('#tb-doc tbody input[type=checkbox]:checked').forEach(function(cb) {
        const row = cb.closest('tr');
        if (!row) return;
        seleccionados.push({
            seq:      row.cells[0].textContent.trim(),
            producto: row.cells[1].textContent.trim(),
            nombre:   row.cells[2].textContent.trim(),
            cantidad: row.cells[4].textContent.trim()
        });
    });

    if (seleccionados.length === 0) {
        swal("Advertencia!", "Debe seleccionar al menos un producto para eliminar", "warning");
        return;
    }

    let lista = seleccionados.map(function(p) {
        return '<li><b>' + p.producto + '</b> – ' + p.nombre + ' (Cant: ' + p.cantidad + ')</li>';
    }).join('');

    const mensaje = '<div style="text-align:left;padding:8px;">' +
        '<p>Se eliminarán <b>' + seleccionados.length + '</b> producto(s):</p>' +
        '<ul style="max-height:160px;overflow-y:auto;padding-left:18px;">' + lista + '</ul>' +
        '<br><p style="color:#d9534f;font-weight:bold;">&#9888; Esta acción no se puede deshacer</p>' +
        '</div>';

    swal({
        title: "¿Eliminar productos seleccionados?",
        text: mensaje,
        html: true,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (!isConfirm) return;

        swal({ title: "Eliminando...", text: "Por favor espere", type: "info", showConfirmButton: false });

        const seqs     = seleccionados.map(function(p) { return p.seq; }).join(',');
        const productos = seleccionados.map(function(p) { return p.producto; }).join(',');

        $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.eliminar_masivo, {
            tipo: tipo,
            consecutivo: consecutivo,
            seqs: seqs,
            productos: productos
        }, function(data) {
            if (data.trim() === 'success') {
                swal({
                    title: "¡Eliminados!",
                    text: seleccionados.length + " producto(s) eliminado(s) correctamente",
                    type: "success",
                    confirmButtonClass: "btn-success"
                }, function() {
                    $('#tb-doc').DataTable().ajax.reload(null, false);
                    actualizarTodosLosTotales(tipo, consecutivo);
                });
            } else {
                swal("Error", "No se pudieron eliminar los productos. Respuesta: " + data, "error");
            }
        }).fail(function() {
            swal("Error", "Error de conexión. No se pudieron eliminar los productos.", "error");
        });
    });
}

// EVENT HANDLERS
$(document).on("click", "#btncrear", function(event) {
    event.preventDefault();
    crearDocumento();
    return false;
});

$(document).on("click", "#btnlote", function(){
    guardarLote();
});

$(document).on("click", "#btnguardaretapa", function(){
    guardarEtapa();
});

function prepararModalAgregar() {
    window.modoAgregar = true;
    document.getElementById("idproducto").removeAttribute("readonly");
    document.getElementById("idproducto").value = '';
    document.getElementById("nombre_producto").value = '';
    document.getElementById("cantidad").value = '';
    document.getElementById("Valor_Unitario").value = '';
    document.getElementById("porcentaje_iva").value = '';
    document.getElementById("porcentaje_impuesto").value = '0';
    document.getElementById("lote").value = '';
    document.querySelector("#modalagregar .modal-title").textContent = "Agregar Producto";
    document.getElementById("btneditar").textContent = "Agregar";
}

function cargarInfoProducto() {
    const idProducto = document.getElementById("idproducto").value;
    if (!idProducto) return;

    const tipo       = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    // Nit del cliente (Facturar A para manuales/OS, nit3 para consumos)
    const nit1El = document.getElementById("nit1");
    const nit3El = document.getElementById("nit3");
    let nit = (nit1El && nit1El.value.trim()) ? nit1El.value.trim()
            : (nit3El ? nit3El.value.trim() : '');

    // Dirección (puede venir como "12,nit" desde el select)
    const dir1El = document.getElementById("direccion1");
    const dir3El = document.getElementById("direccion3");
    let dir = '';
    if (dir1El && dir1El.value) dir = dir1El.value;
    else if (dir3El && dir3El.value) dir = dir3El.value;
    if (dir.indexOf(',') !== -1) dir = dir.split(',')[0];

    const payload = { idProducto, tipo, numdoc: consecutivo, nit, direccion: dir };
    console.log('[cargarInfoProducto] Enviando payload:', payload);

    $.ajax({
        url: CONFIG.endpoints.salidas.get_info_producto,
        type: "POST",
        data: payload,
        dataType: "json",
        success: function(response) {
            console.log('[cargarInfoProducto] Respuesta del servidor:', response);
            if (response.status === "success") {
                document.getElementById("nombre_producto").value  = response.nombre;
                document.getElementById("Valor_Unitario").value   = response.precio;
                document.getElementById("porcentaje_iva").value   = response.porcentaje_impuesto + '%';
                document.getElementById("porcentaje_impuesto").value = response.porcentaje_impuesto;
                console.log('[cargarInfoProducto] idLista usado:', response.idLista,
                            '| precio:', response.precio,
                            '| IVA:', response.porcentaje_impuesto + '%');
            } else {
                console.warn('[cargarInfoProducto] Error del servidor:', response.message);
                document.getElementById("nombre_producto").value  = '';
                document.getElementById("Valor_Unitario").value   = '';
                document.getElementById("porcentaje_iva").value   = '';
                document.getElementById("porcentaje_impuesto").value = '0';
                swal("Advertencia!", response.message, "warning");
            }
        },
        error: function(xhr, status, errorThrown) {
            console.error('[cargarInfoProducto] Error HTTP:', status, errorThrown);
            console.error('[cargarInfoProducto] Respuesta cruda:', xhr.responseText);
            document.getElementById("nombre_producto").value  = '';
            document.getElementById("Valor_Unitario").value   = '';
            document.getElementById("porcentaje_iva").value   = '';
            document.getElementById("porcentaje_impuesto").value = '0';
            swal("Error!", "Error al consultar el producto. Revisa la consola (F12) para más detalles.", "error");
        }
    });
}

function guardarModalProducto() {
    if (window.modoAgregar) {
        agregarProductoManual();
    } else {
        editarProducto();
    }
}

function agregarProductoManual() {
    const tipo              = getUrlParameter('tipo');
    const consecutivo       = getUrlParameter('consecutivo');
    const idProducto        = document.getElementById("idproducto").value;
    const cantidad          = document.getElementById("cantidad").value;
    const valorUnitario     = document.getElementById("Valor_Unitario").value || 0;
    const lote              = document.getElementById("lote").value || '0';
    const porcentajeImpuesto = parseFloat(document.getElementById("porcentaje_impuesto").value) || 0;
    const hoy = new Date();
    const fechaVence = hoy.getFullYear() + '-' + String(hoy.getMonth()+1).padStart(2,'0') + '-' + String(hoy.getDate()).padStart(2,'0');

    if (!validarCampoRequerido(idProducto, "Código de Producto") ||
        !validarCampoRequerido(cantidad, "Cantidad")) {
        return false;
    }

    const payload = { tipo, numdoc: consecutivo, idProducto, cantidad,
                      valorUnitario, lote, fechaVence, porcentajeImpuesto };
    console.log('[agregarProductoManual] Enviando payload:', payload);

    $.ajax({
        url: CONFIG.endpoints.salidas.agregar_linea_manual,
        type: "POST",
        data: payload,
        dataType: "json",
        success: function(response) {
            console.log('[agregarProductoManual] Respuesta:', response);
            if (response.status === "success") {
                $('#modalagregar').modal('hide');
                $('#tb-doc').DataTable().ajax.reload();
                actualizarTodosLosTotales(tipo, consecutivo);
                window.modoAgregar = false;
            } else {
                swal("Error!", response.message, "error");
            }
        },
        error: function(xhr, status, errorThrown) {
            console.error('[agregarProductoManual] Error HTTP:', status, errorThrown);
            console.error('[agregarProductoManual] Respuesta cruda:', xhr.responseText);
            swal("Error!", "Ha ocurrido un error al agregar el producto.", "error");
        }
    });
}

// Manejo de eventos de teclado en el modal de agregar producto para agilizar el ingreso
$(document).on("keydown", "#idproducto", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        if (window.modoAgregar) {
            cargarInfoProducto();
        }
        $("#cantidad").focus();
    }
});

$(document).on("keydown", "#cantidad", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $("#lote").focus();
    }
});

$(document).on("keydown", "#lote", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $("#btneditar").click();
    }
});

$(document).on("blur", "#idproducto", function() {
    if (window.modoAgregar) {
        cargarInfoProducto();
    }
});

// Auto-enfocar el primer campo al abrir el modal para agilizar el ingreso
$('#modalagregar').on('shown.bs.modal', function () {
    if (window.modoAgregar) {
        $('#idproducto').focus();
    } else {
        $('#cantidad').focus();
    }
});

$(document).on("click", "#btneditar", function(event) {
    event.preventDefault();
    guardarModalProducto();
});

$(document).on("click", "#btnguardar", function() {
    guardarDocumento();
});

$(document).on("click", "#btnflete", function() {
    $('#flete_cantidad').val('');
    $('#flete_valor').val('');
    $('#modalFlete').modal('show');
    setTimeout(function() { $('#flete_cantidad').focus(); }, 400);
});

$('#modalFlete').on('shown.bs.modal', function() {
    $('#flete_cantidad').focus();
});

$(document).on("click", "#btnConfirmarFlete", function() {
    var tipo        = getUrlParameter('tipo');
    var consecutivo = getUrlParameter('consecutivo');
    var cantidad    = parseFloat($('#flete_cantidad').val());
    var valor       = parseFloat($('#flete_valor').val());

    if (!cantidad || cantidad <= 0) {
        swal("Atención", "Ingrese una cantidad válida.", "warning");
        $('#flete_cantidad').focus();
        return;
    }
    if (isNaN(valor) || valor < 0) {
        swal("Atención", "Ingrese un valor unitario válido.", "warning");
        $('#flete_valor').focus();
        return;
    }

    $.ajax({
        url: CONFIG.endpoints.salidas.agregar_linea_manual,
        type: "POST",
        data: {
            tipo:               tipo,
            numdoc:             consecutivo,
            idProducto:         1810,
            cantidad:           cantidad,
            valorUnitario:      valor,
            lote:               '0',
            fechaVence:         new Date().toISOString().split('T')[0],
            porcentajeImpuesto: 0
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $('#modalFlete').modal('hide');
                $('#tb-doc').DataTable().ajax.reload();
                actualizarTodosLosTotales(tipo, consecutivo);
            } else {
                swal("Error!", response.message, "error");
            }
        },
        error: function() {
            swal("Error!", "No se pudo agregar el flete.", "error");
        }
    });
});

$(document).on("click", "#btnreiniciar", function() {
    var tipo        = getUrlParameter('tipo');
    var consecutivo = getUrlParameter('consecutivo');
    swal({
        title: "¿Reiniciar documento?",
        text: "Esta acción eliminará todos los productos del documento y los volverá a cargar desde la Orden de Salida con las cantidades pendientes actuales. El consecutivo no cambiará.\n\n¿Desea continuar?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, reiniciar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }, function(confirm) {
        if (!confirm) return;
        enviarReinicioSalida(tipo, consecutivo, false);
    });
});

// El backend responde status "confirmar" cuando el documento ya había sido guardado y
// alguien lo desmarcó: en ese caso el detalle actual pudo haberse impreso, así que se
// pide un segundo sí explícito antes de destruirlo.
function enviarReinicioSalida(tipo, consecutivo, confirmado) {
    $.ajax({
        url: CONFIG.endpoints.salidas.reiniciar_doc_desde_os,
        type: "POST",
        data: { tipo: tipo, numdoc: consecutivo, confirmado: confirmado ? '1' : '0' },
        dataType: "json",
        success: function(data) {
            if (data.status === "success") {
                // Recarga interna tras reiniciar: el borrador debe sobrevivir. Antes se
                // usaba window.location.reload() a secas y el listener de 'pagehide'
                // descartaba el borrador recién regenerado, dejando la pantalla apuntando
                // a un documento que ya no existía.
                setTimeout(recargarConservandoBorrador, 400);
            } else if (data.status === "confirmar") {
                swal({
                    title: "Documento ya impreso",
                    text: data.message + "\n\n¿Reiniciar de todas formas?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, reiniciar igual",
                    cancelButtonText: "No, cancelar",
                    confirmButtonColor: "#d33",
                    closeOnConfirm: true
                }, function(seguro) {
                    if (seguro) enviarReinicioSalida(tipo, consecutivo, true);
                });
            } else {
                swal("Error", data.message, "error");
            }
        },
        error: function(xhr, status, err) {
            console.log("reiniciar error:", xhr.responseText, status, err);
            swal("Error", "No se pudo conectar con el servidor.", "error");
        }
    });
}

function mostrarNotificacion(tipo, mensaje) {
    var color = tipo === 'success' ? 'alert-success' : 'alert-danger';
    var $n = $('<div>')
        .addClass('alert ' + color)
        .css({ position: 'fixed', top: '20px', right: '20px', zIndex: 99999,
               minWidth: '280px', boxShadow: '0 2px 8px rgba(0,0,0,.3)', padding: '12px 18px' })
        .html(mensaje)
        .appendTo('body');
    setTimeout(function() { $n.fadeOut(400, function() { $(this).remove(); }); }, 3500);
}

$(document).on("click", "#btnprint", imprimirDocumento);

function imprimirDocumento() {
    var tipo        = getUrlParameter('tipo');
    var consecutivo = getUrlParameter('consecutivo');

    if (!tipo || !consecutivo) {
        swal("Advertencia!", "No hay documento abierto para imprimir.", "warning");
        return;
    }

    var tipodoc    = $('#tipodoc').val() || '';
    var numdoc     = $('#numdoc').val() || '';
    var prefijoDoc = ($('#prefijo_doc').val() || '').trim();
    var numdocConPrefijo = prefijoDoc ? (prefijoDoc + '-' + numdoc) : numdoc;
    var fecha1     = $('#fecha1').val() || '';
    var pedido1    = $('#pedido1').val() || '';
    var nroOcTercero = $('#nroOcTercero').val() || '';
    var traslfact1 = $('#traslfact1').val() || '';
    var nit1       = $('#nit1').val() || '';
    var nombre1    = $('#nombre1').val() || '';
    var nit2       = $('#nit2').val() || '';
    var nombre2    = $('#nombre2').val() || '';
    var nit3       = $('#nit3').val() || '';
    var nombre3    = $('#nombre3').val() || '';
    var notas      = $('#notas').val() || '';
    var dir1text      = $('#direccion1 option:selected').text() || '';
    var dir2text      = $('#direccion2 option:selected').text() || '';
    var nombreBodega   = $('#nombre_bodega').val()  || '';
    var nombreVendedor = $('#nombre_vendedor').val() || '';
    var ciudadDoc      = $('#ciudad_doc').val()      || '';
    var transportadorText = $('#idTransportador option:selected').text() || '';
    var placaText          = $('#idVehiculo option:selected').text() || '';
    if (transportadorText.indexOf('Seleccione') !== -1) transportadorText = '';
    if (placaText.indexOf('Seleccione') !== -1) placaText = '';

    var totalCantidad = $('#totalCantidad').text().trim() || '0';

    var nombreDe  = nombre1 || nombre3 || '';
    var nitDe     = nit1    || nit3    || '';
    var dirDe     = dir1text;
    var nombreEnv = nombre2;
    var dirEnv    = dir2text;

    var filas = [];
    $('#tb-doc tbody tr').each(function() {
        var c = this.cells;
        if (c.length >= 12) {
            filas.push({
                seq:      c[0].textContent.trim(),
                codigo:   c[1].textContent.trim(),
                nombre:   c[2].textContent.trim(),
                umedida:  c[3].textContent.trim(),
                cantidad: c[4].textContent.trim(),
                lote:     c[8].textContent.trim()
            });
        }
    });

    var filasHtml = filas.map(function(f) {
        return '<tr>' +
            '<td style="text-align:center">'        + f.seq      + '</td>' +
            '<td style="text-align:center"><b>'     + f.codigo   + '</b></td>' +
            '<td>'                                  + f.nombre   + '</td>' +
            '<td style="text-align:center">'        + f.umedida  + '</td>' +
            '<td style="text-align:center">'        + f.cantidad + '</td>' +
            '<td style="text-align:center">'        + f.lote     + '</td>' +
            '</tr>';
    }).join('') || '<tr><td colspan="6" style="text-align:center">Sin registros</td></tr>';

    var seccionNotas = notas
        ? '<div style="margin-bottom:12px">' +
          '<label style="font-weight:bold;display:block;margin-bottom:4px">NOTAS:</label>' +
          '<div style="border:1px solid #ccc;border-radius:4px;padding:8px;min-height:40px">' + notas + '</div>' +
          '</div>'
        : '';

    var logoMap = {
        '805018495': 'logo-empresa.png',
        '900184187': 'logo-empresa-agroinversiones.png',
        '901123996': 'logo-empresa-cjpork.png',
        '900805477': 'logo-empresa-frigopork.png',
        '900714090': 'logo-empresa-grupork.png',
        '900328449': 'logo-empresa-impagro.png',
        '900062893': 'logo-empresa-integraciones.png',
        '900798244': 'logo-empresa-valleverde.png'
    };
    var baseUrl  = window.location.href.split('/view/')[0] + '/public/logo-empresas/';
    var logoFile = logoMap[nitDe.trim()] || 'logo-empresa.png';
    var logoUrl  = baseUrl + logoFile;

    var htmlContent = '<!DOCTYPE html><html><head>' +
        '<meta charset="UTF-8">' +
        '<title>Movimiento de Inventario # ' + numdocConPrefijo + '</title>' +
        '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>' +
        '<style>' +
        'body{font-family:Arial,sans-serif;font-size:10px;margin:14px;color:#222}' +
        '.cabecera{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:10px;border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:10px}' +
        '.logo-empresa img{max-height:60px;max-width:120px;object-fit:contain}' +
        '.info-doc h2{margin:0 0 4px;font-size:13px;text-transform:uppercase;font-weight:bold;letter-spacing:1px}' +
        '.info-doc p{margin:1px 0;font-size:10px}' +
        '.barcode{text-align:center}' +
        '#doc-barcode{display:block;max-width:260px;height:auto}' +
        '.barcode-num{font-size:13px;font-weight:bold;letter-spacing:2px;display:block;margin-top:1px}' +
        '.seccion{border:1px solid #bbb;border-radius:3px;padding:8px;margin-bottom:8px}' +
        '.grid2col{display:grid;grid-template-columns:1fr 1fr;gap:12px}' +
        '.campo{margin-bottom:4px}' +
        '.campo label{display:block;font-weight:bold;font-size:9px;color:#666;margin-bottom:1px;text-transform:uppercase}' +
        '.campo span{display:block;border-bottom:1px solid #ddd;padding:1px 0;font-size:10px;min-height:13px}' +
        'table{width:100%;border-collapse:collapse;margin-bottom:8px}' +
        'thead tr{background:#e9e9e9}' +
        'th,td{border:1px solid #bbb;padding:3px 5px;font-size:9px}' +
        'th{text-align:center;font-weight:bold}' +
        '.total-cant{text-align:right;font-weight:bold;font-size:10px;margin-bottom:10px}' +
        '.epp-recibe{margin:16px 0 10px}' +
        '.epp-recibe-titulo{font-size:11px;font-weight:bold;text-transform:uppercase;margin:0 0 6px}' +
        '.epp-firma-linea{border-top:1px solid #333;width:60%;margin:30px 0 6px}' +
        '.epp-recibe-dato{font-size:10px;margin:2px 0}' +
        '.epp-legal{margin-top:12px;font-size:8.5px;color:#333;line-height:1.5}' +
        '.epp-legal h4{font-size:9px;font-weight:bold;margin:8px 0 2px;text-transform:uppercase}' +
        '.epp-legal p{margin:0 0 4px;text-align:justify}' +
        '.firmas-seccion{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:36px;padding-top:10px}' +
        '.firma-bloque{text-align:center}' +
        '.firma-bloque-titulo{font-size:11px;font-weight:bold;text-transform:uppercase;margin:0 0 40px}' +
        '.firma-linea{border-top:1px solid #333;width:80%;margin:0 auto 6px}' +
        '.firma-etiqueta{font-size:9px;color:#555;margin:3px 0 0}' +
        '@media print{body{margin:6px}}' +
        '</style></head><body>' +

        (($('#dotacion_epp').prop('checked')) ?
        '<div style="text-align:center;margin-bottom:6px">' +
        '<span style="font-size:10px;font-weight:bold;color:#555;letter-spacing:1px;border:1px solid #bbb;border-radius:3px;padding:2px 8px">FO-INV-003</span>' +
        '</div>' : '') +

        '<div class="cabecera">' +
        '<div class="logo-empresa"><img src="' + logoUrl + '" alt="Logo empresa"></div>' +
        '<div class="info-doc">' +
        ($('#dotacion_epp').prop('checked')
            ? '<h2>Entrega Dotación y EPP</h2>'
            : '<h2>Movimiento de Inventario</h2>') +
        '<p><b>NRO:</b> ' + numdocConPrefijo + '</p>' +
        '<p><b>Fecha Documento:</b> ' + fecha1 + '</p>' +
        '<p><b>Tipo Movimiento:</b> ' + tipodoc + '</p>' +
        '</div>' +
        '<div class="barcode"><svg id="doc-barcode"></svg><span class="barcode-num"># ' + numdocConPrefijo + '</span></div>' +
        '</div>' +

        '<div class="seccion">' +
        '<div class="grid2col">' +

        '<div>' +
        '<div class="campo"><label>De</label><span>' + nombreDe + '</span></div>' +
        '<div class="campo"><label>Dirección</label><span>' + dirDe + '</span></div>' +
        '<div class="campo"><label>Enviar a</label><span>' + nombreEnv + '</span></div>' +
        '<div class="campo"><label>Dirección</label><span>' + dirEnv + '</span></div>' +
        '<div class="campo"><label>Ciudad</label><span>' + ciudadDoc + '</span></div>' +
        '<div class="campo"><label>Nit</label><span>' + nitDe + '</span></div>' +
        '</div>' +

        '<div>' +
        '<div class="campo"><label>Orden / Consumo</label><span>' + pedido1 + '</span></div>' +
        '<div class="campo"><label>Orden N°</label><span>' + nroOcTercero + '</span></div>' +
        '<div class="campo"><label>Factura</label><span>' + traslfact1 + '</span></div>' +
        '<div class="campo"><label>Bodega</label><span>' + nombreBodega + '</span></div>' +
        '<div class="campo"><label>Lote</label><span></span></div>' +
        '<div class="campo"><label>Grupo</label><span>' + nombreVendedor + '</span></div>' +
        '<div class="campo"><label>Transportador</label><span>' + transportadorText + '</span></div>' +
        '<div class="campo"><label>Placa</label><span>' + placaText + '</span></div>' +
        '</div>' +

        '</div></div>' +

        '<table><thead><tr>' +
        '<th>Seq</th><th>Código</th><th>Nombre Producto</th><th>U. Medida</th><th>Cantidad</th><th>Lote</th>' +
        '</tr></thead><tbody>' + filasHtml + '</tbody></table>' +

        '<div class="total-cant">Total Cantidad: ' + totalCantidad + '</div>' +

        seccionNotas +
        ((!$('#dotacion_epp').prop('checked')) ?
        '<div class="firmas-seccion">' +
        '<div class="firma-bloque">' +
        '<p class="firma-bloque-titulo">Entrega</p>' +
        '<div class="firma-linea"></div>' +
        '<p class="firma-etiqueta">Nombre legible</p>' +
        '</div>' +
        '<div class="firma-bloque">' +
        '<p class="firma-bloque-titulo">Recibe</p>' +
        '<div class="firma-linea"></div>' +
        '<p class="firma-etiqueta">Nombre legible</p>' +
        '</div>' +
        (tipo === '155' ?
        '<div class="firma-bloque" style="margin-top:30px">' +
        '<p class="firma-bloque-titulo">Firma Conductor</p>' +
        '<div class="firma-linea"></div>' +
        '<p class="firma-etiqueta">Nombre legible</p>' +
        '</div>' +
        '<div class="firma-bloque" style="margin-top:30px">' +
        '<p class="firma-bloque-titulo">Firma Báscula</p>' +
        '<div class="firma-linea"></div>' +
        '<p class="firma-etiqueta">Nombre legible</p>' +
        '</div>'
        : '') +
        '</div>'
        : '') +
        (($('#dotacion_epp').prop('checked')) ?
        '<div class="epp-recibe">' +
        '<p class="epp-recibe-titulo">Recibe</p>' +
        '<div class="epp-firma-linea"></div>' +
        '<p class="epp-recibe-dato"><b>Nit/Cédula:</b> ' + (nit2 || nit1 || '') + '</p>' +
        '<p class="epp-recibe-dato"><b>Nombre:</b> ' + (nombre2 || nombre1 || '') + '</p>' +
        '</div>' +

        '<div class="epp-legal">' +

        '<h4>Objeto</h4>' +
        '<p>Mediante el presente documento se formaliza la entrega de los elementos de dotación y de protección personal (EPP) requeridos para garantizar la ' +
        'seguridad, salud y bienestar del trabajador durante el desarrollo de sus funciones dentro de la empresa, en cumplimiento ' +
        'de la legislación laboral vigente en Colombia, especialmente lo dispuesto en el artículo 230 del Código Sustantivo del Trabajo.</p>' +

        '<h4>Estado de los Elementos Entregados</h4>' +
        '<p>Todos los elementos entregados al trabajador se encuentran en óptimas condiciones de uso, en buen estado físico y de funcionamiento.</p>' +

        '<h4>Obligaciones del Trabajador</h4>' +
        '<p>El trabajador declara haber recibido la dotación en cumplimiento de lo establecido por la ley, en las fechas determinadas por la empresa, y se ' +
        'compromete a usarla conforme a lo dispuesto en el decálogo de vestuario. Así mismo, se obliga a portarla únicamente en los días estipulados, con ' +
        'los zapatos y accesorios permitidos por la empresa, sin realizar modificaciones, enmendaduras, reducciones, tejidos, bordados u otros cambios ' +
        'que alteren su diseño o presentación original.</p>' +
        '<p>El trabajador también se compromete a hacer uso adecuado y permanente de los elementos de protección personal (EPP) en todas las actividades ' +
        'que lo requieran, y a reportar de manera inmediata cualquier daño o necesidad de reposición a su jefe inmediato. Además, reconoce su obligación ' +
        'de recibir los EPP y la dotación asignada a su cargo y firmar la constancia de entrega correspondiente.</p>' +

        '<h4>Responsabilidades del Empleador</h4>' +
        '<p>La empresa se compromete a suministrar de forma oportuna todos los elementos de protección personal y la dotación necesarios, con base en un ' +
        'análisis técnico y de costo-beneficio, orientado a salvaguardar la integridad del trabajador y garantizar condiciones ' +
        'laborales seguras, de acuerdo con la normatividad vigente en materia de Seguridad y Salud en el Trabajo.</p>' +

        '</div>'
        : '') +

        '<script>' +
        'window.onload = function() {' +
        '    JsBarcode("#doc-barcode", "' + numdocConPrefijo + '", {' +
        '        format: "CODE39",' +
        '        width: 2.2,' +
        '        height: 55,' +
        '        displayValue: false,' +
        '        margin: 10' +
        '    });' +
        '    setTimeout(function() { window.print(); }, 500);' +
        '};' +
        '<\/script>' +
        '</body></html>';

    var newWindow = window.open('', '_blank');
    newWindow.document.write(htmlContent);
    newWindow.document.close();
}

init();

// ─── CARGA MASIVA EXCEL (validar → confirmar) ─────────────────────────────────

let excelEstadoModalSalidas      = 'inicial'; // 'inicial' | 'validado'
let excelValidosPendientesSalidas = [];

function resetModalExcel() {
    document.getElementById('archivoExcel').value = '';
    document.getElementById('excelResultados').style.display = 'none';
    document.getElementById('tbExcelBody').innerHTML = '';
    document.getElementById('excelResumen').innerHTML = '';

    excelEstadoModalSalidas = 'inicial';
    excelValidosPendientesSalidas = [];

    // Restaurar botones al estado inicial
    var btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = false;
    btnProcesar.style.display = 'inline-block';
    btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';

    document.getElementById('btnNuevoArchivo').style.display = 'none';

    var btnCerrar = document.getElementById('btnCerrarExcel');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.classList.remove('btn-success');
    btnCerrar.classList.add('btn-secondary');
}

$('#modalexcel').on('hidden.bs.modal', function() {
    resetModalExcel();
});

function pintarResumenExcelSalidas(ok, warning, error) {
    const resumenClass = error > 0 ? (ok > 0 || warning > 0 ? 'alert-warning' : 'alert-danger') : (warning > 0 ? 'alert-warning' : 'alert-success');
    document.getElementById('excelResumen').innerHTML =
        `<div class="alert ${resumenClass} py-2 mb-0">
            <strong>Resultado:</strong>
            <span class="badge badge-success ml-2">${ok} válidos</span>
            <span class="badge badge-warning ml-1">${warning} con advertencia</span>
            <span class="badge badge-danger ml-1">${error} con error</span>
        </div>`;
}

function formatearCantidadExcel(cantidad) {
    const n = parseFloat(cantidad);
    return isNaN(n) ? cantidad : n.toFixed(3);
}

function pintarResultadosExcelSalidas(resultados) {
    const tbody = document.getElementById('tbExcelBody');
    tbody.innerHTML = '';
    (resultados || []).forEach(function(r) {
        let statusBadge, rowClass;
        if (r.status === 'ok') {
            statusBadge = '<span class="badge badge-success">OK</span>';
            rowClass = 'table-success';
        } else if (r.status === 'warning') {
            statusBadge = '<span class="badge badge-warning">Advertencia</span>';
            rowClass = 'table-warning';
        } else {
            statusBadge = '<span class="badge badge-danger">Error</span>';
            rowClass = 'table-danger';
        }
        const tr = document.createElement('tr');
        tr.className = rowClass;
        tr.innerHTML = `<td>${r.fila}</td><td>${r.idProducto}</td><td>${formatearCantidadExcel(r.cantidad)}</td><td>${r.lote || '-'}</td><td>${statusBadge}</td><td>${r.mensaje}</td>`;
        tbody.appendChild(tr);
    });
    document.getElementById('excelResultados').style.display = 'block';
}

function obtenerNitDireccionActual() {
    const nit       = (document.getElementById('nit1')       || document.getElementById('nit3'))?.value || '';
    const direccion = (document.getElementById('direccion1') || document.getElementById('direccion3'))?.value || '';
    const dirLimpia = direccion.indexOf(',') !== -1 ? direccion.split(',')[0] : direccion;
    return { nit, direccion: dirLimpia };
}

// El botón único del modal cambia de rol según el estado: primero valida, luego procesa.
function cargarExcelMasivo() {
    if (excelEstadoModalSalidas === 'validado') {
        confirmarExcelSalidas();
    } else {
        validarExcelSalidas();
    }
}

function validarExcelSalidas() {
    const fileInput = document.getElementById('archivoExcel');

    if (!fileInput.files || fileInput.files.length === 0) {
        swal("Advertencia!", "Seleccione un archivo Excel (.xlsx) primero.", "warning");
        return;
    }

    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        swal("Advertencia!", "Solo se aceptan archivos con extensión .xlsx", "warning");
        return;
    }

    const tipo       = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const { nit, direccion } = obtenerNitDireccionActual();

    const formData = new FormData();
    formData.append('archivo',   file);
    formData.append('tipo',      tipo);
    formData.append('numdoc',    consecutivo);
    formData.append('nit',       nit);
    formData.append('direccion', direccion);

    const btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Validando...';

    $.ajax({
        url:         CONFIG.endpoints.salidas.validar_excel_salidas,
        type:        'POST',
        data:        formData,
        processData: false,
        contentType: false,
        dataType:    'json',
        success: function(response) {
            btnProcesar.disabled = false;

            if (response.status === 'error') {
                btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
                swal("Error!", response.message, "error");
                return;
            }

            const ok      = response.ok      || 0;
            const warning = response.warning || 0;
            const error   = response.error   || 0;
            pintarResumenExcelSalidas(ok, warning, error);
            pintarResultadosExcelSalidas(response.resultados);
            document.getElementById('excelAvisoNoGuardado').style.display = 'block';

            excelEstadoModalSalidas = 'validado';
            excelValidosPendientesSalidas = response.validos || [];

            document.getElementById('btnNuevoArchivo').style.display = 'inline-block';

            const totalValidos = ok + warning;
            if (totalValidos > 0) {
                btnProcesar.innerHTML = '<i class="fa fa-check"></i> Procesar ' + totalValidos + ' registro(s) válido(s)';
                btnProcesar.disabled = false;
            } else {
                btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
                btnProcesar.disabled = true;
            }
        },
        error: function(xhr, status, errorThrown) {
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
            console.error('[validarExcelSalidas] Error HTTP:', status, errorThrown, xhr.responseText);
            swal("Error!", "Error de comunicación con el servidor.", "error");
        }
    });
}

function confirmarExcelSalidas() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const { nit, direccion } = obtenerNitDireccionActual();

    if (!excelValidosPendientesSalidas.length) {
        swal("Advertencia!", "No hay registros válidos para procesar.", "warning");
        return;
    }

    const btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Procesando...';

    $.ajax({
        url:  CONFIG.endpoints.salidas.confirmar_excel_salidas,
        type: 'POST',
        data: {
            tipo:      tipo,
            numdoc:    consecutivo,
            nit:       nit,
            direccion: direccion,
            validos:   JSON.stringify(excelValidosPendientesSalidas)
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'error') {
                btnProcesar.disabled = false;
                swal("Error!", response.message, "error");
                return;
            }

            const ok      = response.ok      || 0;
            const warning = response.warning || 0;
            const error   = response.error   || 0;
            pintarResumenExcelSalidas(ok, warning, error);
            pintarResultadosExcelSalidas(response.resultados);
            document.getElementById('excelAvisoNoGuardado').style.display = 'none';

            excelEstadoModalSalidas = 'inicial';
            excelValidosPendientesSalidas = [];

            btnProcesar.style.display = 'none';
            var btnCerrar = document.getElementById('btnCerrarExcel');
            btnCerrar.innerHTML = '<i class="fa fa-check"></i> Listo';
            btnCerrar.classList.remove('btn-secondary');
            btnCerrar.classList.add('btn-success');

            if (ok > 0 || warning > 0) {
                listardetalle(tipo, consecutivo);
                actualizarTodosLosTotales(tipo, consecutivo);
            }
        },
        error: function(xhr, status, errorThrown) {
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-check"></i> Procesar registros válidos';
            console.error('[confirmarExcelSalidas] Error HTTP:', status, errorThrown, xhr.responseText);
            swal("Error!", "Error de comunicación con el servidor.", "error");
        }
    });
}

// ─── MODAL CONCEPTO DEVOLUCIÓN ────────────────────────────────────────────────

function abrirModalConceptoDevolucion() {
    // Limpiar estado previo
    $('#selectConceptoDevolucion').html('<option value="">Cargando conceptos...</option>');
    $('#divSinConceptos').hide();
    $('#divSelectConcepto').show();
    $('#btnConfirmarConcepto').prop('disabled', false);

    // Cargar conceptos activos desde el backend
    $.ajax({
        url:      CONFIG.endpoints.conceptosDevolucion.listar_activos,
        type:     'GET',
        dataType: 'json',
        success: function(data) {
            if (!Array.isArray(data) || data.length === 0) {
                $('#selectConceptoDevolucion').html('');
                $('#divSelectConcepto').hide();
                $('#divSinConceptos').show();
                $('#btnConfirmarConcepto').prop('disabled', true);
            } else {
                var opts = '<option value="">-- Seleccione un concepto --</option>';
                $.each(data, function(i, concepto) {
                    opts += '<option value="' + concepto.id + '">' + concepto.nombre + '</option>';
                });
                $('#selectConceptoDevolucion').html(opts);
            }
        },
        error: function() {
            $('#selectConceptoDevolucion').html('');
            $('#divSelectConcepto').hide();
            $('#divSinConceptos').show().find('i').after(' Error al cargar los conceptos. Intente de nuevo.');
            $('#btnConfirmarConcepto').prop('disabled', true);
        }
    });

    $('#modalConceptoDevolucion').modal('show');
}

// Botón Cancelar dentro del modal de concepto
$(document).on('click', '#btnCancelarConcepto', function() {
    $('#modalConceptoDevolucion').modal('hide');
    $("#btncrear").prop('disabled', false);
});

// Botón Confirmar dentro del modal de concepto
$(document).on('click', '#btnConfirmarConcepto', function() {
    var idConcepto     = $('#selectConceptoDevolucion').val();
    var nombreConcepto = $('#selectConceptoDevolucion option:selected').text().trim();

    if (!idConcepto || idConcepto === '') {
        swal("Advertencia!", "Debe seleccionar un concepto de devolución para continuar.", "warning");
        return;
    }

    $('#modalConceptoDevolucion').modal('hide');

    $.blockUI({ message: '<h2>Generando devolución, por favor espere...</h2>' });

    const formDataDev = new FormData($("#doc_form")[0]);
    formDataDev.set('docref', '3'); // Forzar ruta de devolución independiente del estado del DOM
    formDataDev.append('idConceptoDevolucion',     idConcepto);
    formDataDev.append('nombreConceptoDevolucion', nombreConcepto);
    if (lineasDevolucionSeleccionadas !== null) {
        formDataDev.append('lineasDevolucion', JSON.stringify(lineasDevolucionSeleccionadas));
    }

    $.ajax({
        url:         CONFIG.endpoints.salidas.insert_doc_salida,
        type:        'POST',
        data:        formDataDev,
        contentType: false,
        processData: false,
        dataType:    'json',
        success: function(response) {
            $.unblockUI();
            if (response.status === 'success') {
                swal({ title: 'Devolución Registrada', text: response.message, type: 'success' }, function() {
                    window.location.href = 'index.php?tipo=' + response.tipo + '&consecutivo=' + response.consecutivo;
                });
            } else {
                swal("Error!", response.message, "error");
                $("#btncrear").prop('disabled', false);
            }
        },
        error: function() {
            $.unblockUI();
            swal("Error!", "Ha ocurrido un error al procesar la solicitud.", "error");
            $("#btncrear").prop('disabled', false);
        }
    });

    $("#btncrear").prop('disabled', true);
});

// ─── MODAL ÍTEMS DEVOLUCIÓN PARCIAL ──────────────────────────────────────────

function abrirModalItemsDevolucion(numero, tiporef) {
    $('#divItemsDevLoading').show();
    $('#divItemsDevError').hide();
    $('#divItemsDevTabla').hide();
    $('#tbodyItemsDev').html('');
    $('#btnContinuarItemsDev').prop('disabled', true);
    $('#modalItemsDevolucion').modal('show');

    $.ajax({
        url:      CONFIG.endpoints.salidas.get_lineas_devolucion,
        type:     'POST',
        dataType: 'json',
        data:     { tipo: tiporef, numero: numero },
        success: function(data) {
            $('#divItemsDevLoading').hide();
            if (data.status !== 'success' || !data.lineas || data.lineas.length === 0) {
                $('#divItemsDevError').show();
                return;
            }

            var filas = '';
            var hayDisponibles = false;

            $.each(data.lineas, function(i, l) {
                var cantOrig  = parseFloat(l.cantidad_original);
                var cantDev   = parseFloat(l.cantidad_devuelta);
                var cantDisp  = parseFloat(l.cantidad_disponible);
                var valUnit   = parseFloat(l.Valor_Unitario).toLocaleString('es-CO', {minimumFractionDigits:2, maximumFractionDigits:2});
                var agotado   = cantDisp <= 0;

                if (!agotado) hayDisponibles = true;

                var devueltaHtml = cantDev > 0
                    ? '<span style="color:#c0392b;font-size:11px">' + cantDev + '</span>'
                    : '<span style="color:#aaa;font-size:11px">—</span>';

                var cantCeldaHtml = agotado
                    ? '<span style="color:#aaa;font-size:11px">Agotado</span>'
                    : '<input type="number" class="form-control form-control-sm inputCantDev" ' +
                      'value="' + cantDisp + '" min="0.001" max="' + cantDisp + '" step="any" ' +
                      'style="width:90px;display:none" disabled>';

                var rowStyle = agotado ? 'background:#f8f8f8;color:#aaa;' : '';

                filas +=
                    '<tr data-seq="' + l.seq + '" data-cant="' + cantDisp + '" style="' + rowStyle + '">' +
                    '<td style="text-align:center;vertical-align:middle">' +
                        (agotado ? '' : '<input type="checkbox" class="chkItemDev">') +
                    '</td>' +
                    '<td style="font-size:12px;vertical-align:middle">' + l.IdProducto + ' - ' + l.Producto + '</td>' +
                    '<td style="font-size:12px;vertical-align:middle">' + l.Unidad + '</td>' +
                    '<td style="text-align:right;font-size:12px;vertical-align:middle">' + cantOrig + '</td>' +
                    '<td style="text-align:center;vertical-align:middle">' + devueltaHtml + '</td>' +
                    '<td style="text-align:center;vertical-align:middle">' + cantCeldaHtml + '</td>' +
                    '<td style="text-align:right;font-size:12px;vertical-align:middle">' + valUnit + '</td>' +
                    '<td style="font-size:11px;vertical-align:middle">' + (l.Numero_Lote || '') + '</td>' +
                    '</tr>';
            });

            $('#tbodyItemsDev').html(filas);

            if (!hayDisponibles) {
                $('#tbodyItemsDev').after(
                    '<div class="alert alert-danger mt-2">' +
                    '<i class="fa fa-ban"></i> Todos los ítems de este documento ya fueron devueltos.' +
                    '</div>'
                );
                $('#btnContinuarItemsDev').prop('disabled', true);
            }

            $('#divItemsDevTabla').show();
        },
        error: function() {
            $('#divItemsDevLoading').hide();
            $('#divItemsDevError').show();
        }
    });
}

$(document).on('change', '.chkItemDev', function() {
    var $row   = $(this).closest('tr');
    var $input = $row.find('.inputCantDev');
    if ($(this).prop('checked')) {
        $input.show().prop('disabled', false);
    } else {
        $input.hide().prop('disabled', true);
    }
    var haySel = $('#tbodyItemsDev .chkItemDev:checked').length > 0;
    $('#btnContinuarItemsDev').prop('disabled', !haySel);
});

$(document).on('click', '#btnSeleccionarTodosItems', function(e) {
    e.preventDefault();
    $('#tbodyItemsDev .chkItemDev').each(function() {
        $(this).prop('checked', true).trigger('change');
    });
});

$(document).on('click', '#btnDeseleccionarTodosItems', function(e) {
    e.preventDefault();
    $('#tbodyItemsDev .chkItemDev').each(function() {
        $(this).prop('checked', false).trigger('change');
    });
});

$(document).on('click', '#btnCancelarItemsDev', function() {
    lineasDevolucionSeleccionadas = null;
    $('#modalItemsDevolucion').modal('hide');
    $('#btncrear').prop('disabled', false);
});

$(document).on('click', '#btnContinuarItemsDev', function() {
    var lineas = [];
    var errores = [];

    $('#tbodyItemsDev tr').each(function() {
        var $chk = $(this).find('.chkItemDev');
        if (!$chk.prop('checked')) return;

        var seq      = parseInt($(this).data('seq'), 10);
        var cantOrig = parseFloat($(this).data('cant'));
        var cantDev  = parseFloat($(this).find('.inputCantDev').val());

        if (isNaN(cantDev) || cantDev <= 0) {
            errores.push('La cantidad del ítem seq ' + seq + ' debe ser mayor a 0.');
            return;
        }
        if (cantDev > cantOrig) {
            errores.push('La cantidad del ítem seq ' + seq + ' (' + cantDev + ') supera la disponible (' + cantOrig + ').');
            return;
        }
        lineas.push({ seq: seq, cantidad: cantDev });
    });

    if (errores.length > 0) {
        swal('Advertencia', errores.join('\n'), 'warning');
        return;
    }
    if (lineas.length === 0) {
        swal('Advertencia', 'Debe seleccionar al menos un ítem para continuar.', 'warning');
        return;
    }

    lineasDevolucionSeleccionadas = lineas;
    $('#modalItemsDevolucion').modal('hide');
    abrirModalConceptoDevolucion();
});

// ─── MODAL CONCEPTO DOTACIÓN Y EPP ───────────────────────────────────────────

$(document).on('change', '#dotacion_epp', function() {
    if (!$(this).prop('checked')) return;
    var numdoc = $('#numdoc').val();
    if (!numdoc) return;

    $('#selectConceptoDotacion').html('<option value="">Cargando conceptos...</option>');
    $('#divSinConceptosDotacion').hide();
    $('#divSelectConceptoDotacion').show();
    $('#btnConfirmarConceptoDotacion').prop('disabled', false);

    $.ajax({
        url: CONFIG.endpoints.conceptosDotacion.listar_activos,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!Array.isArray(data) || data.length === 0) {
                $('#selectConceptoDotacion').html('');
                $('#divSelectConceptoDotacion').hide();
                $('#divSinConceptosDotacion').show();
                $('#btnConfirmarConceptoDotacion').prop('disabled', true);
            } else {
                var opts = '<option value="">-- Seleccione un concepto --</option>';
                $.each(data, function(i, c) {
                    opts += '<option value="' + c.id + '">' + c.nombre + '</option>';
                });
                $('#selectConceptoDotacion').html(opts);
            }
        },
        error: function() {
            $('#divSelectConceptoDotacion').hide();
            $('#divSinConceptosDotacion').show();
            $('#btnConfirmarConceptoDotacion').prop('disabled', true);
        }
    });
    $('#modalConceptoDotacion').modal('show');
});

$(document).on('click', '#btnCancelarConceptoDotacion', function() {
    $('#modalConceptoDotacion').modal('hide');
    $('#dotacion_epp').prop('checked', false);
});

$(document).on('click', '#btnConfirmarConceptoDotacion', function() {
    var idConcepto     = $('#selectConceptoDotacion').val();
    var nombreConcepto = $('#selectConceptoDotacion option:selected').text().trim();
    if (!idConcepto) {
        swal("Advertencia!", "Debe seleccionar un concepto de Dotación/EPP para continuar.", "warning");
        return;
    }
    $('#modalConceptoDotacion').modal('hide');
    $('#notas').val(nombreConcepto);
    var tipo   = getUrlParameter('tipo');
    var numdoc = $('#numdoc').val();
    if (tipo && numdoc) {
        $.post(CONFIG.endpoints.salidas.update_notas_etapa,
            { tipo: tipo, numdoc: numdoc, notas: nombreConcepto });
    }
});