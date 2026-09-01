var tabla;
var usu_id = $('#user_id').val();
var rol_id = $('#rol_id').val();

let editingRow = null;
let originalData = {};
let documentoExportado = false;

// Configuración centralizada
const CONFIG = {
    baseUrl: "../../controller/",
    endpoints: {
        permisos: "permisos.php?op=combo_entradas_permisos",
        tipodoctos: {
            consecutivos: "tipodoctos.php?op=consecutivos"
        },
        terceros: {
            combo_dir: "terceros.php?op=combo_dir",
            telefono_dir: "terceros.php?op=telefono_dir"
        },
        documento: {
            preview_doc_entrada: "documento.php?op=preview_doc_entrada",
            insert_doc_entrada: "documento.php?op=insert_doc_entrada",
            insert_doc_entrada_manual: "documento.php?op=insert_doc_entrada_manual",
            insert_linea_entrada_manual: "documento.php?op=insert_linea_entrada_manual",
            get_info_producto: "salidas.php?op=get_info_producto",
            combo_lotes: "salidas.php?op=combo_lotes",
            asignar_selecc: "documento.php?op=asignar_selecc",
            update_prod_doc: "documento.php?op=update_prod_doc",
            guardar_entrada: "documento.php?op=guardar_entrada",
            guardar_doc: "documento.php?op=guardar_doc",
            mostrar_entrada: "documento.php?op=mostrar_entrada",
            combo_transportador: "documento.php?op=combo_transportador",
            combo_vehiculo: "documento.php?op=combo_vehiculo",
            listar_detalle_entrada: "documento.php?op=listar_detalle_entrada",
            total_entrada: "documento.php?op=total_entrada",
            totales: "documento.php?op=totales",
            total_cantidad: "documento.php?op=total_cantidad",
            mostrarXproducto: "documento.php?op=mostrarXproducto",
            duplicar_linea: "documento.php?op=duplicar_linea",
            eliminar: "documento.php?op=eliminar",
            eliminar_masivo: "documento.php?op=eliminar_masivo",
            actualizar_producto_linea: "documento.php?op=actualizar_producto_linea",
            reiniciar_doc_desde_pedido: "../../controller/documento.php?op=reiniciar_doc_desde_pedido"
        }
    }
};

function init() {
    // Inicialización si es necesaria
}

$(document).ready(function() {
    inicializarCombos();
    inicializarEventos();
    
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    listardetalle(tipo, consecutivo);
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
function inicializarCombos() {
    $.post(CONFIG.baseUrl + CONFIG.endpoints.permisos, function(data) {
        $('#idTipo').html(data);
    });
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.combo_transportador, function(data) {
        $('#idTransportador').html(data);
    });
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.combo_vehiculo, function(data) {
        $('#idVehiculo').html(data);
    });
}

