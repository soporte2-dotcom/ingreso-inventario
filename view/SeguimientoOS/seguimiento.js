const CONFIG = {
    endpoint: "../../controller/seguimientoos.php?op=seguimiento_os"
};

let tabla = null;

function num(v, decimales) {
    const d = decimales === undefined ? 3 : decimales;
    return Number(v).toLocaleString('es-CO', { minimumFractionDigits: d, maximumFractionDigits: d });
}

// Escapa el texto: los nombres de producto y las notas vienen de la base y pueden
// traer caracteres que romperían el HTML de la tabla.
function txt(v) {
    if (v === null || v === undefined || v === '') return '';
    return $('<div>').text(v).html();
}

$(document).ready(function() {
    tabla = $('#tb-seguimiento').DataTable({
        data: [],
        paging: false,
        ordering: false,
        searching: true,
        info: false,
        columns: [
            {   // Columna de despliegue
                className: 'detalle-control',
                orderable: false,
                data: null,
                defaultContent: '+'
            },
            { data: 'linea' },
            { data: 'idProducto' },
            { data: 'producto', render: txt },
            { data: 'unidad', render: txt },
            { data: 'ordenado',   className: 'text-right', render: function(d) { return num(d); } },
            { data: 'despachado', className: 'text-right', render: function(d) { return num(d); } },
            { data: 'pendiente',  className: 'text-right', render: function(d) {
                  // El pendiente es el dato que se viene a buscar: se resalta.
                  if (d > 0)  return '<span class="badge badge-warning">' + num(d) + '</span>';
                  if (d < 0)  return '<span class="badge badge-danger">' + num(d) + '</span>';
                  return '<span class="text-muted">' + num(d) + '</span>';
              }},
            { data: 'movimientos', className: 'text-center', render: function(m) {
                  return m.length === 0
                      ? '<span class="text-muted">-</span>'
                      : '<span class="badge badge-info">' + m.length + '</span>';
              }}
        ],
        createdRow: function(row, data) {
            $(row).addClass(data.pendiente > 0 ? 'item-pendiente' : 'item-completo');
        },
        language: {
            emptyTable: "La orden no tiene ítems",
            zeroRecords: "Sin coincidencias",
            search: "Filtrar ítems:"
        }
    });

    // Desplegar / plegar los documentos de un ítem
    $('#tb-seguimiento tbody').on('click', 'td.detalle-control', function() {
        const tr  = $(this).closest('tr');
        const fila = tabla.row(tr);
        if (fila.child.isShown()) {
            fila.child.hide();
            $(this).text('+');
        } else {
            fila.child(tablaMovimientos(fila.data())).show();
            $(this).text('−');
        }
    });

    $('#btnBuscarOS').on('click', consultar);
    $('#numeroOS').on('keypress', function(e) { if (e.which === 13) consultar(); });
});

// Tabla anidada con los documentos donde se descontó el ítem.
function tablaMovimientos(item) {
    if (!item.movimientos || item.movimientos.length === 0) {
        return '<div class="text-muted" style="padding:8px">Este ítem todavía no se ha descontado en ningún documento.</div>';
    }

    let filas = '';
    let acumulado = 0;
    item.movimientos.forEach(function(m) {
        // Las devoluciones restan; se muestra el acumulado para poder seguir cómo se
        // llegó al descontado total del ítem.
        const signo = m.esDespacho ? 1 : -1;
        acumulado += signo * Number(m.cantidad);

        let estado = '';
        if (m.anulado === 'S')        estado += ' <span class="badge badge-danger">ANULADO</span>';
        if (m.exportado !== 'S')      estado += ' <span class="badge badge-warning">Sin guardar</span>';
        if (!m.esDespacho)            estado += ' <span class="badge badge-info">Devolución</span>';
        // El despacho no cuenta los documentos de otra bodega al calcular pendientes.
        if (m.otraBodega === 'S')     estado += ' <span class="badge badge-danger" title="El cálculo de pendientes del despacho ignora este documento">Bodega ' + txt(m.bodega) + '</span>';
        // Enlazado por coincidencia de líneas, no porque el documento se declare de esta OS.
        if (m.vinculo !== 'oficial')  estado += ' <span class="badge badge-warning" title="Tipo_Docto_Base_2 = ' + txt(m.tipoBase2) + ', no 10. No se suma al descontado.">Por contenido</span>';

        filas +=
            '<tr>' +
            '<td>' + txt(m.tipoNombre) + '</td>' +
            '<td>' + txt(m.numero) + '</td>' +
            '<td>' + txt(m.fecha) + '</td>' +
            '<td>' + txt(m.lote) + '</td>' +
            '<td class="text-right">' + (signo < 0 ? '−' : '') + num(m.cantidad) + '</td>' +
            '<td class="text-right">' + num(acumulado) + '</td>' +
            '<td>' + txt(m.usuario) + estado + '</td>' +
            '<td class="text-center">' +
                '<a href="../Salidas/index.php?tipo=' + encodeURIComponent(m.tipo) +
                '&consecutivo=' + encodeURIComponent(m.numero) + '" target="_blank" ' +
                'class="btn btn-xs btn-primary" title="Abrir el documento"><i class="fa fa-eye"></i></a>' +
            '</td>' +
            '</tr>';
    });

    return '<table class="tabla-movimientos">' +
        '<thead><tr>' +
        '<th>Tipo de documento</th><th>N°</th><th>Fecha</th><th>Lote</th>' +
        '<th class="text-right">Cantidad</th><th class="text-right">Acumulado</th>' +
        '<th>Usuario / Estado</th><th></th>' +
        '</tr></thead><tbody>' + filas + '</tbody></table>';
}

