<?php

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

/**
 * @var Admin $this
 */

$subpage = $_GET['subpage'] ?? '';
$edit_link_id = (int) ( $_GET['edit_link'] ?? 0 )
?>

<style>
	<?= file_get_contents( plugin()->dir . '/assets/admin-page.css' ) ?>
</style>

<div class="wrap">
	<?php
	if( @ $this->msg ){
		$is_error = preg_match( '~error~i', $this->msg );
		echo '<div id="message" class="' . ( $is_error ? 'error' : 'updated' ) . '"><p>' . $this->msg . '</p></div>';
	}

	if( 'admin_menu' ){

		$subpage = $_GET['subpage'] ?? '';
		$edit_link = $_GET['edit_link'] ?? '';

		$edit_link = $edit_link
			? '<a class="nav-tab nav-tab-active" href="#">' . __( 'Link editing', 'kama-clic-counter' ) . '</a>'
			: '';

		$settings_link = sprintf(
			'<a class="nav-tab %s" href="%s">%s</a>',
			( ( $subpage === 'settings' ) ? 'nav-tab-active' : '' ),
			$this->admin_page_url( 'settings' ),
			__( 'Settings', 'kama-clic-counter' )
		);

		?>
		<h1 class="nav-tab-wrapper demenu">Kama Click Counter
			<small><code>v<?= plugin()->info['version'] ?></code></small>
			<br><br>

			<a class="nav-tab <?= ( ( ! $subpage && ! $edit_link ) ? 'nav-tab-active' : '' ) ?>" href="<?= $this->admin_page_url() ?>">
				<?= __( 'List', 'kama-clic-counter' ) ?>
			</a>
			<?= plugin()->admin_access ? $settings_link : '' ?>
			<?= $edit_link ?>
		</h1>
		<?php
	}

	// Options page
	if( 'settings' === $subpage && plugin()->admin_access ){
		require plugin()->dir . '/admin/pages/_options.php';
	}
	// edit link
	elseif( $edit_link_id ){
		require plugin()->dir . '/admin/pages/_edit-link.php';
	}
	// stat table
	else {
		require plugin()->dir . '/admin/pages/_table.php';
	}
	?>
</div><!-- wrap -->