function inicializarEventos() {
    // Traslado/Factura: solo letras y números, sin espacios ni símbolos
    $("#traslfact1").on("input", function() {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    // Evento para cambio de tipo de documento
    $("#idTipo").change(function() {
        const idTipo = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.tipodoctos.consecutivos, { idTipo }, function(data) {
            data = JSON.parse(data);
            $("#consecutivo").val(data.consecutivo);
        });

        const tiposRestringidosEntrada = ['294'];
        if (tiposRestringidosEntrada.includes(idTipo)) {
            if (window.permiteEntradaFrigopork) {
                // Con permiso: se flexibiliza (no se obliga). El usuario elige entre
                // crear por Número de Orden o de forma Manual.
                $("#col_docref").show();
                $("#opt_manual").show();
                $("#opt_si").hide();
                $("#docref").prop('disabled', false).val("0");
                showInp();
            } else {
                // Sin permiso: se obliga a crear por Número de Orden, igual que cualquier otro tipo.
                $("#col_docref").hide();
                $("#opt_manual").hide();
                $("#opt_si").hide();
                $("#docref").val("0").prop('disabled', true);
                showInp();
            }
        } else {
            // Otro tipo: selector oculto, siempre por Número de Orden
            $("#col_docref").hide();
            $("#opt_manual").hide();
            $("#opt_si").hide();
            if ($("#docref").val() === "2") {
                $("#docref").val("0");
            }
            $("#docref").prop('disabled', false);
            showInp();
        }
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

// FUNCIONES DE GESTIÓN DE DOCUMENTOS
function crearDocumento() {
    const tipo = document.getElementById("idTipo").value;
    const consecutivo = document.getElementById("consecutivo").value;
    const docref = document.getElementById("docref").value;
    const numero = document.getElementById("numero").value;

    const esFrigoporkManual = tipo === '294' && docref === '2' && window.permiteEntradaFrigopork;

    if (!validarCampoRequerido(tipo, "Tipo de Documento") ||
        !validarCampoRequerido(consecutivo, "Consecutivo")) {
        return false;
    }

    if (!esFrigoporkManual && !validarCampoRequerido(numero, "Número")) {
        return false;
    }

    if (esFrigoporkManual) {
        const nit1 = $("#nit1").val();
        const dir1 = $("#select_dir1").val();
        const nit2 = $("#nit2").val();
        const dir2 = $("#select_dir2").val();

        if (!validarCampoRequerido(nit1, "NIT Facturar A") ||
            !validarCampoRequerido(dir1, "Dirección Facturar A") ||
            !validarCampoRequerido(nit2, "NIT Enviar A") ||
            !validarCampoRequerido(dir2, "Dirección Enviar A")) {
            return false;
        }

        $("#btncrear").prop('disabled', true);
        $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });

        $.ajax({
            url: CONFIG.baseUrl + CONFIG.endpoints.documento.insert_doc_entrada_manual,
            type: "POST",
            data: { idTipo: tipo, nit1: nit1, dir1: dir1, nit2: nit2, dir2: dir2 },
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
            error: function(xhr, status, error) {
                $.unblockUI();
                swal("Error!", "Ha ocurrido un error al procesar la solicitud. Por favor intente nuevamente.", "error");
                console.error("Error en la petición:", error);
                $("#btncrear").prop('disabled', false);
            }
        });
        return false;
    }

    // Flujo normal (Orden de Compra): antes de crear, mostrar una vista previa con la
    // información que se va a usar para que el usuario la confirme.
    mostrarPreviewCrearDocumento(tipo, numero);
    return false;
}

function mostrarPreviewCrearDocumento(tipo, numero) {
    $("#btncrear").prop('disabled', true);
    $.blockUI({ message: '<h2>Consultando información...</h2>' });

    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.preview_doc_entrada, { idTipo: tipo, numero: numero }, function(response) {
        $.unblockUI();
        if (response.status !== 'success') {
            swal("Error!", response.message || "No se pudo consultar la orden de compra.", "error");
            $("#btncrear").prop('disabled', false);
            return;
        }

        const info = response.data;
        $('#previewTipoDoc').text(info.tipoDoctos || $('#idTipo option:selected').text());
        $('#previewConsecutivo').text(info.consecutivo);
        $('#previewNumeroOC').text(numero);
        $('#previewProveedor').text(info.proveedor || '-');
        $('#previewItems').text(info.totalItems);
        $('#previewValorTotal').text('$ ' + Number(info.valorTotal || 0).toLocaleString('es-CO', { minimumFractionDigits: 2 }));
        $('#previewNotas').text(info.notas || '-');

        $('#modalConfirmarCrear').modal('show');
    }, 'json').fail(function() {
        $.unblockUI();
        swal("Error!", "Error de conexión al consultar la orden de compra.", "error");
        $("#btncrear").prop('disabled', false);
    });
}

// Evita reactivar el botón "Crear" cuando la modal se cierra porque el usuario confirmó
// (en vez de cancelarla), ya que en ese caso la creación real sigue en curso.
let confirmandoCreacionDoc = false;

$(document).on('click', '#btnConfirmarCrearDoc', function() {
    confirmandoCreacionDoc = true;
    $('#modalConfirmarCrear').modal('hide');
    ejecutarCreacionDocumentoEntrada();
});

$('#modalConfirmarCrear').on('hidden.bs.modal', function() {
    if (!confirmandoCreacionDoc) {
        $("#btncrear").prop('disabled', false);
    }
    confirmandoCreacionDoc = false;
});

function ejecutarCreacionDocumentoEntrada() {
    $("#btncrear").prop('disabled', true);
    $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });
    const formData = new FormData($("#doc_form")[0]);
    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.insert_doc_entrada,
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
    console.log('Presion guardar lote');

    const seleccionados = $('#tb-doc input[name="id[]"]:checked').length;
    if (seleccionados === 0) {
        swal("Advertencia!", "Debe marcar al menos un producto en la tabla antes de asignar el lote.", "warning");
        return;
    }

    let formData = new FormData($("#doc_form")[0]);

    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.asignar_selecc,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos){
            console.log(datos);
            if (String(datos).indexOf('No se seleccionó') !== -1) {
                swal("Advertencia!", "Debe marcar al menos un producto en la tabla antes de asignar el lote.", "warning");
                return;
            }
            swal("Correcto!", "Lote Ingresado Correctamente", "success");
            $("#lot").modal('hide');
            $('#tb-doc').DataTable().ajax.reload();
            $('#lote1').val('');
        },
        error: function(xhr, status, error) {
            swal("Error!", "Ha ocurrido un error al asignar el lote. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
        }
    });
}

