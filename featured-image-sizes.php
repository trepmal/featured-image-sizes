<?php
/*
 * Plugin Name: Feature Image Sizes
 * Plugin URI: trepmal.com
 * Description:
 * Version:
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: featured-images-sizes
 * DomainPath:
 * Network:
 */

$featured_image_sizes = new Featured_Image_Sizes();

class Featured_Image_Sizes {

	var $textdomain = 'featured-image-sizes';

	function __construct() {

		global $_wp_post_type_features;
		$possible_post_types = wp_list_filter( $_wp_post_type_features, array( 'thumbnail' => 1 ) );

		$this->allowed_post_types = apply_filters( 'fis_post_types', $possible_post_types );

		add_filter( 'admin_post_thumbnail_html', array( &$this, 'admin_post_thumbnail_html' ), 10, 2 );
		add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );

		add_filter( 'post_thumbnail_size', array( &$this, 'post_thumbnail_size' ) );

	}

	function admin_post_thumbnail_html( $content, $post_id ) {

		$post_type = get_post_type( $post_id );
		if ( ! isset( $this->allowed_post_types[ $post_type ] ) ) return $content;

		ob_start();
		wp_nonce_field( 'na-fis-image', 'nn-fis-image' );
		$saved_value = get_post_meta( $post_id, 'fis-image-size', true );

		echo '<label>';
		_e( 'Choose a size for this image to be displayed at', $this->textdomain );
		echo '<select name="fis-image-size">';
		echo '<option value="">' . __( 'Depend on theme setting', $this->textdomain ) .'</option>';

		foreach( get_intermediate_image_sizes() as $size ) {
			$selected = selected( $size, $saved_value, false );
			echo "<option value='$size'$selected>$size</option>";
		}
		echo '</select></label>';

		$html = ob_get_clean();

		return $content . $html;
	}

	function save_post( $post_id, $post ) {

		if ( ! isset( $_POST['nn-fis-image'] ) ) //make sure our custom value is being sent
			return;
		if ( ! wp_verify_nonce( $_POST['nn-fis-image'], 'na-fis-image' ) ) //verify intent
			return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) //no auto saving
			return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) //verify permissions
			return;

		$fis_size = trim( $_POST['fis-image-size'] );
		if ( empty( $fis_size ) ) {
			delete_post_meta( $post_id, 'fis-image-size' );
			return;
		}

		// make sure the POSTed value is an okay size name
		$ok_sizes = get_intermediate_image_sizes();
		if ( ! in_array( $fis_size, $ok_sizes ) ) return;

		update_post_meta( $post_id, 'fis-image-size', $fis_size );

	}

	function post_thumbnail_size( $size ) {

		// make sure we're in the main loop
		// so as not to interfere with secondary loops (perhaps widgets)
		global $wp_query;
		if ( ! $wp_query->in_the_loop ) return $size;

		// var_dump( get_the_ID() );

		$fis_size = get_post_meta( get_the_ID(), 'fis-image-size', true );

		if ( empty( $fis_size ) ) return $size;

		return $fis_size;
	}

}