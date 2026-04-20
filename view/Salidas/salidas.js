var tabla;
var usu_id = $('#user_id').val();
var rol_id = $('#rol_id').val();

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
            cargar_masiva_excel: "../../controller/salidas.php?op=cargar_masiva_excel",
            update_notas_etapa: "../../controller/salidas.php?op=update_notas_etapa",
            validar_os: "../../controller/salidas.php?op=validar_os"
        },
        etapas: {
            listar_activas: "../../controller/etapas.php?op=listar_activas"
        },
        conceptosDevolucion: {
            listar_activos: "../../controller/conceptosdevolucion.php?op=listar_activos"
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
            eliminar_masivo: "documento.php?op=eliminar_masivo"
        }
    }
};

function init() {
    // Inicialización si es necesaria
}

$(document).ready(function() {
    inicializarCombos();
    inicializarEventos();
    
    // Lista de tipos que requieren OS obligatoriamente
    const tiposRestringidos = ['215', '213', '914', '938', '947'];
    window.tiposRestringidos = tiposRestringidos;
    
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    if(tipo && consecutivo){
        listardetalle(tipo, consecutivo);
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
            $("#consecutivo").val(data.consecutivo);
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

    $("#direccion1").change(function() {
        const direccion = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion }, function(data) {
            data = JSON.parse(data);
            $("#telefono1").val(data.telefono_1);
        });
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

    // Devolución
    if (esDev) {
        const tipoDocOrig = $("#tipoDocOrig").val();
        const tipoDocOrigTexto = $("#tipoDocOrig option:selected").text().trim();
        const numeroDev = document.getElementById("numero").value;

        if (!validarCampoRequerido(tipoDocOrig, "Tipo Documento Original") ||
            !validarCampoRequerido(numeroDev, "Número del documento a devolver")) {
            return false;
        }

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

function guardarLote() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const lote        = $('#lote1').val();

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

    const sw = document.getElementById("sw").value;

    if (sw == 10 || sw == 2) {

      const nit1 = document.getElementById("nit1").value;
      const direccion1 = document.getElementById("direccion1").value;
      const nit2 = document.getElementById("nit2").value;
      const direccion2 = document.getElementById("direccion2").value;

      if (!validarCampoRequerido(nit1, "NIT Facturar A") ||
          !validarCampoRequerido(direccion1, "Dirección Facturar A") ||
          !validarCampoRequerido(nit2, "NIT Enviar A") ||
          !validarCampoRequerido(direccion2, "Dirección Enviar A")) {
          return false;
      }

      procesarGuardado(CONFIG.endpoints.salidas.guardar_salida);

    } else if (sw == 9) {
        const nit = document.getElementById("nit3").value;
        const direccion = document.getElementById("direccion3").value;
        const traslfact1 = document.getElementById("traslfact1").value;

        if (!validarCampoRequerido(nit, "NIT/Cédula") ||
            !validarCampoRequerido(direccion, "Dirección") ||
            !validarCampoRequerido(traslfact1, "Despacho")) {
            return false;
        }

        procesarGuardado(CONFIG.endpoints.documento.guardar_entrada);
    } else {
        procesarGuardado(CONFIG.endpoints.documento.guardar_doc);
    }
}

function procesarGuardado(endpoint) {
    $.blockUI({ message: '<h2>Guardando por favor Espere...</h2>' });
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.baseUrl + endpoint,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos) {                  
            swal({
                title: "Correcto!", 
                text: "Documento Registrado Correctamente", 
                type: "success"
            }, function() {
                window.location.href = 'index.php'; 
            });
            console.log(datos);
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
        "txt_tipodoc", "txt_numdoc", "txt_fecha1", "txt_pedido1",
        "txt_traslfact1", "txt_fecha_factura2",
        "tipodoc", "numdoc", "fecha1", "pedido1", "traslfact1", "fecha_factura2",
        "div_dotacion", "btnlot", "btnetapas", "btneliminarsel", "btnguardar"
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
        camposEditables.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.disabled = true;
        });

        window.documentoExportado = true;
    } else {
        window.documentoExportado = false;
        $("#btnguardar").prop('disabled', false)
                       .removeClass('btn-disabled')
                       .html('Guardar')
                       .removeAttr('title');
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
        
        // Llenar campos del formulario
        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#numdoc').val(data.Numero_documento);
        $('#pedido1').val(data.Numero_Docto_Base_2);
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
        $('#nit2').val(data.nit_Cedula_2);
        $('#nombre2').val(data.nombre2);
        if(data.nit_Cedula_2) {
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: data.nit_Cedula_2 }, function(html) {
                $('#direccion2').html(html);
                $('#direccion2 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(data.codigo_direccion_2).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        $('#notas').html(data.notas);
        $('#sw').val(data.Tipo_Docto_Base_2);
        $('#dotacion_epp').prop('checked', data.IdVendedor == 12);
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
    }
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

        if (data.status === 'success') {
            var displayValue = newValue || '';
            // Para fecha vence (col 9): el input type=date retorna YYYY-MM-DD, mostrar como DD/MM/YYYY
            if (cellIndex === 9 && newValue && newValue.indexOf('-') !== -1) {
                var dp = newValue.split('-');
                if (dp.length === 3) displayValue = dp[2] + '/' + dp[1] + '/' + dp[0];
            }
            cell.textContent = displayValue;

            row.classList.remove('editing', 'saving');
            limpiarEstadoEdicionNativa();

            mostrarFeedbackExitoso();
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

init();

// ─── CARGA MASIVA EXCEL ───────────────────────────────────────────────────────

function resetModalExcel() {
    document.getElementById('archivoExcel').value = '';
    document.getElementById('excelResultados').style.display = 'none';
    document.getElementById('tbExcelBody').innerHTML = '';
    document.getElementById('excelResumen').innerHTML = '';

    // Restaurar botones al estado inicial
    var btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = false;
    btnProcesar.style.display = 'inline-block';
    btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Procesar';

    document.getElementById('btnNuevoArchivo').style.display = 'none';

    var btnCerrar = document.getElementById('btnCerrarExcel');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.classList.remove('btn-success');
    btnCerrar.classList.add('btn-secondary');
}

$('#modalexcel').on('hidden.bs.modal', function() {
    resetModalExcel();
});

function cargarExcelMasivo() {
    const tipo       = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const fileInput  = document.getElementById('archivoExcel');

    if (!fileInput.files || fileInput.files.length === 0) {
        swal("Advertencia!", "Seleccione un archivo Excel (.xlsx) primero.", "warning");
        return;
    }

    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        swal("Advertencia!", "Solo se aceptan archivos con extensión .xlsx", "warning");
        return;
    }

    const nit       = (document.getElementById('nit1')        || document.getElementById('nit3'))?.value || '';
    const direccion = (document.getElementById('direccion1')  || document.getElementById('direccion3'))?.value || '';
    const dirLimpia = direccion.indexOf(',') !== -1 ? direccion.split(',')[0] : direccion;

    const formData = new FormData();
    formData.append('archivo',   file);
    formData.append('tipo',      tipo);
    formData.append('numdoc',    consecutivo);
    formData.append('nit',       nit);
    formData.append('direccion', dirLimpia);

    const btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Procesando...';

    $.ajax({
        url:         CONFIG.endpoints.salidas.cargar_masiva_excel,
        type:        'POST',
        data:        formData,
        processData: false,
        contentType: false,
        dataType:    'json',
        success: function(response) {
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Procesar';

            if (response.status === 'error') {
                swal("Error!", response.message, "error");
                return;
            }

            // Mostrar resumen
            const ok    = response.ok    || 0;
            const error = response.error || 0;
            const resumenClass = error > 0 ? (ok > 0 ? 'alert-warning' : 'alert-danger') : 'alert-success';
            document.getElementById('excelResumen').innerHTML =
                `<div class="alert ${resumenClass} py-2 mb-0">
                    <strong>Resultado:</strong>
                    <span class="badge badge-success ml-2">${ok} agregados</span>
                    <span class="badge badge-danger ml-1">${error} con error</span>
                </div>`;

            // Llenar tabla
            const tbody = document.getElementById('tbExcelBody');
            tbody.innerHTML = '';
            (response.resultados || []).forEach(function(r) {
                const statusBadge = r.status === 'ok'
                    ? '<span class="badge badge-success">OK</span>'
                    : '<span class="badge badge-danger">Error</span>';
                const tr = document.createElement('tr');
                tr.className = r.status === 'ok' ? 'table-success' : 'table-danger';
                tr.innerHTML = `<td>${r.fila}</td><td>${r.idProducto}</td><td>${r.cantidad}</td><td>${r.lote || '-'}</td><td>${statusBadge}</td><td>${r.mensaje}</td>`;
                tbody.appendChild(tr);
            });

            document.getElementById('excelResultados').style.display = 'block';

            // Cambiar botones: ocultar "Procesar", mostrar "Cargar otro" y cambiar "Cerrar" → "Listo"
            btnProcesar.style.display = 'none';
            document.getElementById('btnNuevoArchivo').style.display = 'inline-block';
            var btnCerrar = document.getElementById('btnCerrarExcel');
            btnCerrar.innerHTML = '<i class="fa fa-check"></i> Listo';
            btnCerrar.classList.remove('btn-secondary');
            btnCerrar.classList.add('btn-success');

            // Refrescar detalle si hubo al menos un OK
            if (ok > 0) {
                listardetalle(tipo, consecutivo);
                actualizarTodosLosTotales(tipo, consecutivo);
            }
        },
        error: function(xhr, status, errorThrown) {
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Procesar';
            console.error('[cargarExcelMasivo] Error HTTP:', status, errorThrown, xhr.responseText);
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
    formDataDev.append('idConceptoDevolucion',     idConcepto);
    formDataDev.append('nombreConceptoDevolucion', nombreConcepto);

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