$('#lot').on('show.bs.modal', function() {
    if (window.permiteLoteManualEntradas) {
        $('#chkLoteManualWrap').show();
    }
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.combo_lotes, function(data) {
        $('#lote1').html(data);
    });
});

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

function editarProducto() {
    const idproducto = document.getElementById("idproducto").value;
    const cantidad = document.getElementById("cantidad").value;
    const valorUnitario = document.getElementById("Valor_Unitario").value;
    const lote = document.getElementById("lote").value;
    const fechaVence = document.getElementById("fecha_vence").value;
    
    if (!validarCampoRequerido(idproducto, "Código de Producto") ||
        !validarCampoRequerido(cantidad, "Cantidad") ||
        !validarCampoRequerido(valorUnitario, "Valor Unitario") ||
        !validarCampoRequerido(lote, "Lote") ||
        !validarCampoRequerido(fechaVence, "Fecha de Vencimiento")) {
        return false;
    }
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
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
                $('#cantidad, #idproducto, #Valor_Unitario, #lote, #fecha_vence').val('');
        
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

    const totalProductos = $('#tb-doc').DataTable().rows().count();
    if (totalProductos === 0) {
        swal("Advertencia!", "Debe agregar al menos un producto antes de guardar el documento.", "warning");
        return false;
    }

    const sw = document.getElementById("sw").value;
    
    if (sw == 9) {
        console.log("Vamos a Guardar Documentos de Entrada");
        
        const nit = document.getElementById("nit3").value;
        const direccion = document.getElementById("direccion3").value;
        const traslfact1 = document.getElementById("traslfact1").value;
        
        if (!validarCampoRequerido(nit, "NIT/Cédula") ||
            !validarCampoRequerido(direccion, "Dirección") ||
            !validarCampoRequerido(traslfact1, "Traslado/Factura")) {
            return false;
        }
        
        procesarGuardado(CONFIG.endpoints.documento.guardar_entrada);
    } else {
        console.log("Vamos a Guardar Documentos con Traslado");
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
            const texto = String(datos);
            if (texto.indexOf('No se puede guardar') !== -1 || texto.toLowerCase().indexOf('no se actualizo') !== -1) {
                swal("No se pudo guardar", texto.trim(), "error");
                return;
            }
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
    const docref = document.getElementById("docref").value;

    // Mostrar tipo de doc referencia solo cuando docref=1 (Sí/traslado)
    const showRef = docref === "1" ? "inline-block" : "none";
    document.getElementById("txt_tipoDocRef").style.display = showRef;
    document.getElementById("tipoDocRef").style.display = showRef;

    const numeroEl = document.getElementById("numero");
    const txtNumeroEl = document.getElementById("txt_numero");
    const preCreacion = document.getElementById("btncrear").style.display !== "none";

    const camposFacturarA = ["hr1", "txt_nit1", "nit1", "txt_nombre1", "nombre1", "txt_telefono1", "telefono1", "txt_direccion1"];
    const camposEnviarA   = ["hr2", "txt_nit2", "nit2", "txt_nombre2", "nombre2", "txt_direccion2"];

    if (docref === "2" && preCreacion) {
        // Manual Frigopork: ocultar Número, mostrar Facturar A y Enviar A con selects de dirección
        numeroEl.style.display = "none";
        txtNumeroEl.style.display = "none";
        numeroEl.removeAttribute("required");

        camposFacturarA.forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = "inline-block";
        });
        document.getElementById("select_dir1").style.display = "inline-block";
        document.getElementById("direccion1").style.display = "none";

        camposEnviarA.forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = "inline-block";
        });
        document.getElementById("select_dir2").style.display = "inline-block";
        document.getElementById("direccion2").style.display = "none";

    } else {
        // Modo normal: mostrar Número, ocultar secciones de creación manual
        numeroEl.style.display = "inline-block";
        txtNumeroEl.style.display = "inline-block";
        numeroEl.setAttribute("required", "required");

        if (preCreacion) {
            camposFacturarA.forEach(function(id) {
                const el = document.getElementById(id);
                if (el) el.style.display = "none";
            });
            document.getElementById("select_dir1").style.display = "none";
            camposEnviarA.forEach(function(id) {
                const el = document.getElementById(id);
                if (el) el.style.display = "none";
            });
            document.getElementById("select_dir2").style.display = "none";
        }
    }
}

