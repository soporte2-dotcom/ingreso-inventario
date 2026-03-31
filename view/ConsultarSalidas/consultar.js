var tabla;

function init(){
    // Si quieres añadir algún botón para crear nueva salida
    $("#btnNueva").click(function(){
        window.location.href = "../Salidas/index.php";
    });
}

$(document).ready(function(){    
    
    tabla = $('#doc_data').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ordering": true, // Habilitar ordenamiento
        dom: 'Bfrtip',
        "searching": true,
        lengthChange: false,
        buttons: [		          
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            {
                extend: 'pdfHtml5',
                title: 'Consulta de Salida',
                orientation: 'landscape', // Orientación apaisada para más columnas
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7] // Exportar todas las columnas excepto la de acciones
                }
            }
        ],
        "ajax":{
            url: '../../controller/salidas.php?op=listar_salidas',
            type : "post",
            dataType : "json",						
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
        "order": [[0, "desc"]], // Ordenar por fecha descendente por defecto
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
            "sInfo":           "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
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
});

init();