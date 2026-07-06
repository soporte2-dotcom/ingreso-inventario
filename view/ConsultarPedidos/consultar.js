var tabla;

function init(){
    // Si quieres añadir algún botón para crear nuevo documento
    $("#btnNueva").click(function(){
        window.location.href = "../Pedidos/index.php";
    });
}

function formatoFecha(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function fijarFiltrosPorDefecto() {
    var hoy = new Date();
    var hace30 = new Date();
    hace30.setDate(hoy.getDate() - 30);

    $('#filtroFechaDesde').val(formatoFecha(hace30));
    $('#filtroFechaHasta').val(formatoFecha(hoy));
    $('#filtroNumDesde').val('');
    $('#filtroNumHasta').val('');
    $('#filtroTipo').val('');
    // Por defecto se muestran los documentos NO exportados (borradores sin guardar),
    // que es lo que se quiere detectar al entrar al módulo.
    $('#filtroExportado').val('N');
}

$(document).ready(function(){

    // Combo del tipo de documento de pedidos permitido para el usuario (mismo endpoint
    // que ya usa el formulario de creación de Pedidos).
    $.post("../../controller/permisos.php?op=combo_pedidos_permisos", function(data){
        console.log("combo_pedidos_permisos respuesta:", data);
        $('#filtroTipo').html('<option value="">Todos mis tipos permitidos</option>' + data);
    }).fail(function(xhr, status, error){
        console.error("Error cargando combo_pedidos_permisos:", status, error, xhr.responseText);
        swal("Error!", "No se pudo cargar el combo de tipos de documento.", "error");
    });

    fijarFiltrosPorDefecto();

    tabla = $('#doc_data').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": true,
        lengthChange: false,
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            {
                extend: 'pdfHtml5',
                title: 'Consulta de Pedidos',
                orientation: 'landscape', // Orientación apaisada para más columnas
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7] // Exportar todas las columnas excepto la de acciones
                }
            }
        ],
        "ajax":{
            url: '../../controller/documento.php?op=listar_pedidos',
            type : "post",
            dataType : "json",
            data: function(d) {
                d.tipo        = $('#filtroTipo').val();
                d.fechaDesde  = $('#filtroFechaDesde').val();
                d.fechaHasta  = $('#filtroFechaHasta').val();
                d.numDesde    = $('#filtroNumDesde').val();
                d.numHasta    = $('#filtroNumHasta').val();
                d.exportado   = $('#filtroExportado').val();
            },
            error: function(e){
                console.log(e.responseText);
                swal("Error!", "No se pudieron cargar los datos. Intente nuevamente.", "error");
            }
        },
        "columnDefs": [
            {
                "targets": [7], // Columna de exportado
                "className": "text-center"
            },
            {
                "targets": [8], // Columna de acciones
                "className": "text-center"
            }
        ],
        "order": [], // Quitar ordenamiento por defecto
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 10,
        "autoWidth": false,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfo":           "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando un total de 0 registros",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();

    $('#btnFiltrar').on('click', function(){
        tabla.ajax.reload();
    });

    $('#btnLimpiarFiltro').on('click', function(){
        fijarFiltrosPorDefecto();
        tabla.ajax.reload();
    });
});

init();
