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
 * @since             1.0.6
 * @package           Geodirectorymemberpresspostlimit
 *
 * @wordpress-plugin
 * Plugin Name:       WP GeoDirectory MemberPress Limiter
 * Plugin URI:        https://www.strainovic-it.ch
 * Description:       Limit geoDirectory max. posts and image upload based on memberpress packages.
 * Version:           1.0.6
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
 * Get the html input for the custom field: images
 *
 * @param string $html The html to be filtered.
 * @param array $cf The custom field array details.
 * @since 1.6.6
 *
 * @return string The html to output for the custom field.
 */
function geodir_cfi_files2( $html, $cf ) {
    $html_var = $cf['htmlvar_name'];

    // we use the standard WP tags UI in backend
    if ( is_admin() && $html_var == 'post_images' ) {
        return '';
    }

    // Check if there is a custom field specific filter.
    if ( has_filter("geodir_custom_field_input_files_{$html_var}" ) ) {
        /**
         * Filter the multiselect html by individual custom field.
         *
         * @param string $html The html to filter.
         * @param array $cf The custom field array.
         * @since 1.6.6
         */
        $html = apply_filters("geodir_custom_field_input_files_{$html_var}", $html, $cf );
    }

	$html = "";

    // If no html then we run the standard output.
    if ( empty( $html ) ) {
        global $gd_post, $post;

        if ( empty( $gd_post ) && ! empty( $post ) ) {
            $gd_post = geodir_get_post_info( $post->ID );
        }

        ob_start(); // Start buffering;

        $horizontal = true;

        $extra_fields = maybe_unserialize( $cf['extra_fields'] );
        $file_limit = ! empty( $extra_fields ) && ! empty( $extra_fields['file_limit'] ) ? absint( $extra_fields['file_limit'] ) : 0;
        $file_limit = apply_filters( "geodir_custom_field_file_limit", $file_limit, $cf, $gd_post );

        $allowed_file_types = isset( $extra_fields['gd_file_types'] ) ? maybe_unserialize( $extra_fields['gd_file_types'] ) : array( 'jpg','jpe','jpeg','gif','png','bmp','ico','webp');
        $display_file_types = $allowed_file_types != '' ? '.' . implode( ", .", $allowed_file_types ) : '';
        if ( ! empty( $allowed_file_types ) ) {
            $allowed_file_types = implode( ",", $allowed_file_types );
        }

        // adjust values here
        $id = $cf['htmlvar_name']; // this will be the name of form field. Image url(s) will be submitted in $_POST using this key. So if $id == �img1� then $_POST[�img1�] will have all the image urls

        $revision_id = isset( $gd_post->post_parent ) && $gd_post->post_parent ? $gd_post->ID : '';
        $post_id = isset( $gd_post->post_parent ) && $gd_post->post_parent ? $gd_post->post_parent : $gd_post->ID;

        // check for any auto save temp media values first
        $temp_media = get_post_meta( $post_id, "__" . $revision_id, true );
        if ( ! empty( $temp_media ) && isset( $temp_media[ $html_var ] ) ) {
            $files = $temp_media[ $html_var ];
        } else {
            $files = GeoDir_Media::get_field_edit_string( $post_id, $html_var, $revision_id );
        }

        if ( ! empty( $files ) ) {
            $total_files = count( explode( '::', $files ) );
        } else {
            $total_files = 0;
        }

		
		$sundSPlusAbos = 'membership:8240,31384';
        
		$image_limit = current_user_can('mepr-active', $sundSPlusAbos) ? absint(3) : absint(8) ;
		
        $multiple = $image_limit == 1 ? false : true; // Allow multiple files upload
        $show_image_input_box = true;
        /**
         * Filter to be able to show/hide the image upload section of the add listing form.
         *
         * @since 1.0.0
         * @param bool $show_image_input_box Set true to show. Set false to not show.
         * @param string $listing_type The custom post type slug.
         */
        $show_image_input_box = apply_filters( 'geodir_file_uploader_on_add_listing', $show_image_input_box, $cf['post_type'] );

        if ( $show_image_input_box ) {

            // admin only
            $admin_only = geodir_cfi_admin_only($cf);
            ?>

            <div id="<?php echo $cf['name']; ?>_row" class="<?php if ( $cf['is_required'] ) {echo 'required_field';} ?> form-group row ">


                <label for="<?php echo $id; ?>" class="<?php echo $horizontal ? '  col-sm-2 col-form-label' : '';?>">
                    <?php $frontend_title = esc_attr__( $cf['frontend_title'], 'geodirectory' );
                    echo ( trim( $frontend_title ) ) ? $frontend_title : '&nbsp;'; echo $admin_only;?>
                    <?php if ( $cf['is_required'] ) {
                        echo '<span>*</span>';
                    } ?>
                </label>

                <?php

                if($horizontal){echo "<div class='col-sm-10'>";}
                echo class_exists("AUI_Component_Helper") ? AUI_Component_Helper::help_text(__( $cf['desc'], 'geodirectory' )) : '';
                if($horizontal){echo "</div>";}



                // params for file upload
                $is_required = $cf['is_required'];

                if($horizontal){echo "<div class='mx-3 w-100'>";}
                // the file upload template
                echo geodir_get_template_html( "bootstrap/file-upload.php", array(
                    'id'                  => $id,
                    'is_required'         => $is_required,
                    'files'	              => $files,
                    'image_limit'         => $image_limit,
                    'total_files'         => $total_files,
                    'allowed_file_types'  => $allowed_file_types,
                    'display_file_types'  => $display_file_types,
                    'multiple'            => $multiple,
                ) );
                if($horizontal){echo "</div>";}

                if ( $is_required ) { ?>
                    <span class="geodir_message_error"><?php esc_attr_e($cf['required_msg'], 'geodirectory'); ?></span>
                <?php } ?>
            </div>

            <?php
        }
        $html = ob_get_clean();
    }

    return $html;
}
// add_filter('geodir_custom_field_input_images','geodir_cfi_files',10,2);

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

			$posts_count = (int) GeoDir_Post_Limit::count_user_cpt_posts($params);

			// 	// Limit exceed.
			// 	if ($posts_limit <= $posts_count) {
			// 		$can_add = false;
			// 	}
			// } else if ($posts_limit < 0) {
			// 	$can_add = false; // Disabled from CPT
			// }
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

	add_filter('geodir_custom_field_input_images','geodir_cfi_files2',10,2);
	$gd_add_listing = '[gd_add_listing mapzoom="0" label_type="horizontal" show_login="1" mb="3"]';
	$args = array('post_type' => 'gd_place');
	$can_add_post = user_can_add_post2($args);


	$plusMitgliedschaft5WA = 'membership:31395,8258,31391,31363,31363,31360';
	$standardMitgliedschaft1WA = 'memberships:31393,31366,31389,8257,31382,8240';
	$mehrAls5WA = '<br><h3>Falls Sie mehr als 5x Waschanlagen benötigen, kontaktieren Sie uns und Sie erhalten ein Spezialangebot.</h3>';


	if (current_user_can('mepr-active', $plusMitgliedschaft5WA) and $can_add_post['posts_count'] < 5) {
		return do_shortcode($gd_add_listing); // Plus Abos unter 5x WA dürfen noch bis 5x WA erstellen
	} elseif (current_user_can('mepr-active', $plusMitgliedschaft5WA) and $can_add_post['posts_count'] === 5) {
		return ('<h3>Bitte kontaktieren Sie uns für ein Spezialangebot, um mehr als 5x Waschanlagen hinzuzufügen.</h3>');
	} elseif (current_user_can('mepr-active', $standardMitgliedschaft1WA) and $can_add_post['posts_count'] === 0) {
		return do_shortcode($gd_add_listing); // Standard ohne WA dürfen 1x WA erstellen
	} elseif (isset($_GET['pid'])) { 
		return do_shortcode($gd_add_listing); // bearbeiten immer möglich
	} elseif (current_user_can('mepr-active', $standardMitgliedschaft1WA) and $can_add_post['posts_count'] === 1) {
		return('<h3>Bitte erhöhen Sie Ihre Mitgliedschaft auf Plus um bis zu 5x Waschanlagen hinzuzufügen.</h3>'.$mehrAls5WA);
	} else {
		return('<h3>Bitte schliessen Sie eine Mitgliedschaft ab um eine Waschanlagen hinzuzufügen.</h3><br><h3>Mit einer Plus Mitgliedschaft können Sie bis zu 5x Waschanlagen hinzufügen.</h3>'.$mehrAls5WA);
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


// // register shortcode
add_shortcode('gd-wp-pl', 'run_shortcode');


function run_geodirectorymemberpresspostlimit()
{


	$plugin = new Geodirectorymemberpresspostlimit();
	$plugin->run();
}
run_geodirectorymemberpresspostlimit();
