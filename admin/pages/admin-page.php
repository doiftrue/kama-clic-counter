<?php

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

$subpage = $_GET['subpage'] ?? '';
$edit_link_id = (int) ( $_GET['edit_link'] ?? 0 );
?>
<style>
	<?= file_get_contents( plugin()->dir . '/assets/admin-page.css' ) ?>
</style>

<div class="wrap">
	<?php
	$msg = plugin()->admin->admin_page->msg;
	if( $msg ){
		$is_error = preg_match( '~error~i', $msg );
		echo '<div id="message" class="' . ( $is_error ? 'error' : 'updated' ) . '"><p>' . $msg . '</p></div>';
	}

	require plugin()->dir . '/admin/pages/_admin-menu.php';

	if( 'settings' === $subpage && plugin()->admin_access ){
		require plugin()->dir . '/admin/pages/_options.php';
	}
	elseif( $edit_link_id ){
		require plugin()->dir . '/admin/pages/_edit-link.php';
	}
	else {
		require plugin()->dir . '/admin/pages/_table.php';
	}
	?>
</div><!-- wrap -->