function consultar() {
    const numero = $('#numeroOS').val().trim();
    if (!numero) {
        swal("Atención", "Indique el número de la Orden de Salida.", "warning");
        return;
    }

    $('#btnBuscarOS').prop('disabled', true);
    $('#sinResultado').hide();

    $.post(CONFIG.endpoint, { numero: numero }, function(r) {
        $('#btnBuscarOS').prop('disabled', false);

        if (!r || r.status !== 'ok') {
            $('#resultadoOS').hide();
            $('#sinResultado').text((r && r.message) || 'No se pudo consultar la orden.').show();
            return;
        }

        $('#os_numero').text(r.os.numero);
        $('#os_fecha').text(r.os.fecha);
        $('#os_cliente').text((r.os.nit ? r.os.nit + ' - ' : '') + (r.os.cliente || ''));
        $('#os_bodega').text(r.os.bodega);
        $('#os_usuario').text(r.os.usuario);
        $('#os_notas').text(r.os.notas);
        $('#avisoOsAnulada').toggle(r.os.anulada === 'S');

        // Documentos que el cálculo de pendientes del despacho no ve.
        if (r.totales.movsOtraBodega > 0) {
            $('#avisoBodegaTexto').text(
                'esta orden tiene ' + r.totales.movsOtraBodega +
                ' movimiento(s) hechos desde una bodega distinta a la de la orden.');
            $('#avisoBodegaOs').text(r.totales.bodegaOs === '' ? '(sin bodega)' : r.totales.bodegaOs);
            $('#avisoBodega').show();
        } else {
            $('#avisoBodega').hide();
        }

        if (r.totales.movsPorContenido > 0) {
            $('#avisoContenidoNum').text(r.totales.movsPorContenido);
            $('#avisoContenido').show();
            $('#t_otros').text(num(r.totales.despachadoOtros));
            $('#fila_otros').show();
        } else {
            $('#avisoContenido').hide();
            $('#fila_otros').hide();
        }

        if (r.totales.docsSinEnlace > 0) {
            $('#avisoSinEnlaceNum').text(r.totales.docsSinEnlace);
            $('#avisoSinEnlace').show();
        } else {
            $('#avisoSinEnlace').hide();
        }

        $('#t_lineas').text(r.totales.lineas);
        $('#t_pendientes').text(r.totales.lineasPendientes);
        $('#t_documentos').text(r.totales.documentos);
        $('#t_ordenado').text(num(r.totales.ordenado));
        $('#t_despachado').text(num(r.totales.despachado));
        $('#t_pendiente').html(r.totales.pendiente > 0
            ? '<span class="badge badge-warning">' + num(r.totales.pendiente) + '</span>'
            : '<span class="badge badge-success">' + num(r.totales.pendiente) + '</span>');

        tabla.clear();
        tabla.rows.add(r.lineas);
        tabla.draw();

        $('#resultadoOS').show();
    }, 'json').fail(function() {
        $('#btnBuscarOS').prop('disabled', false);
        $('#sinResultado').text('No se pudo conectar con el servidor.').show();
        $('#resultadoOS').hide();
    });
}
