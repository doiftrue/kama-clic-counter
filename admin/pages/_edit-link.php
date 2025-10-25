<?php
namespace KamaClickCounter;

/**
 * @var int $edit_link_id
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->kcc_clicks WHERE link_id = %d", $edit_link_id ) );

if( ! $link ){
	echo '<br><br>';
	echo __( 'Link not found...', 'kama-clic-counter' );

	return;
}

?>
<style>
	.editlink__goback{ padding:1.5rem 0; }
	.editlinkform{ position:relative; width:900px; display:flex; flex-direction:column; gap:1.2em; }
	.editlinkform__img{ position:absolute; right:350px; width:50px; }
	.editlinkform__row{ display:flex; gap:.5em; align-items:center; }
	.editlinkform__row input, .editlinkform__row textarea{ width:min(40rem,70vw); }
	.editlinkform__editbtn{ position:absolute; margin-top:.5em; margin-left:-1.8em; cursor:pointer; opacity:0.5; }
</style>
<div class="editlink__goback">
	<?php
	$referer = sanitize_text_field( $_POST['local_referer'] ?? preg_replace( '~https?://[^/]+~', '', $_SERVER['HTTP_REFERER'] ?? '' ) );
	if( $referer === remove_query_arg( 'edit_link', $_SERVER['REQUEST_URI'] ) ){
		$referer = '';
	}

	if( $referer ){
		echo sprintf( '<a class="button" href="%s">← %s</a>', esc_url( $referer ), __( 'Go back', 'kama-clic-counter' ) );
	}
	?>
</div>

<form class="editlinkform" method="post" action="">
	<?php wp_nonce_field('update_link'); ?>
	<input type="hidden" name="local_referer" value="<?= esc_attr( $referer ) ?>" />

	<img class="editlinkform__img" src="<?= esc_attr( Helpers::get_icon_url( $link->link_url ) ) ?>" alt="" />

	<div class="editlinkform__row">
		<input type="number" style="width:10rem;" name="up[link_clicks]" value="<?= esc_attr( $link->link_clicks ) ?>"/>
		<?= __( 'All clicks', 'kama-clic-counter' ) ?>
	</div>
	<div class="editlinkform__row">
		<input type="number" style="width:10rem;" name="up[clicks_in_month]"
		       value="<?= esc_attr( $link->clicks_in_month ) ?>"/>
		<?= sprintf( __( 'Current month clicks — %s per day', 'kama-clic-counter' ), get_clicks_per_day( $link ) ?: 0 ) ?>
	</div>
	<div class="editlinkform__row">
		<input type="number" style="width:10rem;" name="up[clicks_prev_month]"
		       value="<?= esc_attr( $link->clicks_prev_month ) ?>"/>
		<?= __( 'Previous month clicks', 'kama-clic-counter' ) ?>
	</div>
	<div class="editlinkform__row">
		<textarea type="number" style="width:10rem;" name="up[clicks_history]" disabled><?= esc_textarea( $link->clicks_history ) ?></textarea>
		<?= __( 'Clicks history', 'kama-clic-counter' ) ?>
	</div>
	<div class="editlinkform__row">
		<input type="text" style='width:10rem;' name="up[file_size]" value='<?= esc_attr( $link->file_size ) ?>' /> <?= esc_html__('File size', 'kama-clic-counter') ?>
	</div>
	<div class="editlinkform__row">
		<input type="text" name="up[link_name]" value='<?= esc_attr( $link->link_name ) ?>' /> <?= esc_html__('File name', 'kama-clic-counter') ?>
	</div>
	<div class="editlinkform__row">
		<input type="text" name="up[link_title]" value='<?= esc_attr( $link->link_title ) ?>' /> <?= esc_html__('File title', 'kama-clic-counter') ?>
	</div>
	<div class="editlinkform__row">
		<textarea type="text" rows="4" name='up[link_description]' ><?= esc_textarea( stripslashes( $link->link_description ) ) ?></textarea> <?= esc_html__('File description', 'kama-clic-counter') ?>
	</div>
	<?php
	$edit_btn = <<<'HTML'
		<span class="editlinkform__editbtn" onclick="this.parentNode.querySelector('input').removeAttribute('readonly'); this.remove();">&#128393;</span>
		HTML;
	?>
	<div class="editlinkform__row">
		<div>
			<input type="text" name="up[link_url]" value="<?= esc_attr( $link->link_url ) ?>" readonly="readonly" />
			<?= $edit_btn ?>
		</div>
		<?= esc_html__('Link to file', 'kama-clic-counter') ?>
	</div>
	<div class="editlinkform__row">
		<div>
			<input type="text" style="width:10rem;" name="up[link_date]" value="<?= esc_attr( $link->link_date ) ?>" readonly="readonly" />
			<?= $edit_btn ?>
		</div>
		<?= esc_html__('Date added', 'kama-clic-counter') ?>
	</div>

	<?php if( plugin()->opt->in_post ){ ?>
		<div class="editlinkform__row">
			<div>
				<input type="text" style="width:10rem;" name="up[in_post]" value="<?= esc_attr( $link->in_post ) ?>" readonly="readonly" />
				<?= $edit_btn ?>
			</div>
			<?= esc_html__('Post ID', 'kama-clic-counter') ?>
			<?php
			if( $link->in_post ){
				$cpost = get_post( $link->in_post );
				echo $cpost
					? sprintf( ': <a href="%s" target="_blank">%s</a>', get_permalink( $cpost ), esc_html( get_post( $link->in_post )->post_title ) )
					: ' - ';
			}
			?>
		</div>
	<?php } ?>

	<input type="hidden" name="up[link_id]" value="<?= esc_attr( $edit_link_id ) ?>" />
	<input type="hidden" name="up[attach_id]" value="<?= esc_attr( $link->attach_id ) ?>" />

	<p style="margin-top: 3rem">
		<input type="submit" name="update_link" class="button-primary" value="<?= esc_attr__( 'Save changes', 'kama-clic-counter' ) ?>" />
		&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="button kcc-alert-button" href="<?= esc_url( plugin()->admin->delete_link_url( $link->link_id ) ) ?>"
		   onclick="return confirm('<?= __('Sure to delete it?', 'kama-clic-counter') ?>');">
			<?= __('Delete', 'kama-clic-counter') ?>
		</a>
	</p>
</form>