function configurarInterfazParaDocumentoExistente(data) {
    const elementosOcultar = [
        "idTipo", "consecutivo", "numero", "docref", "fecha",
        "txt_idTipo", "txt_consecutivo", "txt_numero", "txt_docref", "txt_fecha"
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
        "txt_traslfact1", "txt_remision", "tipodoc", "numdoc",
        "fecha1", "pedido1", "traslfact1", "remision", "btnlot", "btneliminarsel", "btnguardar",
        "txt_transportador", "idTransportador", "txt_vehiculo", "idVehiculo"
    ];
    
    elementosMostrar.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });

    // Configuración específica según tipo de documento
    if(data.Tipo_Docto_Base_2 == 9){
        mostrarCamposEntrada();
    } else {
        mostrarCamposTraslado();
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
}

function configurarEstadoExportado(exportado) {
    if(exportado === 'S') {
        documentoExportado = true;
        $("#btnguardar, #btnlot, #btneliminarsel").prop('disabled', true).addClass('btn-disabled');
        $("#btnguardar").html('Documento Exportado')
                       .attr('title', 'No se puede modificar un documento exportado');
        ['nit1','nombre1','direccion1','telefono1',
         'nit2','nombre2','direccion2',
         'nit3','nombre3','direccion3','telefono3',
         'traslfact1','remision','idTransportador','idVehiculo','notas'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.disabled = true;
        });
        $('#tb-doc tbody td.editable-cell').removeClass('editable-cell');
        $('#btnreiniciar').hide();
    } else {
        documentoExportado = false;
        $("#btnguardar").prop('disabled', false)
                       .removeClass('btn-disabled')
                       .html('Guardar')
                       .removeAttr('title');
    }
}

function reiniciarDocumento() {
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    swal({
        title: "¿Reiniciar documento?",
        text: "Se eliminarán todas las líneas actuales y se volverán a cargar desde el pedido original. Esta acción no se puede deshacer.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Sí, reiniciar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(confirmed) {
        if (!confirmed) return;
        enviarReinicioEntrada(tipo, consecutivo, false);
    });
}

