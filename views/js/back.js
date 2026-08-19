/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
const module = "clientify"
// document.addEventListener('keyup', (event) => {
//     if (event.ctrlKey && event.altKey  && event.key == 'c') {
// 		 $('#other_config').show(1000)
//     }
// 	setTimeout(function(){ $('#other_config').hide(1000) }, 10000);
// });
jQuery(document).ready(function () {
	$("#CLIENTIFY_ORDER_STATUS").select2({
		maximumSelectionLength: 5
	  });
	var pathname = window.location.pathname;
	console.log(pathname)
	var btnconnect = $("#connect");
	var btndisconnect = $("#disconnect");
  	var classMessage = $('.clientify_message');	
	var connect = $('#connect');
	var disconnect = $('#disconnect');
	var apikey = $("#key").val();
	var status = $("#status_clientify").val();

		if (apikey !== '' && status == 1 ) {
				btnconnect.attr("disabled", true).addClass('clientify_connected').text("Conectado");        		
			}
		if (apikey !== '' && status == 0 ) {
				btndisconnect.attr("disabled", true).text("Desconectado");        		
			}	

	function floatLabel(inputType){
		$(inputType).each(function(){
			var $this = $(this);
			// Si el campo ya tiene valor al cargar, marcamos el label como activo
			if ($this.val() !== '' && $this.val() !== 'blank') {
				$this.next().addClass("clientify_active");
			}
			// on focus add class active to label
			$this.on('focus', function(){
				$this.next().addClass("clientify_active");
			});
			// on blur check field and remove ONLY the float-label class if needed
			$this.on('blur', function(){
				if($this.val() === '' || $this.val() === 'blank'){
					$this.next().removeClass("clientify_active");
				}
			});
		});
	}
	floatLabel(".clientify_floatLabel-prestashop");
	// just add a class of "floatLabel to the input field!"
	function statusMessage(message, status) {
		if (status === 'success') {
			if (typeof showSuccessMessage === 'function') {
				showSuccessMessage(message);
			} else if (typeof $.growl !== 'undefined') {
				$.growl.notice({ title: '', message: message });
			}
		} else {
			if (typeof showErrorMessage === 'function') {
				showErrorMessage(message);
			} else if (typeof $.growl !== 'undefined') {
				$.growl.error({ title: '', message: message });
			}
		}
	};

	connect.click(function() {
		var btnconnect = jQuery(this);
		var apikey = $("#key").val();
		var order_stat = $("#CLIENTIFY_ORDER_STATUS").val();
		var ac_time = $("#CLIENTIFY_CART_HOUR").val();
		var res = 0;
		btnconnect.attr("disabled", true).text(btnconnect.data("loading-text"));
		if($("#key").val() == ""){
        	statusMessage('Error de Conexión Clientify API Key Vacía','error');
        	$("#key").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
			btnconnect.removeAttr("disabled").text("Conectar").addClass('connect-class').removeClass('error');
        return false;
			}else{					
				$('#modal_shop').modal({ backdrop: 'static', keyboard: false })
				var id_shop = $("#CLIENTIFY_id_shop_sel").val()

				$('#CLIENTIFY_id_shop_sel').change(function() {	id_shop = $("#CLIENTIFY_id_shop_sel").val()	});
				$('#btn_shop_close').click(function() {	btnconnect.removeAttr("disabled").text("Conectar").addClass('connect-class').removeClass('error') });
				$('#btn_shop_accept').click(function() {
					$('#modal_shop').modal('hide');
					console.log('data : ' + id_shop)
					$.ajax({
						type: 'POST',
						dataType: 'JSON',
						timeout: 65000,
						url: decodeURIComponent(clientify_adminController).replace(/&amp;/g, '&'),
						data: {
							ajax: true,
							action: "connectClientify",
							'apikey': apikey,
							'order_stat': order_stat,
							'ac_time': ac_time,
							'id_shop':	id_shop,
						},
						error: function(xhr, status) {
							var msg = status === 'timeout'
								? 'Tiempo de espera agotado al conectar con Clientify'
								: 'Error de conexi\u00f3n con Clientify';
							statusMessage(msg, 'error');
							btnconnect.removeAttr("disabled").text("Conectar").addClass('connect-class').removeClass('error');
						},
						success: function(response) {

							res = response;
							console.log(res.data['status'])

								if (res.detail === "Invalid token.") {

									statusMessage('Error de conexión token','error');
									$("#key").focus();
									btnconnect.removeAttr("disabled").text("Conectar").addClass('connect-class').removeClass('error');

								}else {
									if (res === "null" || res === "" || res.data['status'] == "error" || res.data['status'] == "Invalid token." ||
                                    res.data['status'] == 'failed') {	

										if(res.data['status'] == "Invalid token."){
											statusMessage('Error Invalid Token','error')
											$("#key").focus();
										}else if(res.data['status'] == 'failed'){
                                            statusMessage('Error existe otro usuario con esta tienda','error')
											$("#key").focus();
                                        }else {
											statusMessage('Error de conexión Clientify','error')
											$("#key").focus();
										}

										btnconnect.removeAttr("disabled").text("Conectar").addClass('connect-class').removeClass('error');		
										
									}else {		
										if (res.data['status'] == 'success') {
											statusMessage('Conexión Clientify Exitosa','success');
											btnconnect.attr("disabled", true).text("Conectado").addClass('clientify_connected').removeClass('error');
											$('#key').focus();
											location.reload();
											var win = window.open('http://app.clientify.com/ecommerce/settingsv2/list-store/prestashop', '_blank');
											
										}
										if (response == '' || response == 0 ) {
											statusMessage('Error al conectar Clientify API Key Vacía','error');
											$("#key").focus();  // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
											return false;
										}		
								
									}
								}
						}
					});	
				});
			}		
  	});

	disconnect.click(function() {
		var form = $('#api-form-settings');
		var apikey = $("#key").val();
		var btndisconnect = jQuery(this);
		var res = 0;
		btndisconnect.attr("disabled", true).text("Desconectando");

		if($("#key").val() == ""){
        	statusMessage('Error al Conectar Clientify Api Key Vacía','error');
        	$("#key").focus();       // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
        return false;
    	}else{

			$.ajax({
				type: 'POST',
				dataType: 'JSON',
				url: decodeURIComponent(clientify_adminController).replace(/&amp;/g, '&'),
				data: {
					ajax: true,
					action: "disconnectClientify",
					'apikey': apikey,
				},
				success: function(response) {
						res = response;	
						console.log(res);
					if (res === null) {
						statusMessage('Error Al Desconectar Clientify','error');
						$("#key").focus();						
					}else{
						if (res.data['status'] == 'success') {
							statusMessage('Desconexión Clientify Exitosa','success');
							btndisconnect.attr("disabled", true).text("Desconectado");
							$("#key").focus();
							setTimeout(() => {  location.reload(); }, 3000);							
						}else{
							statusMessage('Error Al Desconectar Clientify','error');
							location.reload();
							$("#key").focus();  // Esta función coloca el foco de escritura del usuario en el campo Nombre directamente.
						}
					}		
				}
			});

		}
		
  	});
});