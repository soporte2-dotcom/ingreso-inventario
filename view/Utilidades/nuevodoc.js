$(document).ready(function() {

    // Combo con todos los tipos de documento.
    $.post("../../controller/tipodoctos.php?op=doctos", function(data) {
        $('#idTipo').html(data);
    });

    // Evitar que el formulario haga un submit normal (recargaría la página y
    // borraría los filtros); la consulta y la actualización se hacen por AJAX.
    $('#doc_form').on('submit', function(event) {
        event.preventDefault();
        return false;
    });

    $(document).on('click', '#btnConsultar', function() {
        consultarUtilidades();
    });

    $(document).on('click', '#btnupdate', function(event) {
        event.preventDefault();
        actualizarUtilidades();
    });

});

// Consulta los documentos por Tipo/Fecha y repinta la tabla, sin tocar los
// filtros ni recargar la página (mismo criterio que Gestión de Documentos).
function consultarUtilidades(callback) {
    var idTipo = $('#idTipo').val() || '';
    var fecha1 = $('#fecha1').val() || '';

    // Sin filtros la consulta traería todos los documentos de la compañía.
    if (idTipo === '' || fecha1 === '') {
        swal("Advertencia!", "Seleccione el tipo de documento y la fecha.", "warning");
        return;
    }

    var $tbody = $('#tb-doc tbody');
    $tbody.empty();

    $.blockUI({ message: '<h2>Consultando documentos, favor espere...</h2>' });

    $.ajax({
        url: '../../controller/documento.php?op=listar_utilidades_doc_ref',
        type: 'POST',
        data: { idTipo: idTipo, fecha1: fecha1 },
        dataType: 'json',
        success: function(data) {
            if (!data.status || !data.data || data.data.length === 0) {
                $tbody.append($('<tr>').append($('<td colspan="6">').text('No se encontraron resultados.')));
                return;
            }

            data.data.forEach(function(row) {
                var $tr = $('<tr>');
                $tr.append($('<td>').text(row.tipoDoctos));
                $tr.append($('<td>').text(row.numero));
                $tr.append($('<td>').text(row.nombre));
                $tr.append($('<td>').text(row.fecha));

                // El tipo y el consecutivo viajan en data-* (antes se armaba un id
                // "tipo_numero" y se partía por "_", lo que se rompe si el tipo
                // trae guion bajo). data-original permite enviar sólo lo modificado.
                var $input = $('<input type="text" class="form-control js-docto-base" maxlength="50">')
                    .attr('data-tipo', row.tipo)
                    .attr('data-numero', row.numero)
                    .attr('data-original', row.numeroDoctoBase)
                    .val(row.numeroDoctoBase);
                $tr.append($('<td>').append($input));
                $tr.append($('<td>'));

                $tbody.append($tr);
            });

            if (typeof callback === 'function') {
                callback();
            }
        },
        error: function(jqXHR) {
            $tbody.empty().append($('<tr>').append($('<td colspan="6">').text('Error al consultar los documentos.')));
            if (jqXHR.status === 401) { return; } // lo maneja el interceptor global de sesión
            swal("Error", "Error al consultar los documentos.", "error");
        },
        complete: function() {
            $.unblockUI();
        }
    });
}

// Envía a la base de datos únicamente los números de documento base que el
// usuario cambió en pantalla.
function actualizarUtilidades() {

    var $inputs = $("#tb-doc tbody input.js-docto-base");

    if ($inputs.length === 0) {
        swal("Advertencia!", "Realice una consulta antes de actualizar.", "warning");
        return;
    }

    var registrosModificados = [];

    $inputs.each(function() {
        var $input = $(this);
        var valor = $.trim($input.val());
        var original = $.trim($input.attr('data-original') || '');

        if (valor === original) {
            return; // sin cambios, no se envía
        }

        registrosModificados.push({
            tipo: $input.attr('data-tipo'),
            numeroDocumento: $input.attr('data-numero'),
            numeroDoctoBase: valor
        });
    });

    if (registrosModificados.length === 0) {
        swal("Advertencia!", "No hay cambios por actualizar.", "warning");
        return;
    }

    var mensaje = registrosModificados.length === 1
        ? 'Actualizando 1 documento, favor espere...'
        : 'Actualizando ' + registrosModificados.length + ' documentos, favor espere...';

    $.blockUI({ message: '<h2>' + mensaje + '</h2>' });
    $("#btnupdate").prop('disabled', true);

    $.ajax({
        url: '../../controller/documento.php?op=update_doc_ref',
        type: 'POST',
        data: { registros: JSON.stringify(registrosModificados) },
        dataType: 'json',
        success: function(data) {
            if (data && data.status) {
                swal({
                    title: data.actualizados > 0 ? "¡Éxito!" : "Aviso",
                    type: data.actualizados > 0 ? "success" : "warning",
                    text: data.message || "Registros actualizados correctamente"
                }, function() {
                    // Se refresca la tabla con lo que quedó realmente en la base. El
                    // setTimeout deja cerrar el swal actual antes de que la consulta
                    // pueda abrir otro (SweetAlert v1 se traba si se encadenan).
                    setTimeout(consultarUtilidades, 300);
                });
            } else {
                swal("Error", (data && data.message) ? data.message : "No se pudieron actualizar los registros", "error");
            }
        },
        error: function(jqXHR) {
            if (jqXHR.status === 401) { return; } // lo maneja el interceptor global de sesión
            swal("Error", "Error en la comunicación con el servidor", "error");
        },
        complete: function() {
            $.unblockUI();
            $("#btnupdate").prop('disabled', false);
        }
    });
}
