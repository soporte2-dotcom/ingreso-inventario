const CONFIG = {
    baseUrl: "../../controller/",
    endpoints: {
        consultar:   "auditoria.php?op=consultar",
        detalle:     "auditoria.php?op=detalle",
        operaciones: "auditoria.php?op=operaciones"
    }
};

let tabla = null;

$(document).ready(function() {
    // Por defecto se muestran los últimos 7 días: es el rango en el que suelen
    // reportarse los documentos con el detalle cambiado.
    const hoy = new Date();
    const hace7 = new Date();
    hace7.setDate(hoy.getDate() - 7);
    $('#fechaHasta').val(aISO(hoy));
    $('#fechaDesde').val(aISO(hace7));

    $.post(CONFIG.baseUrl + CONFIG.endpoints.operaciones, function(ops) {
        if (!Array.isArray(ops)) return;
        ops.forEach(function(op) {
            $('#operacion').append($('<option>').val(op).text(op));
        });
    }, 'json');

    tabla = $('#tb-auditoria').DataTable({
        data: [],
        order: [],            // el servidor ya devuelve por fecha descendente
        pageLength: 25,
        columnDefs: [
            { targets: [9], width: '30%' },
            { targets: [10], orderable: false, searchable: false }
        ],
        language: {
            emptyTable: "Sin movimientos registrados para estos filtros",
            zeroRecords: "Sin coincidencias",
            search: "Filtrar en pantalla:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_",
            infoEmpty: "Sin registros",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    consultar();
});

function aISO(d) {
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
}

function consultar() {
    const filtros = {
        fechaDesde:       $('#fechaDesde').val(),
        fechaHasta:       $('#fechaHasta').val(),
        tipo:             $('#tipo').val().trim(),
        numero:           $('#numero').val().trim(),
        usuario:          $('#usuario').val().trim(),
        operacion:        $('#operacion').val(),
        resultado:        $('#resultado').val(),
        soloDestructivas: $('#soloDestructivas').is(':checked') ? '1' : '0',
        soloConPerdida:   $('#soloConPerdida').is(':checked') ? '1' : '0'
    };

    $('#btnConsultar').prop('disabled', true).text('Consultando...');

    $.post(CONFIG.baseUrl + CONFIG.endpoints.consultar, filtros, function(resp) {
        $('#btnConsultar').prop('disabled', false).text('Consultar');

        if (resp && resp.status === 'error') {
            swal("Error!", resp.message, "error");
            return;
        }

        tabla.clear();
        if (resp && resp.aaData) tabla.rows.add(resp.aaData);
        tabla.draw();

        // El backend acota la consulta; si llegó al tope hay más movimientos sin mostrar
        // y el usuario debe estrechar el rango para no sacar conclusiones incompletas.
        if (resp && resp.tope) {
            $('#avisoTope')
                .text('Se muestran los ' + resp.tope + ' movimientos más recientes que cumplen el filtro. '
                    + 'Hay más registros: acote el rango de fechas o filtre por documento.')
                .show();
        } else {
            $('#avisoTope').hide();
        }
    }, 'json').fail(function() {
        $('#btnConsultar').prop('disabled', false).text('Consultar');
    });
}

$(document).on('click', '#btnConsultar', consultar);

$(document).on('click', '#btnLimpiar', function() {
    $('#tipo, #numero, #usuario').val('');
    $('#operacion, #resultado').val('');
    $('#soloDestructivas, #soloConPerdida').prop('checked', false);
    consultar();
});

// Enter en cualquier filtro dispara la consulta
$(document).on('keypress', '#tipo, #numero, #usuario', function(e) {
    if (e.which === 13) consultar();
});

$(document).on('click', '.btn-ver-detalle', function() {
    const id = $(this).data('id');

    $.post(CONFIG.baseUrl + CONFIG.endpoints.detalle, { id: id }, function(resp) {
        if (!resp || resp.status !== 'ok') {
            swal("Error!", (resp && resp.message) || "No se pudo cargar el detalle", "error");
            return;
        }

        const r = resp.registro;
        $('#da_resumen').html(
            '<b>' + r.operacion + '</b> sobre el documento <b>' + (r.tipo || '-') + ' N° ' + (r.numero || '-') + '</b><br>' +
            'Ejecutada por <b>' + (r.usuario || '-') + '</b> (' + (r.ip || 'sin IP') + ') el <b>' + r.fecha + '</b><br>' +
            'Líneas: ' + (r.lineas_antes === null ? '?' : r.lineas_antes) +
            ' &rarr; ' + (r.lineas_despues === null ? '?' : r.lineas_despues) +
            (r.mensaje ? '<br><i>' + $('<div>').text(r.mensaje).html() + '</i>' : '')
        );

        const lineas = (resp.detalle && resp.detalle.lineas) || [];
        const $tb = $('#da_lineas').empty();

        if (lineas.length === 0) {
            $tb.append('<tr><td colspan="8" class="text-center text-muted">Esta operación no guardó detalle de líneas</td></tr>');
        } else {
            lineas.forEach(function(l) {
                $tb.append(
                    '<tr>' +
                    '<td>' + txt(l.seq)   + '</td>' +
                    '<td>' + txt(l.prod)  + '</td>' +
                    '<td>' + txt(l.lote)  + '</td>' +
                    '<td>' + txt(l.cant)  + '</td>' +
                    '<td>' + txt(l.vlr)   + '</td>' +
                    '<td>' + txt(l.costo) + '</td>' +
                    '<td>' + txt(l.iva)   + '</td>' +
                    '<td>' + txt(l.nota)  + '</td>' +
                    '</tr>'
                );
            });
        }

        if (resp.detalle && resp.detalle.truncado) {
            $('#da_truncado')
                .text('El documento tenía ' + resp.detalle.total + ' líneas y solo se guardaron las primeras '
                    + lineas.length + '.')
                .show();
        } else {
            $('#da_truncado').hide();
        }

        $('#modalDetalleAuditoria').modal('show');
    }, 'json');
});

// Escapa el valor para que un lote o una nota con < > no rompa la tabla.
function txt(v) {
    if (v === null || v === undefined || v === '') return '<span class="text-muted">-</span>';
    return $('<div>').text(v).html();
}
