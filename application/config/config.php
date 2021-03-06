<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Base path of the web site. If this includes a domain, eg: localhost/kohana/
 * then a full URL will be used, eg: http://localhost/kohana/. If it only includes
 * the path, and a site_protocol is specified, the domain will be auto-detected.
 */
$config['site_domain'] = '/ninja/';

/**
 * Force a default protocol to be used by the site. If no site_protocol is
 * specified, then the current protocol is used, or when possible, only an
 * absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = '';

/**
 * Name of the front controller for this application. Default: index.php
 *
 * This can be removed by using URL rewriting.
 */
$config['index_page'] = 'index.php';

/**
* In case anyone would like to brand their installation
* This string is shown throughout the GUI in various places
* and this is the only place you will have to change it.
*/
$config['product_name'] = 'Ninja';

/**
 * Custom version info file. Format:
 * VERSION=x.y.z
 * This info will be visible in the 'product info' link
 */
$config['version_info'] = '/etc/ninja-release';

/**
 * Fake file extension that will be added to all generated URLs. Example: .html
 */
$config['url_suffix'] = '';

/**
 * Enable or disable gzip output compression. This can dramatically decrease
 * server bandwidth usage, at the cost of slightly higher CPU usage. Set to
 * the compression level (1-9) that you want to use, or FALSE to disable.
 *
 * Do not enable this option if you are using output compression in php.ini!
 */
$config['output_compression'] = FALSE;

/**
 * Enable or disable displaying of Kohana error pages. This will not affect
 * logging. Turning this off will disable ALL error pages.
 */
$config['display_errors'] = TRUE;

/**
 * Enable or disable statistics in the final output. Stats are replaced via
 * specific strings, such as {execution_time}.
 *
 * @see http://docs.kohanaphp.com/general/configuration
 */
$config['render_stats'] = false;

$config['autoload'] = array
(
		'libraries' => 'session, database'
);

/**
 * 	Base path to the location of Nagios.
 * 	This is used if we need to read some
 * 	configuration from the config files.
 * 	This path sare assumed to contain the
 * 	following subdirectories (unless specified below):
 * 		/bin
 * 		/etc
 * 		/var
 *
 * 	No trailing slash.
 */
$config['nagios_base_path'] = '/opt/monitor';

/**
 *	Path to Nagios command pipe (FIFO).
 */
$config['nagios_pipe'] = $config['nagios_base_path'].'/var/rw/nagios.cmd';

/**
 *	If the nagios etc directory is to be found outside
 * 	the nagios base path, please specify here.
 *
 * 	No trailing slash.
 */
$config['nagios_etc_path'] = false;

/**
 *	Path to where host logos as stored.
 *	Should be relative to webroot
 */
$config['logos_path'] = '/ninja/application/media/images/logos';

/**
 * current_skin is the subdirectory to 'css'. a skin a simple way of altering
 * colours etc in the gui.
 */
$config['current_skin'] = 'default/';

/**
 * Url to configuration interface for each table.
 */
$config['config_url'] = array();

/**
 * Do we use NACOMA (Nagios Configuration Manager)?
 * If path differs from the one below but still installed
 * you could simply change it.
 */
$nacoma_real_path = '/opt/monitor/op5/nacoma/';
if (is_dir($nacoma_real_path)) {
	$config['nacoma_path'] = '/monitor/op5/nacoma/';
	$config['config_url']['hosts'] = $config['site_domain'] . 'index.php/configuration/configure/?type=host&name=$HOSTNAME$';
	$config['config_url']['services'] = $config['site_domain'] . 'index.php/configuration/configure/?type=service&name=$HOSTNAME$&service=$SERVICEDESC$';
} else {
	$config['nacoma_path'] = false;
}

/**
 * Web path to Pnp4nagios
 * If installed, change path below or set to false if not
 */
$config['pnp4nagios_path'] = '/monitor/op5/pnp/';

/**
*	Path to the pnp config file 'config.php'
*	Only used if 'pnp4nagios_path' !== false
*/
$config['pnp4nagios_config_path'] = '/opt/monitor/etc/pnp/config.php';
if (!is_file($config['pnp4nagios_config_path'])) {
	$config['pnp4nagios_path'] = false;
	$config['pnp4nagios_config_path'] = false;
}

/**
 * Control command line access to Ninja
 * Possible values:
 * 	false 		: 	No commandline access
 * 	true		:	Second command line argument (i.e after path)
 * 					will be used as username (default)
 * 	'username'	:	The entered username will be used for authentication
 */
$config['cli_access'] = true;

/**
* Nr of items returned for searches
*/
$config['search_limit'] = 10;

/**
* Nr of items returned for autocomplete search
*/
$config['autocomplete_limit'] = 10;

/**
* Pop-up delay
* Milliseconds before the pop-up is shown
*/
$config['popup_delay'] = 150;

/**
 * How often do each listview refresh itself? 0 means it's disabled
 */
$config['listview_refresh_rate'] = 30;