// El backend responde status "confirmar" cuando el documento ya había sido guardado y
// alguien lo desmarcó: en ese caso el detalle actual pudo haberse impreso, así que se
// pide un segundo sí explícito antes de destruirlo.
function enviarReinicioEntrada(tipo, consecutivo, confirmado) {
    $.blockUI({ message: '<h2>Reiniciando, favor espere...</h2>' });
    $.ajax({
        url: CONFIG.endpoints.documento.reiniciar_doc_desde_pedido,
        type: "POST",
        data: { tipo: tipo, numdoc: consecutivo, confirmado: confirmado ? '1' : '0' },
        dataType: "json",
        success: function(resp) {
            $.unblockUI();
            if (resp.status === 'success') {
                swal("Correcto!", resp.message, "success");
                $('#tb-doc').DataTable().ajax.reload();
                actualizarTodosLosTotales(tipo, consecutivo);
            } else if (resp.status === 'confirmar') {
                swal({
                    title: "Documento ya impreso",
                    text: resp.message + "\n\n¿Reiniciar de todas formas?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "Sí, reiniciar igual",
                    cancelButtonText: "No, cancelar",
                    closeOnConfirm: true
                }, function(seguro) {
                    if (seguro) enviarReinicioEntrada(tipo, consecutivo, true);
                });
            } else {
                swal("Error!", resp.message, "error");
            }
        },
        error: function() {
            $.unblockUI();
            swal("Error!", "No se pudo reiniciar el documento.", "error");
        }
    });
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
                       console.log('Todos los totales han sido actualizados');
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
        $('#numdoc').val(data.Numero_documento);
        $('#pedido1').val(data.Numero_Docto_Base_2);
        $('#traslfact1').val(data.Numero_Docto_Base);        
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#direcc').val(data.codigo_direccion);
        $('#direccion1').val(data.direccion);
        $('#telefono1').val(data.telefono_1);
        var nit2Usar = data.nit_Cedula_2       || data.nit_Cedula;
        var nom2Usar = data.nombre2            || data.Nombre_Cliente;
        var dir2Usar = data.codigo_direccion_2 || data.codigo_direccion;

        if (String(data.Tipo_Docto_Base_2) === '9') {
            // Entrada desde pedido: Enviar A usa nit3/nombre3/direccion3
            $('#nit3').val(nit2Usar);
            $('#nombre3').val(nom2Usar);
            if (nit2Usar) {
                $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: nit2Usar }, function(html) {
                    $('#direccion3').html(html);
                    $('#direccion3 option').each(function() {
                        if ($(this).val().split(',')[0].trim() == String(dir2Usar).trim()) {
                            $(this).prop('selected', true);
                            var dirVal = $(this).val();
                            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion: dirVal }, function(resp) {
                                resp = JSON.parse(resp);
                                $('#telefono3').val(resp.telefono_1);
                            });
                            return false;
                        }
                    });
                });
            }
        } else {
            // Traslado: Enviar A usa nit2/nombre2/direccion2
            $('#nit2').val(nit2Usar);
            $('#nombre2').val(nom2Usar);
            $('#codigo_direccion2').val(dir2Usar);
            $('#direccion2').val(data.direccion2 || data.direccion);
        }
        $('#notas').html(data.notas);
        $('#sw').val(data.Tipo_Docto_Base_2);
        if(data.IdTransportador){ $('#idTransportador').val(data.IdTransportador); }
        if(data.IdVehiculo){ $('#idVehiculo').val(data.IdVehiculo); }

        if(data !== null){
            try {
                configurarInterfazParaDocumentoExistente(data);
            } catch(e) {
                console.error('Error en configurarInterfazParaDocumentoExistente:', e);
            }

            // Mostrar Reiniciar si tiene documento de referencia y no está exportado
            var tienePedido   = String(data.Tipo_Docto_Base_2) === '9' && data.Numero_Docto_Base_2 && String(data.Numero_Docto_Base_2).trim() !== '';
            var tieneTraslado = data.Numero_Docto_Base && String(data.Numero_Docto_Base).trim() !== '' && String(data.Numero_Docto_Base).trim() !== '0';
            console.log('Reiniciar check:', { tienePedido, tieneTraslado, Tipo_Docto_Base_2: data.Tipo_Docto_Base_2, Numero_Docto_Base: data.Numero_Docto_Base, exportado: data.exportado });
            if ((tienePedido || tieneTraslado) && data.exportado !== 'S') {
                $('#btnreiniciar').show();
            } else {
                $('#btnreiniciar').hide();
            }

            // Mostrar "Agregar Producto" solo en entradas manuales (sin pedido ni traslado)
            const esEntradaManual = !tienePedido && !tieneTraslado && data.exportado !== 'S';
            if (esEntradaManual) {
                $('#btnAgregarProd').show();
            } else {
                $('#btnAgregarProd').hide();
            }
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
            { "targets": [14], "visible": false, "searchable": false } // Grupo de producto (oculta, usada por Salidas)
        ],
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 70,
        "autoWidth": false,
        "createdRow": function(row, data, dataIndex) {
            if (!documentoExportado) {
                $('td', row).eq(4).addClass('editable-cell');  // Cantidad
                $('td', row).eq(5).addClass('editable-cell');  // % Desc
                // col 6 = % IVA (solo lectura, no editable)
                if (window.puedeEditarValor) {
                    $('td', row).eq(7).addClass('editable-cell');  // Valor - solo LAUREN y SA
                }
                $('td', row).eq(8).addClass('editable-cell');  // Lote
                $('td', row).eq(9).addClass('editable-cell');  // Fecha Venc
                $('td', row).eq(10).addClass('editable-cell'); // Nota
                $('td', row).eq(11).addClass('editable-cell'); // Unidades
            }
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

    // Evento para eliminar con delegation
    document.removeEventListener('click', manejarEliminarGlobal);
    document.addEventListener('click', manejarEliminarGlobal);

    // Evento para imprimir etiqueta con delegation
    document.removeEventListener('click', manejarImprimirEtiquetaGlobal);
    document.addEventListener('click', manejarImprimirEtiquetaGlobal);

    // Evento para actualizar %IVA/Valor desde el producto, con delegation
    document.removeEventListener('click', manejarActualizarProductoGlobal);
    document.addEventListener('click', manejarActualizarProductoGlobal);
}

// Función separada para manejar doble clic
function manejarDobleClic(e) {
    if (documentoExportado) return;
    if (e.target.classList.contains('editable-cell')) {
        console.log('Doble clic en celda editable');
        iniciarEdicionNativa(e.target);
    }
}

// Función separada para manejar eliminación global
function manejarEliminarGlobal(e) {
    if (documentoExportado) return;
    if (e.target.closest('.btn-eliminar')) {
        e.preventDefault();
        const row = e.target.closest('tr');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();

        console.log(' Eliminando producto:', producto);
        eliminar(tipo, consecutivo, producto, seq);
    }
}

// Función separada para manejar impresión de etiqueta global
function manejarImprimirEtiquetaGlobal(e) {
    if (e.target.closest('.btn-imprimir-etiqueta')) {
        e.preventDefault();
        const row = e.target.closest('tr');
        abrirModalEtiqueta(row);
    }
}

