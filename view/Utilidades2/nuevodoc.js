var tabla;
var usu_id =  $('#user_id').val();
var rol_id =  $('#rol_id').val();


function init(){   

}

$(document).ready(function() {    
    
    //Solo para documentos de entrada
   /* $.post("../../controller/tipodoctos.php?op=combo_entradas",function(data, status){
       $('#idTipo').html(data);    
    });*/

    //Para todos los tipos de documentos
    $.post("../../controller/tipodoctos.php?op=doctos",function(data, status){
        $('#idTipo').html(data);    
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

$(document).on("click","#btnupdate", function(event){

    event.preventDefault();       

    let tipo = document.getElementById("idTipo").value;
    let consecutivo = document.getElementById("consecutivo").value;    
    let numero = document.getElementById("numero").value;
    let formData = new FormData($("#doc_form")[0]);

    if ((tipo =='') || (consecutivo =='') || (numero =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
    }else{
         // Muestra el mensaje de espera
        $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });  
        $.ajax({
            url: "../../controller/documento.php?op=update_doc_ref",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(datos){   
                console.log(datos);               
                swal({
                    title: "Correcto!", 
                    text: "Documento Actualizado Correctamente", 
                    type: "success"
                }, function(){
                    location.reload();
                });
                                    
            },
            complete: function() {
                // Oculta el mensaje de espera
                $.unblockUI();
            }
        });
        
    }

    $("#btnupdate").prop('disabled',true)
    
    return false;
});





function listardetalle(tipo, consecutivo){

    $.post("../../controller/documento.php?op=mostrar_entrada", { tipo : tipo, consecutivo : consecutivo }, function (data) {
        data = JSON.parse(data);
        console.log(data);
        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#numdoc').val(data.Numero_documento);
        $('#pedido1').val(data.Numero_Docto_Base_2);
        $('#traslfact1').val(data.Numero_Docto_Base);        
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#direcc').val(data.codigo_direccion);
        $('#direccion1').val(data.direccion);
        $('#telefono1').val(data.telefono_1);
        $('#nit2').val(data.nit_Cedula_2);
        $('#nombre2').val(data.nombre2);
        $('#codigo_direccion2').val(data.codigo_direccion_2);
        $('#direccion2').val(data.direccion2);
        $('#sw').val(data.Tipo_Docto_Base_2);

        

    });
        
}


init();
