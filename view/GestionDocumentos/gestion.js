const CONFIG = {
    baseUrl: "../../controller/",
    endpoints: {
        tipodoctos: "tipodoctos.php?op=doctos",
        buscar: "documento.php?op=buscar_documento_gestion",
        desmarcar: "documento.php?op=desmarcar_documento",
        anular: "documento.php?op=anular_documento"
    }
};

let documentoActual = null;

$(document).ready(function() {
    $.post(CONFIG.baseUrl + CONFIG.endpoints.tipodoctos, function(data) {
        $('#tipoDoc').html(data);
    });
});

function badgeEstado(valor, textoSi, textoNo) {
    if (valor === 'S') {
        return `<span class="badge badge-success">${textoSi}</span>`;
    }
    return `<span class="badge badge-secondary">${textoNo}</span>`;
}

function pintarDocumento(doc) {
    documentoActual = doc;

    $('#rd_tipo').text(doc.tipo + ' - ' + doc.tipoNombre);
    $('#rd_numero').text(doc.numero);
    $('#rd_fecha').text(doc.fecha || '-');
    $('#rd_usuario').text(doc.usuario || '-');
    $('#rd_nit').text(doc.nit || '-');
    $('#rd_nombre').text(doc.nombre || '-');
    $('#rd_notas').text(doc.notas || '-');
    $('#rd_exportado').html(badgeEstado(doc.exportado, 'Exportado (S)', 'No Exportado (N)'));
    $('#rd_anulado').html(badgeEstado(doc.anulado, 'Anulado (S)', 'Activo (N)'));

    $('#btnDesmarcar').prop('disabled', !(doc.exportado === 'S' && doc.anulado !== 'S'));
    $('#btnAnular').prop('disabled', doc.anulado === 'S');

    $('#resultadoDoc').show();
}

$(document).on('click', '#btnBuscarDoc', function() {
    const tipo = $('#tipoDoc').val();
    const numero = $('#numeroDoc').val().trim();

    if (!tipo) {
        swal("Advertencia!", "Seleccione el tipo de documento", "warning");
        return;
    }
    if (!numero) {
        swal("Advertencia!", "Ingrese el número de documento", "warning");
        return;
    }

    $('#resultadoDoc').hide();
    documentoActual = null;

    $.post(CONFIG.baseUrl + CONFIG.endpoints.buscar, { tipo: tipo, numero: numero }, function(response) {
        if (response.status !== 'ok') {
            swal("Error!", response.message || "No se pudo encontrar el documento.", "error");
            return;
        }
        pintarDocumento(response.documento);
    }, 'json');
});

function urlVerDocumento(doc) {
    // Pedidos está acotado a un idTipoDoctos puntual (948), no a una categoría.
    if (String(doc.tipo) === '948') {
        return '../Pedidos/index.php?tipo=' + doc.tipo + '&consecutivo=' + doc.numero;
    }
    switch (String(doc.categoria)) {
        case '99':
            return '../NuevoDoc/index.php?tipo=' + doc.tipo + '&consecutivo=' + doc.numero;
        case '12':
        case '3':
            return '../Entradas/index.php?tipo=' + doc.tipo + '&consecutivo=' + doc.numero;
        case '11':
        case '2':
            return '../Salidas/index.php?tipo=' + doc.tipo + '&consecutivo=' + doc.numero;
        default:
            return null;
    }
}

$(document).on('click', '#btnVerDocumento', function() {
    if (!documentoActual) return;

    const url = urlVerDocumento(documentoActual);
    if (!url) {
        swal("Advertencia!", "No se pudo determinar el módulo para ver este tipo de documento.", "warning");
        return;
    }
    window.location.href = url;
});

// Desmarcar pide motivo en una modal, igual que anular: es la operación que vuelve
// editable un documento ya impreso, y sin motivo no queda rastro de por qué se hizo.
$(document).on('click', '#btnDesmarcar', function() {
    if (!documentoActual) return;

    $('#motivoDesmarca').val('');
    $('#motivoDesmarcaDocInfo').text(
        `Documento: ${documentoActual.tipoNombre} N° ${documentoActual.numero}. Pasará de Exportado (S) a No Exportado (N).`
    );
    $('#modalMotivoDesmarca').modal('show');
});

$('#modalMotivoDesmarca').on('shown.bs.modal', function() {
    $('#motivoDesmarca').focus();
});

$(document).on('click', '#btnConfirmarDesmarca', function() {
    if (!documentoActual) return;

    const motivo = $('#motivoDesmarca').val().trim();
    if (!motivo) {
        swal("Advertencia!", "Debe indicar el motivo por el cual desmarca el documento.", "warning");
        return;
    }

    $.post(CONFIG.baseUrl + CONFIG.endpoints.desmarcar,
        { tipo: documentoActual.tipo, numero: documentoActual.numero, motivo: motivo },
        function(response) {
            if (response.status === 'success') {
                // Se espera a que la modal cierre antes del swal, para que los overlays de
                // Bootstrap y SweetAlert no choquen y dejen la pantalla bloqueada.
                $('#modalMotivoDesmarca').one('hidden.bs.modal', function() {
                    swal({ title: "Correcto!", text: response.message, type: "success" }, function() {
                        documentoActual.exportado = 'N';
                        if (response.notas !== undefined) documentoActual.notas = response.notas;
                        pintarDocumento(documentoActual);
                    });
                });
                $('#modalMotivoDesmarca').modal('hide');
            } else {
                swal({ title: "Error!", text: response.message, type: "error" });
            }
        }, 'json');
});

$(document).on('click', '#btnAnular', function() {
    if (!documentoActual) return;

    $('#motivoAnulacion').val('');
    $('#motivoAnulacionDocInfo').text(
        `Documento: ${documentoActual.tipoNombre} N° ${documentoActual.numero}. Esta acción es difícil de revertir desde este módulo.`
    );
    $('#modalMotivoAnulacion').modal('show');
});

$('#modalMotivoAnulacion').on('shown.bs.modal', function() {
    $('#motivoAnulacion').focus();
});

$(document).on('click', '#btnConfirmarAnulacion', function() {
    if (!documentoActual) return;

    const motivo = $('#motivoAnulacion').val().trim();
    if (!motivo) {
        swal("Advertencia!", "Debe indicar el motivo de la anulación.", "warning");
        return;
    }

    $.post(CONFIG.baseUrl + CONFIG.endpoints.anular,
        { tipo: documentoActual.tipo, numero: documentoActual.numero, motivo: motivo },
        function(response) {
            if (response.status === 'success') {
                // Espera a que la modal termine de cerrarse (transición CSS) antes de mostrar
                // el swal de éxito, para evitar que los overlays de Bootstrap y SweetAlert
                // choquen y dejen la pantalla bloqueada (mismo problema que ya vimos con swal
                // encadenado sin closeOnConfirm:false).
                $('#modalMotivoAnulacion').one('hidden.bs.modal', function() {
                    swal({ title: "Correcto!", text: response.message, type: "success" }, function() {
                        documentoActual.anulado = 'S';
                        if (response.notas !== undefined) documentoActual.notas = response.notas;
                        pintarDocumento(documentoActual);
                    });
                });
                $('#modalMotivoAnulacion').modal('hide');
            } else {
                swal({ title: "Error!", text: response.message, type: "error" });
            }
        }, 'json');
});
