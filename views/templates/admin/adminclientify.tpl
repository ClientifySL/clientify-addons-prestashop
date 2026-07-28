{*
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
*}

<script type="text/javascript" src="{$module_dir}views/js/back.js"></script>
<script type="text/javascript" src="{$module_dir}views/js/select2.min.js"></script>

<div class="clientify_conten">

	{* ===== UPDATE BANNER ===== *}
	{if $update_available && $latest_release}
	<div class="alert alert-warning" id="clientify-update-banner" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
		<div>
			<strong><i class="material-icons" style="vertical-align:middle;font-size:18px;">system_update_alt</i>
			{l s='Nueva versión disponible' mod='clientify'}: <span style="font-weight:700;">v{$latest_release.version}</span></strong>
			<span style="color:#666;font-size:13px;margin-left:8px;">({l s='Instalada' mod='clientify'}: v{$current_version})</span>
		</div>
		<div style="display:flex;gap:8px;align-items:center;">
			<a href="{$latest_release.html_url|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default btn-sm">
				<i class="material-icons" style="font-size:14px;vertical-align:middle;">open_in_new</i>
				{l s='Ver cambios' mod='clientify'}
			</a>
			<button type="button" class="btn btn-primary btn-sm" id="clientify-btn-update">
				<i class="material-icons" style="font-size:14px;vertical-align:middle;">download</i>
				{l s='Actualizar ahora' mod='clientify'}
			</button>
		</div>
	</div>
	<div id="clientify-update-progress" style="display:none;" class="alert alert-info">
		<i class="material-icons" style="vertical-align:middle;animation:spin 1s linear infinite;">autorenew</i>
		{l s='Descargando e instalando actualización, por favor espera...' mod='clientify'}
	</div>
	<div id="clientify-update-result" style="display:none;"></div>
	<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
	<script>
	(function(){
		document.getElementById('clientify-btn-update').addEventListener('click', function(){
			if (!confirm('{l s='¿Deseas actualizar el módulo Clientify ahora? El sitio seguirá funcionando durante la actualización.' mod='clientify'}')) return;
			document.getElementById('clientify-update-banner').style.display   = 'none';
			document.getElementById('clientify-update-progress').style.display = 'block';

			fetch('{$clientifyController|escape:'javascript':'UTF-8'}&action=runUpdate&ajax=1', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			})
			.then(function(r){ return r.json(); })
			.then(function(data){
				document.getElementById('clientify-update-progress').style.display = 'none';
				var el = document.getElementById('clientify-update-result');
				if (data.success) {
					el.className = 'alert alert-success';
					el.innerHTML = '<i class="material-icons" style="vertical-align:middle;">check_circle</i> ' + data.message + ' <a href="" style="margin-left:8px;">{l s='Recargar página' mod='clientify'}</a>';
				} else {
					el.className = 'alert alert-danger';
					el.innerHTML = '<i class="material-icons" style="vertical-align:middle;">error</i> ' + data.message;
				}
				el.style.display = 'block';
			})
			.catch(function(){
				document.getElementById('clientify-update-progress').style.display = 'none';
				var el = document.getElementById('clientify-update-result');
				el.className = 'alert alert-danger';
				el.innerHTML = '{l s='Error de conexión al intentar actualizar.' mod='clientify'}';
				el.style.display = 'block';
			});
		});
	})();
	</script>
	{/if}

	<header class="clientify_header">
		<img src="{$module_dir}views/img/CL20horizontal.png" alt="Clientify"
			class="clientify_logo-clientify-responsive">
		<p>Gestiona y automatiza tu Marketing y Ventas fácilmente.</p>
	</header>

	<!-- Nav Tabs -->
	<nav class="clientify_nav-tab-wrapper">
		<a href="{$clientifyController|escape:'html':'UTF-8'}&amp;tab=settings"
			class="clientify_nav-tab{if $tab == 'settings'} clientify_nav-tab-active{/if}">
			<i class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:5px;">settings</i>
			{l s='Configuración' mod='clientify'}
		</a>
		<a href="{$clientifyController|escape:'html':'UTF-8'}&amp;tab=logs"
			class="clientify_nav-tab{if $tab == 'logs'} clientify_nav-tab-active{/if}">
			<i class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:5px;">list</i>
			{l s='Logs' mod='clientify'}
		</a>
	</nav>

	<div class="clientify_body">

		{* ===== TAB: SETTINGS ===== *}
		{if $tab == 'settings'}

			<div class='general'>
				<input type="hidden" id="status_clientify" name="CLIENTIFY_STATUS"
					value="{{$data_config.clientify_module_status}}">
			</div>

			<form id="api-form-settings" action="options.php" method="POST">

				<!-- Modal selección de tienda -->
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

				<!-- Configuración General -->
				<div class="clientify_form-group-prestashop">
					<h2 class="clientify_heading">{l s='Configuracion General' mod='clientify'}</h2>
					<div class="clientify_controls">
						<input type="text" id="key" class="clientify_floatLabel-prestashop" name="CLIENTIFY_API_KEY"
							value="{$data_config.clientify_api_key}">
						<label for="key" {if isset($data_config.clientify_api_key) && $data_config.clientify_api_key != ''}class="clientify_active"{/if}>
							{l s='Clientify Api Key' mod='clientify'}
						</label>
					</div>
					<div class="clientify_controls">
						<input type="text" id="storekey" class="clientify_floatLabel-prestashop" name="CLIENTIFY_STORE_KEY"
							value="{$data_config.clientify_store_key}" readonly>
						<label for="storekey" {if isset($data_config.clientify_store_key) && $data_config.clientify_store_key != ''}class="clientify_active"{/if}>
							{l s='Store Key' mod='clientify'}
						</label>
						<div class="clientify_message"></div>
					</div>
				</div>

				<!-- Otras Configuraciones -->
				<div class="clientify_form-group-prestashop" id="other_config">
					<h2 class="clientify_heading">{l s='Otras Configuraciones' mod='clientify'}</h2>
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
						<label for="orderstatus" class="clientify_active"
							style="top: -10px !important; left: 10px !important; padding: 0 6px !important; font-size: 14px !important; color: #555 !important; background-color: white !important;">
							{l s='Order Status' mod='clientify'}
						</label>
					</div>
					<div class="clientify_controls">
						<input type="text" id="CLIENTIFY_id_shop" class="clientify_floatLabel-prestashop"
							name="CLIENTIFY_id_shop" value="{$shop_config}" readonly>
						<label for="CLIENTIFY_id_shop" {if isset($shop_config) && $shop_config != ''}class="clientify_active"{/if}>
							{l s='Tienda Conectada' mod='clientify'}
						</label>
					</div>
					<div class="clientify_controls">
						<input type="text" id="url" class="clientify_floatLabel-prestashop" name="URL_BASE"
							value="{$url_base}" readonly>
						<label for="url" {if isset($url_base) && $url_base != ''}class="clientify_active"{/if}>
							{l s='Clientify Api Url' mod='clientify'}
						</label>
					</div>
				</div>

			</form>

			<div class='general'>
				<div class="clientify_form-group-prestashop clientify-button-wrapper">
					<button id="connect" class="clientify_connect-class" data-loading-text="Connecting"
						type="button">Conectar</button>
					<button id="disconnect" class="clientify_disconnect-class" type="button">Desconectar</button>
				</div>
			</div>

		{/if}

		{* ===== TAB: LOGS ===== *}
		{if $tab == 'logs'}
			<div class="clientify_logs-wrapper">
				<div class="clientify_logs-header">
					<h2 class="clientify_heading">{l s='Registro de actividad' mod='clientify'}</h2>
					<span class="clientify_logs-hint">
						<i class="material-icons" style="font-size:15px;vertical-align:middle;">schedule</i>
						{l s='Registros de los últimos 15 días' mod='clientify'}
					</span>
				</div>

				{if isset($clientify_logs) && $clientify_logs|@count > 0}
					<div class="clientify_logs-table-wrap">
						<table class="clientify_logs-table">
							<thead>
								<tr>
									<th class="col-date">{l s='Fecha' mod='clientify'}</th>
									<th class="col-type">{l s='Tipo' mod='clientify'}</th>
									<th class="col-msg">{l s='Mensaje' mod='clientify'}</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$clientify_logs item=log}
									<tr class="clientify_log-row clientify_log-{$log.type|escape:'html':'UTF-8'}">
										<td class="col-date">{$log.created_at|escape:'html':'UTF-8'}</td>
										<td class="col-type">
											{if $log.type == 'success'}
												<span class="clientify_badge clientify_badge-success">
													<i class="material-icons">check_circle</i>
													{l s='Éxito' mod='clientify'}
												</span>
											{elseif $log.type == 'error'}
												<span class="clientify_badge clientify_badge-error">
													<i class="material-icons">error</i>
													{l s='Error' mod='clientify'}
												</span>
											{elseif $log.type == 'warning'}
												<span class="clientify_badge clientify_badge-warning">
													<i class="material-icons">warning</i>
													{l s='Aviso' mod='clientify'}
												</span>
											{else}
												<span class="clientify_badge clientify_badge-info">
													<i class="material-icons">info</i>
													{l s='Info' mod='clientify'}
												</span>
											{/if}
										</td>
										<td class="col-msg">{$log.message|escape:'html':'UTF-8'}</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				{else}
					<div class="clientify_logs-empty">
						<i class="material-icons">inbox</i>
						<p>{l s='No hay registros de actividad todavía.' mod='clientify'}</p>
						<span>{l s='Los logs aparecerán aquí cuando conectes o desconectes el módulo.' mod='clientify'}</span>
					</div>
				{/if}
			</div>
		{/if}

	</div>{* /.clientify_body *}

	<div class="clientify_sub_version">
		<p>Clientify E-commerce version 1.0.0</p>
	</div>

</div>

<link rel="stylesheet" href="{$module_dir}views/css/back.css">
<link rel="stylesheet" href="{$module_dir}views/css/select2.min.css">
{literal}
	<script type="text/javascript">
		let clientify_adminController = "{/literal}{$clientifyController|escape:'htmlall':'UTF-8'}{literal}";
	</script>
{/literal}
