var tabla;
var usu_id =  $('#user_idx').val();
var rol_id =  $('#rol_idx').val();


function init(){   
   
    $("#doc_form").on("click","#btnguardar",function(e){
        guardar(e);	
    });
    
}

$(document).ready(function() {   
    
    $.post("../../controller/tipodoctos.php?op=combo",function(data, status){
       $('#idTipo').html(data);  
       console.log("tipo", data, estatus); 
    });

    $("#idTipo").change(function () {
        
        $("#idTipo option:selected").each(function () {
            idTipo = $(this).val();
            $.post("../../controller/tipodoctos.php?op=consecutivos", { idTipo : idTipo }, function(data){
                data = JSON.parse(data);
               $("#consecutivo").val(data.consecutivo);

               console.log(data.consecutivo);
            });            
        });
    });

    $("#nit").change(function () {
        
        $("#nit").each(function () {
            nit = $(this).val();
            $.post("../../controller/terceros.php?op=combo_dir", { nit : nit }, function(data){               
               $("#direccion").html(data);
            });
        });
    });

    $("#direccion").change(function () {
        
        $("#direccion option:selected").each(function () {
            direccion = $(this).val();
            $.post("../../controller/terceros.php?op=telefono_dir", { direccion : direccion}, function(data){
                data = JSON.parse(data);
               $("#telefono").val(data.telefono_1);
            });            
        });
    });

    var tipo =  getUrlParameter('tipo');
    var consecutivo =  getUrlParameter('consecutivo');
    listardetalle(tipo, consecutivo);
    
 

});

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
};

