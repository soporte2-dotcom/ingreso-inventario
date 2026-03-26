var tabla;
var usu_id = $('#user_id').val();
var rol_id = $('#rol_id').val();

function init() {
    cargarTiposDocumento();
    inicializarEventos();
}

function cargarTiposDocumento() {
    $.post("../../controller/tipodoctos.php?op=doctos", function(data, status) {
        $('#idTipo').html(data);
        
        // Si hay un valor actual en el filtro, seleccionarlo
        let currentIdTipo = $('#current_idTipo').val();
        if (currentIdTipo) {
            $('#idTipo').val(currentIdTipo);
        }
    }).fail(function() {
        console.error("Error al cargar tipos de documento");
    });
}

function inicializarEventos() {
    // Evento para cambio de tipo de documento
    $("#idTipo").change(function() {
        let idTipo = $(this).val();
        if (idTipo) {
            $.post("../../controller/tipodoctos.php?op=consecutivos", 
                { idTipo: idTipo }, 
                function(data) {
                    try {
                        data = JSON.parse(data);
                        $("#consecutivo").val(data.consecutivo);
                    } catch (e) {
                        console.error("Error al parsear respuesta:", e);
                    }
                }
            ).fail(function() {
                console.error("Error al obtener consecutivo");
            });
        }
    });

    // Evento para el botón actualizar
    $(document).on("click", "#btnupdate", function(event) {
        event.preventDefault();
        actualizarLotesNotas();
    });

    // Evento para el formulario de consulta
    $("#doc_form").on("submit", function(e) {
        // Permitir el envío normal del formulario para la consulta
    });
}

function obtenerDatosActualizacion() {
    let lineasModificadas = [];
    let notaGeneral = $('#notas').val().trim();
    let idTipo = $('#current_idTipo').val() || $('#idTipo').val();
    let numdoc = $('#current_numdoc').val() || $('#numdoc').val();
    
    console.log("Nota General obtenida:", notaGeneral); // DEBUG
    console.log("ID Tipo:", idTipo); // DEBUG
    console.log("Número Doc:", numdoc); // DEBUG
    
    // Obtener registros de líneas modificadas
    $("#tb-doc tbody tr").each(function() {
        let row = $(this);
        let loteInput = row.find('.lote-input');
        let notalineaInput = row.find('.nota-linea-input');
        
        if (loteInput.length && notalineaInput.length) {
            let loteId = loteInput.attr('id').replace('lote_', '');
            let notalineaId = notalineaInput.attr('id').replace('nota_', '');
            
            if (loteId === notalineaId) {
                let [tipo, numeroDocumento, seq] = loteId.split('_');
                
                lineasModificadas.push({
                    tipo: tipo,
                    numeroDocumento: numeroDocumento,
                    seq: seq,
                    lote: loteInput.val().trim(),
                    nota_linea: notalineaInput.val().trim()  // CAMBIÉ notalinea a nota_linea
                });
            }
        }
    });
    
    console.log("Líneas modificadas:", lineasModificadas); // DEBUG
    
    return {
        lineas: lineasModificadas,
        notaGeneral: notaGeneral,
        idTipo: idTipo,
        numdoc: numdoc
    };
}

function actualizarLotesNotas() {
    let datosActualizacion = obtenerDatosActualizacion();
    
    if (datosActualizacion.lineas.length === 0 && !datosActualizacion.notaGeneral) {
        swal("Advertencia", "No hay cambios para actualizar", "warning");
        return;
    }

    // Mostrar resumen de lo que se va a actualizar
    let mensajeResumen = "Se actualizará:\n";
    if (datosActualizacion.notaGeneral) {
        mensajeResumen += "• Nota General del Documento\n";
    }
    if (datosActualizacion.lineas.length > 0) {
        mensajeResumen += `• ${datosActualizacion.lineas.length} línea(s) de producto\n`;
    }

    swal({
        title: "Confirmar Actualización",
        text: mensajeResumen,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Sí, actualizar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false  // CAMBIÉ a false para manejar manualmente
    }, function(confirm) {
        if (confirm) {
            ejecutarActualizacion(datosActualizacion);
        }
    });
}

function ejecutarActualizacion(datosActualizacion) {
    // DEBUG: Mostrar datos que se enviarán
    console.log("Datos a enviar al servidor:", datosActualizacion);
    
    // Mostrar mensaje de espera
    $.blockUI({ 
        message: '<h2><i class="fa fa-spinner fa-spin"></i> Actualizando, favor espere...</h2>',
        css: { 
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff' 
        } 
    });
    
    $.ajax({
        url: '../../controller/documento.php?op=update_lote_nota',
        type: 'POST',
        data: {
            lineas: JSON.stringify(datosActualizacion.lineas),
            notaGeneral: datosActualizacion.notaGeneral,
            idTipo: datosActualizacion.idTipo,
            numdoc: datosActualizacion.numdoc
        },
        success: function(response) {
            console.log("Respuesta del servidor:", response); // DEBUG
            
            try {
                let data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.status) {
                    swal({
                        title: "¡Éxito!", 
                        text: "Datos actualizados correctamente\n\n" +
                              (datosActualizacion.notaGeneral ? "• Nota General actualizada\n" : "") +
                              (datosActualizacion.lineas.length > 0 ? `• ${datosActualizacion.lineas.length} línea(s) actualizada(s)` : ""),
                        type: "success"
                    }, function() {
                        // Recargar la página para ver los cambios
                        location.reload();
                    });
                } else {
                    swal("Error", data.message || "No se pudieron actualizar los datos", "error");
                }
            } catch (e) {
                console.error('Error al procesar respuesta:', e, response);
                swal("Error", "Error al procesar la respuesta del servidor: " + e.message, "error");
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en AJAX:', error);
            swal("Error", "Error en la comunicación con el servidor: " + error, "error");
        },
        complete: function() {
            $.unblockUI();
        }
    });
}

// Función para obtener parámetros de URL
var getUrlParameter = function getUrlParameter(sParam) {
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
    return null;
};

$(document).ready(function() {
    init();
});