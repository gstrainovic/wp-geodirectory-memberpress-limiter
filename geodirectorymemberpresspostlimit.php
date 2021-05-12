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

    /**
     * Flags a comment as reported. Won't flag a comment that has been flagged before and approved.
     * @param  int $id Comment id.
     * @return bool
     */
    function flag($id)
    {
		$result =  wp_set_comment_status($id,'approve');
		return $result;
        // return add_comment_meta($id, $this->pluginPrefix . '_reported', true, true);
    }

	function flagComment($id)
    {

        if (!flag($id)) {
            // This may happen when the comment has been reported once, but deemed ok by an admin, or
            // when something went wrong. Either way, we won't bother the visitor with that information
            // and we'll show the same message for both sucess and failed here by default.
            echo (getStrings()['report_failed']);
        }
        echo (getStrings()['report_success']);
    }


function trashComment($id)
{
	$comment_status = wp_get_comment_status($id);

	switch ($comment_status) {
		case 'approved':
			if (wp_trash_comment($id)) {
				echo('trashed');
			}
			break;
			case 'unapproved':
				if (wp_trash_comment($id)) {
					echo('trashed');
				}
				break;
		case 'trash':
			if (wp_untrash_comment($id)) {
				echo('untrashed');
			}
			break;
		default:
			echo('error');
	}
}


    /**
     * Sets all strings used by the plugin. Use the 'report_comments_strings' filter to modify them yourself.
     * @return string
     */
     function getStrings()
    {
        $strings = array(
            // Title for link in the menu.
            'menu_title' => __('Reported', 'waofcm-moderate'),
            // Title for the reported comments page.
            'page_title' => __('Reported comments', 'waofcm-moderate'),
            // Confirm dialog on front end for replace.
            'confirm_replace' => __('Are you sure you want to replace this comment? This action can not be undone!', 'waofcm-moderate'),
            // Confirm dialog on front end for reporting.
            'confirm_report' => __('Are you sure you want to report this comment?', 'waofcm-moderate'),
            // Message to show user after successfully reporting a comment.
            'report_success' => __('The comment has been reported', 'waofcm-moderate'),
            // Message to show user after reporting a comment has failed.
            'report_failed' => __('The comment has been reported', 'waofcm-moderate'),
            // Message to show user after successfully replacing a comment.
            'replace_success' => __('The comment text has been replaced', 'waofcm-moderate'),
            // Message to show user after replacing a comment has failed.
            'replace_failed' => __('The comment has already been replaced', 'waofcm-moderate'),
            // Text for the report link shown below each comment.
            'report' => __('Report', 'waofcm-moderate'),
            // Text for the trash link shown below each comment.
            'trash' => __('Trash', 'waofcm-moderate'),
            // Text for the replace link shown below each comment.
            'untrash' => __('Untrash', 'waofcm-moderate'),
            // Text for the replace link shown below each comment.
            'replace' => __('Replace', 'waofcm-moderate'),
            // Text in admin for link that deems the comment OK.
            'ignore_report' => __('Comment is ok', 'waofcm-moderate'),
            // Action of moving a comment in the trash.
            'trashing' => __('trashing', 'waofcm-moderate'),
            // Action of moving a comment out of the trash.
            'untrashing' => __('untrashing', 'waofcm-moderate'),
            // Error message
            'error' => __('an error occurred.', 'waofcm-moderate'),
            // Action while replacing a comment.
            'replacing' => __('replacing', 'waofcm-moderate'),
            // Action while reporting a comment.
            'reporting' => __('reporting', 'waofcm-moderate'),
            // Error message shown when a comment can't be found.
            'invalid_comment' => __('The comment does not exist', 'waofcm-moderate'),
            // Header for settings field.
            'settings_header' => __('Report Comments Settings', 'waofcm-moderate'),
            // Description for members only setting.
            'settings_members_only' => __('Only logged in users may report comments', 'waofcm-moderate')
        );

		return $strings;
	}





function frontendInit($comment)
{
	printModerateLinks($comment);
}

    /**
     * Constructs "report this comment" link.
     * @return string
     */
     function getReportLink($comment)
    {
        $id = $comment->comment_ID;
        $class = 'waofcm-moderate' . "-report";
		$link = '<a href=?approvecomment='.$id.'>Freigeben</a>';
        return $link;
    }

    /**
     * Constructs "trash this comment" link.
     * @return string
     */
     function getTrashLink($comment)
    {
        $id = $comment->comment_ID;
        $class = 'waofcm-moderate' . "-trash";
		$link = '<a href=?delcomment='.$id.'>Löschen</a>';
        return $link;
    }

    //check if the get variable exists
    if (isset($_GET['delcomment']))
    {
        trashComment($_GET['delcomment']);
    }

    if (isset($_GET['approvecomment']))
    {
        flagComment($_GET['approvecomment']);
    }

	

function printModerateLinks($comment)
{

	if (is_single()) {
		echo '<p class="waofcm-moderate-links">' . getReportLink($comment) . ' | ' . getTrashLink($comment) . '</p>';
	}
}

function commentForApproval()
{

	$comments = get_comments(array(
		'post_id'=>get_the_ID(), 
		'order'=>'ASC',
		'status' => 'hold'
		// 'include_unapproved' => array(is_user_logged_in() ? get_current_user_id() : wp_get_unapproved_comment_author_email())
	));

	$comment = $comments[0];

	if ( '0' == $comment->comment_approved ) {
		echo '<h2>Bitte Kommentar freigeben oder löschen</h2>';
		echo '<p>Nach der Freigabe können Sie auf das Kommentar auch Antworten</p>';
		$post_rating = geodir_get_comment_rating( $comment->comment_ID );
		echo '<div class="geodir-review-ratings mb-n2">'. geodir_get_rating_stars( $post_rating, $comment->comment_ID ) . '</div>';
		echo '<div class="comment-content comment card-body m-0">'.comment_text($comment->comment_ID).'</div>';
		frontendInit($comment);
		echo 'huhu';
	}




	// return do_shortcode(fn_do_shortcode($comment));



	// <div class="comment-content comment card-body m-0">
		// c;
	// </div>
}

// // register shortcode
add_shortcode('gd-wp-pl', 'run_shortcode');
add_shortcode('woafcm', 'commentForApproval');


function run_geodirectorymemberpresspostlimit()
{


	$plugin = new Geodirectorymemberpresspostlimit();
	$plugin->run();
}
run_geodirectorymemberpresspostlimit();