$(document).on("click","#btncrear", function(){

    let tipo = document.getElementById("idTipo").value;
    let consecutivo = document.getElementById("consecutivo").value;    
    let nit = document.getElementById("nit").value;
    let direccion = document.getElementById("direccion").value;   
    let formData = new FormData($("#doc_form")[0]);

    if ((tipo =='') || (consecutivo =='') || (nit =='') || (direccion =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
    }else{
        $.ajax({
            url: "../../controller/documento.php?op=insert_doc",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(datos){                  
                swal({
                    title: "Correcto!", 
                    text: "Documento Registrado Correctamente", 
                    type: "success"
                }, function(){
                    window.location.href = 'index.php?tipo='+ tipo +'&consecutivo='+ consecutivo; 
                });
                console.log(datos);
                
            }     
            

        });
    }
});

$("#numerodocumento").keypress(function(event) {
    if (event.keyCode === 13) {
        $("#btnagregar").click();
    }
});

$(document).on("click","#btnagregar", function(){
    
    var tipo =  getUrlParameter('tipo');
    var consecutivo =  getUrlParameter('consecutivo');
    let idproducto = document.getElementById("idproducto").value;
    let cantidad = document.getElementById("cantidad").value;

    var formData = new FormData($("#doc_form")[0]);

    if ((idproducto =='') || (cantidad =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
    }else{

        $.post("../../controller/productos.php?op=consultar", { idproducto : idproducto}, function(data){
            data = JSON.parse(data);
            console.log(data);
            if(data.producto == 0){
                swal("Advertencia!", "Producto No Existe", "warning");
            }else{

                $.ajax({
                    url: "../../controller/documento.php?op=insert_detalle",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(datos){ 

                        $('#cantidad').val('');
                        $('#idproducto').val('');
                        $('#producto').val('');
                        swal("Correcto!", "Registrado Correctamente", "success");
                        listardetalle(tipo, consecutivo);
                        console.log(datos);


                    }
                });
            }

        
        
        });
    } 
          
  
});


$(document).on("click","#btnguardar", function(){ 
    
    var formData = new FormData($("#doc_form")[0]);      
        $.ajax({
            url: "../../controller/documento.php?op=guardar_doc",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(datos){                  
                swal({
                    title: "Correcto!", 
                    text: "Documento Registrado Correctamente", 
                    type: "success"
                }, function(){
                    window.location.href = 'index.php'; 
                });
                console.log(datos);
                
            }        

        });   
    
});


function listardetalle(tipo, consecutivo){

    $.post("../../controller/documento.php?op=consultar_seq", { tipo : tipo, consecutivo : consecutivo }, function (data) {
        data = JSON.parse(data);
        console.log(data);
        $('#seq').val(data.seq);
    
    });

    $.post("../../controller/documento.php?op=mostrar", { tipo : tipo, consecutivo : consecutivo }, function (data) {
        data = JSON.parse(data);
        console.log(data);
        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#numdoc').val(data.Numero_documento);        
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#direcc').val(data.codigo_direccion);
        $('#direccion1').val(data.direccion);
        $('#telefono1').val(data.telefono_1);

    if(data !== null){

      document.getElementById("idTipo").style.display = "none";
      document.getElementById("consecutivo").style.display = "none";
      document.getElementById("fecha").style.display = "none";
      document.getElementById("nit").style.display = "none";
      document.getElementById("nombre").style.display = "none";
      document.getElementById("direccion").style.display = "none";
      document.getElementById("telefono").style.display = "none";

      document.getElementById("txt_idTipo").style.display = "none";
      document.getElementById("txt_consecutivo").style.display = "none";
      document.getElementById("txt_fecha").style.display = "none";
      document.getElementById("txt_nit").style.display = "none";
      document.getElementById("txt_nombre").style.display = "none";
      document.getElementById("txt_direccion").style.display = "none";
      document.getElementById("txt_telefono").style.display = "none";

      document.getElementById("btncrear").style.display = "none";

      document.getElementById("tipodoc").style.display = "inline-block";
      document.getElementById("numdoc").style.display = "inline-block";
      document.getElementById("fecha1").style.display = "inline-block";
      document.getElementById("nit1").style.display = "inline-block";
      document.getElementById("nombre1").style.display = "inline-block";
      document.getElementById("direccion1").style.display = "inline-block";
      document.getElementById("telefono1").style.display = "inline-block";

      document.getElementById("txt_tipodoc").style.display = "inline-block";
      document.getElementById("txt_numdoc").style.display = "inline-block";
      document.getElementById("txt_fecha1").style.display = "inline-block";
      document.getElementById("txt_nit1").style.display = "inline-block";
      document.getElementById("txt_nombre1").style.display = "inline-block";
      document.getElementById("txt_direccion1").style.display = "inline-block";
      document.getElementById("txt_telefono1").style.display = "inline-block";

      document.getElementById("agregar").style.display = "inline-block";
    }

      

    });
        
    tabla=$('#tb-doc').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "paging": false,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": false,
        lengthChange: false,
        colReorder: false,
        buttons: [		          
                
                ],
        "ajax":{
            url: '../../controller/documento.php?op=listar_detalle',
            type : "post",
            dataType : "json",	
            data:{ tipo : tipo, consecutivo : consecutivo },
            error: function(consecutivo){
                console.log(consecutivo.responseText);	
            }
            
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo":true,
        "iDisplayLength": 70,
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
    }).DataTable()
}





function eliminar(tipo, consecutivo, producto){
    swal({
        title: "Documento",
        text: "Esta seguro de Eliminar el registro?",
        type: "error",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Si",
        cancelButtonText: "No",
        closeOnConfirm: false
    },
    function(isConfirm) {
        if (isConfirm) {
            $.post("../../controller/documento.php?op=eliminar", {tipo : tipo, consecutivo : consecutivo, producto : producto}, function (data) {
                console.log(data);
            }); 

            swal({
                title: "Documento!",
                text: "Registro Eliminado.",
                type: "success",
                confirmButtonClass: "btn-success"
            });
            
            listardetalle(tipo, consecutivo);
            $('#tb-doc').DataTable().ajax.reload();
            
        }
    });
}


init();
