<?php
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
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * 1.0.4: registra los hooks que faltan en instalaciones que vienen de 0.1.x
 * (actionCustomerAccountAdd), quita los hooks obsoletos y borra archivos antiguos.
 * La logica vive en Clientify::runMigrations() porque el auto-updater no ejecuta los
 * scripts de upgrade y la misma migracion se lanza tambien desde hookBackOfficeHeader.
 */
function upgrade_module_1_0_4($module)
{
    // Si en esta peticion sigue cargada la clase antigua, la migracion se completa
    // en la siguiente carga del back office (hookBackOfficeHeader).
    if (method_exists($module, 'runMigrations')) {
        return $module->runMigrations();
    }
    return true;
}
