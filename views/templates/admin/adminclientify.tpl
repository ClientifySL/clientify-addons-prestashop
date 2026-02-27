{*
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
*}

<script type="text/javascript" src="/modules/clientify/views/js/back.js"></script>
<script type="text/javascript" src="/modules/clientify/views/js/select2.min.js"></script>

{* <div class="clientify_panel"> *}
<div class="clientify_conten">
	<header class="clientify_header">
		<img src="/modules/clientify/views/img/clientify.svg" alt="Clientify"
			class="clientify_logo-clientify-responsive">
		<!-- <h1>Clientify <span>with Forms</span></h1> -->
		<p>Gestiona y automatiza tu Marketing y Ventas fácilmente.</p>
	</header>
	<div class='general'>
		<input type="hidden" id="status_clientify" name="CLIENTIFY_STATUS"
			value="{{$data_config.clientify_module_status}}">
		<div class="clientify_form-group-prestashop">
			<!-- <h2 class="clientify_heading">Configuracion General</h2> -->
		</div>
	</div>
		<form id="api-form-settings" action="options.php" method="POST">

			<!-- Modal -->
			<div class="clientify_modal" id="modal_shop" tabindex="-1" role="dialog"
				aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="clientify_modal-dialog" role="document">
					<div class="clientify_modal-content">
						<div class="clientify_modal-header">
							<h5 class="clientify_modal-title" id="exampleModalLabel">Seleccione su tienda</h5>
							<button type="button" class="clientify_close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="clientify_modal-body">
							<select name="CLIENTIFY_id_shop_sel" id="CLIENTIFY_id_shop_sel" class="clientify_">
								{foreach key=data item=status from=$shops}
									<option label="{$status.name}" value="{$status.id_shop}">{$status.name}</option>
								{/foreach}
							</select>
						</div>
						<div class="clientify_modal-footer">
							<button type="button" class="clientify_btn btn-secondary" data-dismiss="modal"
								id="btn_shop_close">Cerrar</button>
							<button type="button" class="clientify_btn btn-primary" id="btn_shop_accept">Aceptar</button>
						</div>
					</div>
				</div>
			</div>
			<!--  General -->
			<div class="clientify_form-group-prestashop">
				<h2 class="clientify_heading">Configuracion General</h2>
				<div class="clientify_controls">
					<input type="text" id="key" class="clientify_floatLabel-prestashop" name="CLIENTIFY_API_KEY"
						value="{$data_config.clientify_api_key}">
					<label for="key">
						{l s='Clientify Api Key' mod='clientify	'}
					</label>
				</div>
				<div class="clientify_controls">
					<input type="text" id="storekey" class="clientify_floatLabel-prestashop" name="CLIENTIFY_STORE_KEY"
						value="{$data_config.clientify_store_key}" readonly>

					<label for="storekey" class="clientify_active_label">
						{l s='Store Key' mod='clientify'}
					</label>
					<div class="clientify_message"></div>
				</div>
			</div>
			<div class="clientify_form-group-prestashop clientify_hide_div" id="other_config">
				<h2 class="clientify_heading">Otras Configuraciones</h2>
				<div class="clientify_controls" style="margin-bottom: 4% !important;">
					<select name="CLIENTIFY_ORDER_STATUS" id="CLIENTIFY_ORDER_STATUS" multiple="multiple"
						class="clientify_floatLabel-prestashop">
						<option></option>
						{foreach key=data item=status from=$orderstatus}
							{$status_config = ","|explode:$data_config.clientify_order_status}
							<option label="{$status.name}" value="{$status.id_order_state}"
								{foreach $status_config as $id_status} 
									{if $status.id_order_state == $id_status} selected 
									{/if}
								{/foreach}>
								{$status.name}
							</option>
						{/foreach}
					</select>
					<label for="orderstatus"
						style="top: -20px !important;color: #555 !important;background-color: white !important;">
						{l s='Order Status' mod='clientify'}
					</label>
				</div>
				<div class="clientify_controls">
					{* {assign var="foo" value=shop::getShop(Configuration::get('CLIENTIFY_id_shop'))}
					<input type="text" id="CLIENTIFY_id_shop" class="clientify_floatLabel-prestashop" name="CLIENTIFY_id_shop" value="{$foo['name']}" readonly> *}
					<input type="text" id="CLIENTIFY_id_shop" class="clientify_floatLabel-prestashop"
						name="CLIENTIFY_id_shop" value="{$shop_config}" readonly>

					<label for="CLIENTIFY_id_shop">
						{l s='Tienda Conectada' mod='clientify'}
					</label>
				</div>
				<div class="clientify_controls">
					<input type="text" id="url" class="clientify_floatLabel-prestashop" name="URL_BASE" value="{$url_base}"
						readonly>
					<label for="url">
						{l s='API Url' mod='clientify'}
					</label>
				</div>

			</div>
			<!--  More -->

		</form>

		<div class='general'>
			<div class="clientify_form-group-prestashop">
				<button id="connect" class="clientify_connect-class" data-loading-text="Connecting"
					type="button">Conectar</button>
				<button id="disconnect" class="clientify_disconnect-class" type="button">Desconectar</button>
			</div>
		</div>



	<div class="clientify_sub_version">
		<p>Clientify E-commerce version 0.1.1</p>
	</div>
</div>

{* </div> *}
<link rel="stylesheet" href="/modules/clientify/views/css/back.css">
<link rel="stylesheet" href="/modules/clientify/views/css/select2.min.css">
{literal}
	<script type="text/javascript">
		let clientify_adminController = "{/literal}{$clientifyController|escape:'htmlall':'UTF-8'}{literal}";
	</script>
{/literal}