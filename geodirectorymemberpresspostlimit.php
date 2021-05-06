<?php

// require_once 
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/geodirectory/includes/class-geodir-post-limit.php';;


/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.strainovic-it.ch
 * @since             1.0.1
 * @package           Geodirectorymemberpresspostlimit
 *
 * @wordpress-plugin
 * Plugin Name:       GeoDirectory MemberPress Post Limit
 * Plugin URI:        https://www.strainovic-it.ch
 * Description:       Beschränk die Anzahl Waschanlagen je nach Abo.
 * Version:           1.0.1
 * Author:            Goran Strainovic
 * Author URI:        https://www.strainovic-it.ch
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       geodirectorymemberpresspostlimit
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('GEODIRECTORYMEMBERPRESSPOSTLIMIT_VERSION', '1.0.1');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-geodirectorymemberpresspostlimit-activator.php
 */
function activate_geodirectorymemberpresspostlimit()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-geodirectorymemberpresspostlimit-activator.php';
	Geodirectorymemberpresspostlimit_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-geodirectorymemberpresspostlimit-deactivator.php
 */
function deactivate_geodirectorymemberpresspostlimit()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-geodirectorymemberpresspostlimit-deactivator.php';
	Geodirectorymemberpresspostlimit_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_geodirectorymemberpresspostlimit');
register_deactivation_hook(__FILE__, 'deactivate_geodirectorymemberpresspostlimit');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-geodirectorymemberpresspostlimit.php';




/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.1
 */

function user_can_add_post2($args = array())
{

	$defaults = array(
		'post_type' => '',
		'post_author' => null
	);

	$params = wp_parse_args($args, $defaults);

	$can_add = true;

	// Post author
	if ($params['post_author'] === null && is_user_logged_in()) {
		$params['post_author'] = (int) get_current_user_id();
	}

	if (!current_user_can('manage_options')) {
		if (!empty($params['post_author']) && !user_can((int) $params['post_author'], 'manage_options')) {
			$posts_limit = (int) GeoDir_Post_Limit::cpt_posts_limit($params['post_type'], $params['post_author']);

			if ($posts_limit > 0) {
				$posts_count = (int) GeoDir_Post_Limit::count_user_cpt_posts($params);

				// Limit exceed.
				if ($posts_limit <= $posts_count) {
					$can_add = false;
				}
			} else if ($posts_limit < 0) {
				$can_add = false; // Disabled from CPT
			}
		}
	}

	$return = [
		'posts_count' => $posts_count,
		'posts_limit' => $posts_limit,
		'can_add' => $can_add,
		'params' => $params,
		'args' => $args
	];

	return $return;
}

// function that runs when shortcode is called
function run_shortcode()
{

	$gd_add_listing = '[gd_add_listing mapzoom="0" label_type="horizontal" show_login="1" mb="3"]';
	$args = array('post_type' => 'gd_place');
	$can_add_post = user_can_add_post2($args);


	$plusMitgliedschaft5WA = 'membership:31395,8258,31391,31363,31363,31360';
	$standardMitgliedschaft1WA = 'memberships:31393,31366,31389,8257,31382,8240';
	$mehrAls5WA = '<br><h3>Falls Sie mehr als 5x Waschanlagen benötigen, kontaktieren Sie uns und Sie erhalten ein Spezialangebot.</h3>';

	if (current_user_can('mepr-active', $plusMitgliedschaft5WA) and $can_add_post['posts_count'] < 5) {
		return do_shortcode($gd_add_listing); // Plus Abos unter 5x WA dürfen noch bis 5x WA erstellen
	} elseif (current_user_can('mepr-active', $plusMitgliedschaft5WA) and $can_add_post['posts_count'] === 5) {
		return do_shortcode('<h3>Bitte kontaktieren Sie uns für ein Spezialangebot, um mehr als 5x Waschanlagen hinzuzufügen.</h3>');
	} elseif (current_user_can('mepr-active', $standardMitgliedschaft1WA) and $can_add_post['posts_count'] === 0) {
		return do_shortcode($gd_add_listing); // Standard ohne WA dürfen 1x WA erstellen
	} elseif (current_user_can('mepr-active', $standardMitgliedschaft1WA) and $can_add_post['posts_count'] === 1) {
		return do_shortcode('<h3>Bitte erhöhen Sie Ihre Mitgliedschaft auf Plus um bis zu 5x Waschanlagen hinzuzufügen.</h3>'.$mehrAls5WA);
	} else {
		return do_shortcode('<h3>Bitte schliessen Sie eine Mitgliedschaft ab um eine Waschanlagen hinzuzufügen.</h3><br><h3>Mit einer Plus Mitgliedschaft können Sie bis zu 5x Waschanlagen hinzufügen.</h3>'.$mehrAls5WA);
	}

}
// // register shortcode
add_shortcode('gd-wp-pl', 'run_shortcode');

function run_geodirectorymemberpresspostlimit()
{


	$plugin = new Geodirectorymemberpresspostlimit();
	$plugin->run();
}
run_geodirectorymemberpresspostlimit();
