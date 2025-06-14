<?php
namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

/**
 * @var Admin $this
 * @var int $edit_link_id
 */

global $wpdb;

$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->kcc_clicks WHERE link_id = %d", $edit_link_id ) );

if( ! $link ){
	echo '<br><br>';
	echo __( 'Link not found...', 'kama-clic-counter' );

	return;
}

?>
<br>
<p>
	<?php
	$referer = sanitize_text_field( $_POST['local_referer'] ?? preg_replace( '~https?://[^/]+~', '', $_SERVER['HTTP_REFERER'] ?? '' ) );

	if( $referer === remove_query_arg( 'edit_link', $_SERVER['REQUEST_URI'] ) ){
		$referer = '';
	}

	if( $referer ){
		echo '<a class="button" href="' . esc_url( $referer ) . '">← ' . __( 'Go back', 'kama-clic-counter' ) . '</a>';
	}
	?>
</p>

<form style="position:relative;width:900px;" method="post" action="">
	<?php wp_nonce_field('update_link'); ?>

	<input type="hidden" name="local_referer" value="<?= esc_attr( $referer ) ?>" />

	<img style="position:absolute; top:-10px; right:350px; width:50px;" src="<?= esc_attr( Helpers::get_icon_url( $link->link_url ) ) ?>" alt="" />
	<p>
		<input type="number" style="width:100px;" name="up[link_clicks]" value='<?= esc_attr( $link->link_clicks ) ?>' /> <?php printf( __('Clicks. Per day: %s', 'kama-clic-counter'), ($var=get_clicks_per_day($link)) ? $var : 0 ) ?></p>
	<p>
		<input type="text" style='width:100px;' name='up[file_size]' value='<?= esc_attr( $link->file_size ) ?>' /> <?php _e('File size', 'kama-clic-counter') ?>
	</p>
	<p>
		<input type="text" style='width:600px;' name='up[link_name]' value='<?= esc_attr( $link->link_name ) ?>' /> <?php _e('File name', 'kama-clic-counter') ?>
	</p>
	<p>
		<input type="text" style='width:600px;' name='up[link_title]' value='<?= esc_attr( $link->link_title ) ?>' /> <?php _e('File title', 'kama-clic-counter') ?>
	</p>
	<p>
		<textarea type="text" style='width:600px;height:70px;' name='up[link_description]' ><?= esc_textarea( stripslashes( $link->link_description ) ) ?></textarea> <?php _e('File description', 'kama-clic-counter') ?>
	</p>
	<p>
		<input type="text" style="width:600px;" name="up[link_url]" value="<?= esc_attr( $link->link_url ) ?>" readonly="readonly" />
		<a href="#" style="margin-top:.5em; font-size:110%;" class="dashicons dashicons-edit"
		   onclick="const $the = jQuery(this) $the.parent().find('input').removeAttr('readonly').focus(); $the.remove();"
		></a>
		<?php _e('Link to file', 'kama-clic-counter') ?>
	</p>
	<p>
		<input type="text" style="width:100px;" name="up[link_date]" value="<?= esc_attr( $link->link_date ) ?>" readonly="readonly" /> <a href="#" style="margin-top:.5em; font-size:110%;" class="dashicons dashicons-edit" onclick="var $the = jQuery(this); $the.parent().find('input').removeAttr('readonly').focus(); $the.remove();"></a> <?php _e('Date added', 'kama-clic-counter') ?>
	</p>

	<?php if( $this->opt->in_post ){ ?>
		<p>
			<input type="text" style="width:100px;" name="up[in_post]" value="<?= esc_attr( $link->in_post ) ?>" readonly="readonly" /> <a href="#" style="margin-top:.5em; font-size:110%;" class="dashicons dashicons-edit" onclick="var $the = jQuery(this); $the.parent().find('input').removeAttr('readonly').focus(); $the.remove();"></a> <?php _e('Post ID', 'kama-clic-counter') ?>
			<?php
			if( $link->in_post ){
				$cpost = get_post( $link->in_post );
				echo '. '. __( 'Current:', 'kama-clic-counter' ) . ( $cpost ? ' <a href="'. get_permalink($cpost) .'">'. esc_html( get_post($link->in_post)->post_title ) .'</a>' : ' - ' );
			}
			?>
		</p>
	<?php } ?>

	<input type="hidden" name="up[link_id]" value="<?= esc_attr( $edit_link_id ) ?>" />
	<input type="hidden" name="up[attach_id]" value="<?= esc_attr( $link->attach_id ) ?>" />

	<p style="margin-top: 3rem">
		<input type="submit" name="update_link" class="button-primary" value="<?= esc_attr__( 'Save changes', 'kama-clic-counter' ) ?>" />
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="button kcc-alert-button" href="<?= esc_url( $this->delete_link_url( $link->link_id ) ) ?>"
		   onclick="return confirm('<?= __('Sure to delete it?', 'kama-clic-counter') ?>');">
			<?= __('Delete', 'kama-clic-counter') ?>
		</a>
	</p>
</form>