// Refresca %IVA y Costo de una línea tomando los datos actuales del producto en el
// catálogo. El Valor (precio de compra) no se toca: es el precio real negociado en la
// OC o digitado manualmente, no necesariamente igual al costo del catálogo.
function manejarActualizarProductoGlobal(e) {
    if (documentoExportado) return;
    const btn = e.target.closest('.btn-actualizar-producto');
    if (!btn || btn.disabled) return;

    e.preventDefault();
    const row = btn.closest('tr');
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const seq = row.cells[0].textContent.trim();
    const producto = row.cells[1].textContent.trim();
    const nombreProducto = row.cells[2].textContent.trim();

    swal({
        title: "¿Actualizar producto?",
        text: "Se sobreescribirá el %IVA de \"" + nombreProducto + "\" con los datos actuales del producto en el catálogo. El Valor no se modifica.",
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
            swal({
                title: "¡Actualizado!",
                text: "El %IVA se actualizó desde el producto.",
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
        case 4:  // Cantidad
        case 5:  // % Desc
        case 7:  // Valor
        case 11: // Unidades
            input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.step = cellIndex === 7 ? '0.01' : '1';
            break;
        case 9: // Fecha Venc
            input = document.createElement('input');
            input.type = 'date';
            input.value = currentValue;
            break;
        case 10: // Nota - permitir vacío
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.placeholder = "Nota opcional...";
            break;
        default: // Lote (col 8) y otros campos
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

    // Validación básica - EXCLUIR el campo de notas (índice 10)
    if (!newValue && cellIndex !== 10) { // El campo 10 es "Nota"
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
        case 4:  formData.append('cantidad',    newValue); break;
        case 5:  formData.append('descuento',   newValue); break;
        // case 6: % IVA - no editable
        case 7:  formData.append('valor',       newValue); break;
        case 8:  formData.append('lote',        newValue); break;
        case 9:  formData.append('fecha_vence', newValue); break;
        case 10: formData.append('nota',        newValue); break; // Nota puede ser vacía
        case 11: formData.append('unidades',    newValue); break;
    }

    fetch(CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(response => {
        console.log('📥 Respuesta del servidor:', response);

        if (response && response.status === "success") {
            // 🔥 CORRECIÓN: Primero actualizar el contenido de la celda
            cell.textContent = newValue || '';
            
            // 🔥 CORRECIÓN: Limpiar inmediatamente el estado de edición
            row.classList.remove('editing', 'saving');
            limpiarEstadoEdicionNativa();
            
            mostrarFeedbackExitoso();
            actualizarTodosLosTotales(tipo, consecutivo);
            
            console.log('✅ Cambio guardado exitosamente');
            
        } else {
            row.classList.remove('saving');
            swal("Error!", "No se pudo guardar el cambio en el servidor. " + (response.message || JSON.stringify(response)), "error");
            console.error("Respuesta inesperada del servidor:", response);
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
                <button type="button" class="btn btn-success btn-sm btn-action btn-imprimir-etiqueta" title="Imprimir Etiqueta">
                    <i class="fa fa-tag"></i>
                </button>
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
                
                if (data.includes("success") || data.includes("Eliminado correctamente")) {
                    swal({
                        title: "¡Eliminado!",
                        text: "El producto ha sido eliminado correctamente",
                        type: "success",
                        confirmButtonClass: "btn-success"
                    }, function(){
                        $('#tb-doc').DataTable().ajax.reload();
                        actualizarTodosLosTotales(tipo, consecutivo);
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

    var lista = seleccionados.map(function(p) {
        return '<li><b>' + p.producto + '</b> – ' + p.nombre + ' (Cant: ' + p.cantidad + ')</li>';
    }).join('');

    var mensaje = '<div style="text-align:left;padding:8px;">' +
        '<p>Se eliminarán <b>' + seleccionados.length + '</b> producto(s):</p>' +
        '<ul style="max-height:160px;overflow-y:auto;padding-left:18px;">' + lista + '</ul>' +
        '<br><p style="color:#d9534f;font-weight:bold;">&#9888; Esta acción no se puede deshacer</p>' +
        '</div>';

    swal({
        title:              '¿Eliminar productos seleccionados?',
        text:               mensaje,
        html:               true,
        type:               'warning',
        showCancelButton:   true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText:  'Sí, eliminar',
        cancelButtonText:   'Cancelar',
        closeOnConfirm:     false
    }, function(isConfirm) {
        if (!isConfirm) return;

        swal({ title: 'Eliminando...', text: 'Por favor espere', type: 'info', showConfirmButton: false });

        var seqs     = seleccionados.map(function(p) { return p.seq; }).join(',');
        var productos = seleccionados.map(function(p) { return p.producto; }).join(',');

        $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.eliminar_masivo, {
            tipo:        tipo,
            consecutivo: consecutivo,
            seqs:        seqs,
            productos:   productos
        }, function(data) {
            if (data.trim() === 'success') {
                swal({
                    title: '¡Eliminados!',
                    text:  seleccionados.length + ' producto(s) eliminado(s) correctamente',
                    type:  'success',
                    confirmButtonClass: 'btn-success'
                }, function() {
                    $('#tb-doc').DataTable().ajax.reload(null, false);
                    actualizarTodosLosTotales(tipo, consecutivo);
                });
            } else {
                swal('Error', 'No se pudieron eliminar los productos. Respuesta: ' + data, 'error');
            }
        }).fail(function() {
            swal('Error', 'Error de conexión. No se pudieron eliminar los productos.', 'error');
        });
    });
}

// FUNCIONES DE IMPRESIÓN DE ETIQUETA
function abrirModalEtiqueta(row) {
    const codigo   = row.cells[1].textContent.trim();
    const nombre   = row.cells[2].textContent.trim();
    const lote     = row.cells[8].textContent.trim();
    const cantidadRaw = row.cells[4].textContent.trim();
    const cantidad = parseFloat(cantidadRaw.replace(/,/g, '')) || 0;

    $('#etq_codigo').val(codigo);
    $('#etq_nombre').val(nombre);
    $('#etq_lote').val(lote);
    $('#etq_cantidad').val(cantidad);

    $('#modalImprimirEtiqueta').modal('show');
}

function imprimirEtiqueta() {
    const codigo   = $('#etq_codigo').val().trim();
    const nombre   = $('#etq_nombre').val().trim();
    const lote     = $('#etq_lote').val().trim();
    const cantidad = parseFloat($('#etq_cantidad').val());

    if (!cantidad || cantidad <= 0) {
        swal("Advertencia!", "La cantidad debe ser mayor a 0.", "warning");
        return;
    }

    // Formato barcode: 6 dígitos código + 4 dígitos entero + 2 dígitos decimal + 7 chars lote (sin separadores)
    const codigoFormateado = String(codigo).padStart(6, '0');

    const cantFixed        = Math.abs(cantidad).toFixed(2).split('.');
    const cantidadFormateada = cantFixed[0].padStart(4, '0') + '.' + cantFixed[1]; // solo para display
    const cantidadBarcode    = cantFixed[0].padStart(4, '0') + cantFixed[1];       // 6 dígitos sin punto

    const loteFormateado = String(lote).padStart(7, '0');

    const valorBarcode = codigoFormateado + cantidadBarcode + loteFormateado; // ej: 0110260010000000000

    var baseUrl = window.location.href.split('/view/')[0];
    var logoUrl = baseUrl + '/public/logo-empresas/logo-empresa.png';

    var htmlContent = '<!DOCTYPE html><html><head>' +
        '<meta charset="UTF-8">' +
        '<title>Etiqueta ' + codigoFormateado + '</title>' +
        '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>' +
        '<style>' +
        '@page { size: 4.8cm 4.8cm; margin: 0; }' +
        'html, body { margin: 0; padding: 0; width: 4.8cm; height: 4.8cm; font-family: Arial, sans-serif; }' +
        '.etiqueta { width: 4.8cm; height: 4.8cm; padding: 1.5mm 2mm; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; gap: 0.5mm; overflow: hidden; }' +
        '.etq-logo { max-width: 75%; max-height: 8mm; object-fit: contain; display: block; }' +
        '.etq-codigo-destaque { font-size: 15px; font-weight: bold; color: #000; text-align: center; letter-spacing: 1px; width: 100%; line-height: 1.2; }' +
        '.etq-cantidad { font-size: 8px; color: #333; text-align: center; width: 100%; }' +
        '.etq-barcode-wrap { width: 100%; display: flex; flex-direction: column; align-items: center; padding: 0 2mm; box-sizing: border-box; }' +
        '#etq-barcode { display: block; max-width: 100%; height: auto; }' +
        '.etq-barcode-val { font-size: 8.5px; font-weight: bold; text-align: center; letter-spacing: 0.5px; color: #000; width: 100%; margin-top: 0; line-height: 1; }' +
        '@media print { html, body { margin: 0; } }' +
        '</style></head><body>' +
        '<div class="etiqueta">' +
        '<img class="etq-logo" src="' + logoUrl + '" alt="CERVALLE">' +
        '<div class="etq-codigo-destaque">' + codigo + '</div>' +
        '<div class="etq-cantidad">Cantidad: ' + parseFloat(cantidad.toFixed(2)) + '</div>' +
        '<div class="etq-barcode-wrap"><svg id="etq-barcode"></svg>' +
        '<div class="etq-barcode-val">' + valorBarcode + '</div></div>' +
        '</div>' +
        '<script>' +
        'window.onload = function() {' +
        '    JsBarcode("#etq-barcode", "' + valorBarcode + '", {' +
        '        format: "CODE128",' +
        '        width: 1.2,' +
        '        height: 28,' +
        '        displayValue: false,' +
        '        margin: 0' +
        '    });' +
        '    setTimeout(function() { window.print(); }, 500);' +
        '};' +
        '<\/script>' +
        '</body></html>';

    var newWindow = window.open('', '_blank');
    newWindow.document.write(htmlContent);
    newWindow.document.close();

    $('#modalImprimirEtiqueta').modal('hide');
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

$(document).on("click", "#btneditar", function(event) {
    event.preventDefault();
    editarProducto();
});

$(document).on("click", "#btnguardar", function() {
    guardarDocumento();
});

$(document).on("click", "#btnImprimirEtiqueta", function() {
    imprimirEtiqueta();
});

function cargarInfoProductoEntrada() {
    const idProducto = document.getElementById("idproducto_m").value;
    if (!idProducto) return;
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const nit         = $('#nit1').val() || '';
    let dir           = $('#direccion1').val() || '';
    if (dir.indexOf(',') !== -1) dir = dir.split(',')[0];
    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.get_info_producto,
        type: "POST",
        data: { idProducto, tipo, numdoc: consecutivo, nit, direccion: dir },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                document.getElementById("nombre_prod_m").value         = response.nombre;
                document.getElementById("Valor_Unitario_m").value      = response.precio;
                document.getElementById("porcentaje_iva_m").value      = response.porcentaje_impuesto + '%';
                document.getElementById("porcentaje_impuesto_m").value = response.porcentaje_impuesto;
            } else {
                document.getElementById("nombre_prod_m").value         = '';
                document.getElementById("Valor_Unitario_m").value      = '';
                document.getElementById("porcentaje_iva_m").value      = '';
                document.getElementById("porcentaje_impuesto_m").value = '0';
                swal("Advertencia!", response.message, "warning");
            }
        },
        error: function() {
            document.getElementById("nombre_prod_m").value         = '';
            document.getElementById("Valor_Unitario_m").value      = '';
            document.getElementById("porcentaje_iva_m").value      = '';
            document.getElementById("porcentaje_impuesto_m").value = '0';
            swal("Error!", "Error al consultar el producto.", "error");
        }
    });
}

// Limpiar modal agregar producto al abrir y cargar combo lotes
$('#modalAgregarProd').on('show.bs.modal', function() {
    $('#idproducto_m').val('');
    $('#nombre_prod_m').val('');
    $('#cantidad_m').val('');
    $('#Valor_Unitario_m').val('');
    $('#porcentaje_iva_m').val('');
    $('#porcentaje_impuesto_m').val('0');
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.combo_lotes, function(data) {
        $('#lote_m').html(data);
    });
});

$('#modalAgregarProd').on('shown.bs.modal', function() {
    $('#idproducto_m').focus();
});

$(document).on("blur", "#idproducto_m", function() {
    cargarInfoProductoEntrada();
});

$(document).on("keydown", "#idproducto_m", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        cargarInfoProductoEntrada();
        $('#cantidad_m').focus();
    }
});

$(document).on("keydown", "#cantidad_m", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $('#lote_m').focus();
    }
});

$(document).on("keydown", "#lote_m", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        $('#btnRegistrarProd').click();
    }
});

