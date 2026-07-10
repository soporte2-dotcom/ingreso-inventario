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

$(document).on('click', '#btnDesmarcar', function() {
    if (!documentoActual) return;

    swal({
        title: "¿Desmarcar documento?",
        text: `El documento ${documentoActual.tipoNombre} N° ${documentoActual.numero} pasará de Exportado (S) a No Exportado (N). Esta acción puede afectar reportes e integraciones que dependan de este estado.`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#f0ad4e",
        confirmButtonText: "Sí, desmarcar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(confirmado) {
        if (!confirmado) return;

        $.post(CONFIG.baseUrl + CONFIG.endpoints.desmarcar,
            { tipo: documentoActual.tipo, numero: documentoActual.numero },
            function(response) {
                if (response.status === 'success') {
                    swal({ title: "Correcto!", text: response.message, type: "success" }, function() {
                        documentoActual.exportado = 'N';
                        pintarDocumento(documentoActual);
                    });
                } else {
                    swal({ title: "Error!", text: response.message, type: "error" });
                }
            }, 'json');
    });
});

$(document).on('click', '#btnAnular', function() {
    if (!documentoActual) return;

    swal({
        title: "¿Anular documento?",
        text: `El documento ${documentoActual.tipoNombre} N° ${documentoActual.numero} quedará ANULADO. Esta acción es difícil de revertir desde este módulo.`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d9534f",
        confirmButtonText: "Sí, anular",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(confirmado) {
        if (!confirmado) return;

        $.post(CONFIG.baseUrl + CONFIG.endpoints.anular,
            { tipo: documentoActual.tipo, numero: documentoActual.numero },
            function(response) {
                if (response.status === 'success') {
                    swal({ title: "Correcto!", text: response.message, type: "success" }, function() {
                        documentoActual.anulado = 'S';
                        pintarDocumento(documentoActual);
                    });
                } else {
                    swal({ title: "Error!", text: response.message, type: "error" });
                }
            }, 'json');
    });
});
