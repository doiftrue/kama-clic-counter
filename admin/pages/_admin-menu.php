<?php
namespace KamaClickCounter;

/**
 * @var Admin $this
 */

defined( 'ABSPATH' ) || exit;

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
	<small><code>v<?= plugin()->ver ?></code></small>
	<br><br>

	<a class="nav-tab <?= ( ( ! $subpage && ! $edit_link ) ? 'nav-tab-active' : '' ) ?>" href="<?= $this->admin_page_url() ?>">
		<?= __( 'List', 'kama-clic-counter' ) ?>
	</a>
	<?= plugin()->admin_access ? $settings_link : '' ?>
	<?= $edit_link ?>
</h1>
<?php