// Registrar producto en entrada manual
$(document).on("click", "#btnRegistrarProd", function() {
    const tipo            = getUrlParameter('tipo');
    const consecutivo     = getUrlParameter('consecutivo');
    const idproducto      = $('#idproducto_m').val();
    const cantidad        = $('#cantidad_m').val();
    const valorUnitario   = $('#Valor_Unitario_m').val() || 0;
    const lote            = $('#lote_m').val() || '0';
    const porcImpuesto    = $('#porcentaje_impuesto_m').val() || '0';

    if (!idproducto || idproducto.trim() === '') {
        swal("Advertencia!", "Ingrese el código del producto", "warning");
        return;
    }
    if (!cantidad || parseFloat(cantidad) <= 0) {
        swal("Advertencia!", "Ingrese una cantidad válida", "warning");
        return;
    }

    $('#btnRegistrarProd').prop('disabled', true);

    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.insert_linea_entrada_manual,
        type: "POST",
        data: { tipo, consecutivo, idproducto, cantidad, valorUnitario, lote, porcentaje_impuesto: porcImpuesto },
        dataType: "json",
        success: function(resp) {
            if (resp.status === 'success') {
                $('#modalAgregarProd').modal('hide');
                listardetalle(tipo, consecutivo);
            } else {
                swal("Error!", resp.message, "error");
            }
        },
        error: function() {
            swal("Error!", "No se pudo agregar el producto. Verifique el código.", "error");
        },
        complete: function() {
            $('#btnRegistrarProd').prop('disabled', false);
        }
    });
});

init();