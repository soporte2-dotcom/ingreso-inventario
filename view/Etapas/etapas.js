// ── Configuración ────────────────────────────────────────────────────────────
const ETAPAS_URL = '../../controller/etapas.php';

// ── DataTable ─────────────────────────────────────────────────────────────────
var tablaEtapas;

$(document).ready(function () {
    tablaEtapas = $('#tbEtapas').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url:  ETAPAS_URL + '?op=listar',
            type: 'POST',
            data: function (d) {
                d.busqueda = $('#inputBusqueda').val().trim();
            }
        },
        columns: [
            { data: 0, className: 'text-center' },
            { data: 1 },
            { data: 2, className: 'text-center', orderable: false },
            { data: 3, className: 'text-center' },
            { data: 4, className: 'text-center', orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
        }
    });

    // Buscar al presionar Enter en el campo de búsqueda
    $('#inputBusqueda').on('keypress', function (e) {
        if (e.which === 13) buscar();
    });
});

// ── Búsqueda ──────────────────────────────────────────────────────────────────
function buscar() {
    tablaEtapas.ajax.reload();
}

function limpiarBusqueda() {
    $('#inputBusqueda').val('');
    tablaEtapas.ajax.reload();
}

// ── Modal Crear ───────────────────────────────────────────────────────────────
function abrirModalCrear() {
    $('#etapaId').val('');
    $('#etapaNombre').val('');
    $('#etapaEstado').val('1');
    $('#divEstado').hide();
    $('#modalEtapaTitle').text('Nueva Etapa');
    $('#btnGuardarEtapa').html('<i class="fa fa-save"></i> Guardar');
    $('#modalEtapa').modal('show');
    setTimeout(function () { $('#etapaNombre').focus(); }, 400);
}

// ── Modal Editar ──────────────────────────────────────────────────────────────
function abrirModalEditar(id) {
    $.ajax({
        url:      ETAPAS_URL + '?op=get_etapa&id=' + id,
        type:     'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.status !== 'success') {
                swal('Error', resp.message, 'error');
                return;
            }
            var e = resp.data;
            $('#etapaId').val(e.id);
            $('#etapaNombre').val(e.nombre);
            $('#etapaEstado').val(e.estado);
            $('#divEstado').show();
            $('#modalEtapaTitle').text('Editar Etapa #' + e.id);
            $('#btnGuardarEtapa').html('<i class="fa fa-save"></i> Actualizar');
            $('#modalEtapa').modal('show');
            setTimeout(function () { $('#etapaNombre').focus(); }, 400);
        },
        error: function () {
            swal('Error', 'No se pudo cargar la etapa.', 'error');
        }
    });
}

// ── Guardar (crear o editar) ──────────────────────────────────────────────────
function guardarEtapa() {
    var id     = $('#etapaId').val().trim();
    var nombre = $('#etapaNombre').val().trim();
    var estado = $('#etapaEstado').val();

    if (nombre === '') {
        swal('Advertencia', 'El nombre no puede estar vacío.', 'warning');
        $('#etapaNombre').focus();
        return;
    }

    var esEdicion = id !== '';
    var op        = esEdicion ? 'editar' : 'crear';
    var datos     = { nombre: nombre };
    if (esEdicion) {
        datos.id     = id;
        datos.estado = estado;
    }

    $('#btnGuardarEtapa').prop('disabled', true)
                         .html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url:      ETAPAS_URL + '?op=' + op,
        type:     'POST',
        data:     datos,
        dataType: 'json',
        success: function (resp) {
            $('#btnGuardarEtapa').prop('disabled', false)
                                 .html('<i class="fa fa-save"></i> ' + (esEdicion ? 'Actualizar' : 'Guardar'));
            if (resp.status === 'success') {
                swal('Correcto', resp.message, 'success');
                $('#modalEtapa').modal('hide');
                tablaEtapas.ajax.reload(null, false);
            } else {
                swal('Error', resp.message, 'error');
            }
        },
        error: function () {
            $('#btnGuardarEtapa').prop('disabled', false)
                                 .html('<i class="fa fa-save"></i> ' + (esEdicion ? 'Actualizar' : 'Guardar'));
            swal('Error', 'Error de comunicación con el servidor.', 'error');
        }
    });
}

// ── Desactivar (eliminación lógica) ──────────────────────────────────────────
function desactivarEtapa(id, nombre) {
    swal({
        title:              '¿Desactivar etapa?',
        text:               'Se desactivará la etapa <b>' + nombre + '</b>.',
        html:               true,
        type:               'warning',
        showCancelButton:   true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText:  'Sí, desactivar',
        cancelButtonText:   'Cancelar',
        closeOnConfirm:     false
    }, function (isConfirm) {
        if (!isConfirm) return;
        swal({ title: 'Procesando...', text: 'Por favor espere', type: 'info', showConfirmButton: false });

        $.ajax({
            url:      ETAPAS_URL + '?op=eliminar',
            type:     'POST',
            data:     { id: id },
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 'success') {
                    swal('Listo', resp.message, 'success');
                    tablaEtapas.ajax.reload(null, false);
                } else {
                    swal('Error', resp.message, 'error');
                }
            },
            error: function () {
                swal('Error', 'Error de comunicación con el servidor.', 'error');
            }
        });
    });
}
