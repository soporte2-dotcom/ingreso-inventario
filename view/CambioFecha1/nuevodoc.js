var tabla;

function init() {
}

$(document).ready(function() {

            tabla=$('#tb-doc').dataTable({
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
                    'pdfHtml5'
                    ],
                /*"ajax":{
                    url: '../../controller/documento.php?op=listar_documentos_fecha',
                    type : "post",
                    dataType : "json",						
                    error: function(e){
                        console.log(e.responseText);	
                    }
                },*/
                "bDestroy": true,
                "responsive": true,
                "bInfo":true,
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
                }     
            }).DataTable();
           
    

});


init();
