<?php

namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

/**
 * @var Admin $this
 */

global $wpdb;

// sanitize values
$_sortcols  = [ 'link_name', 'link_clicks', 'in_post', 'attach_id', 'link_date', 'last_click_date', 'downloads' ];
$order_by   = !empty($_GET['order_by']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['order_by']) : '';
$order_by   = in_array($order_by, $_sortcols) ? $order_by : 'link_date';
$order      = ( !empty($_GET['order']) && in_array( strtoupper($_GET['order']), array('ASC','DESC')) ) ? $_GET['order'] : 'DESC';
$paged      = !empty($_GET['paged']) ? intval($_GET['paged']) : 1;
$limit      = 20;
$offset     = ($paged-1) * $limit;
$search_query = isset($_GET['kcc_search']) ? trim( $_GET['kcc_search'] ) : '';

$_LIMIT    = 'LIMIT '. $wpdb->prepare("%d, %d", $offset, $limit ); // to insure
$_ORDER_BY = 'ORDER BY '. sprintf('%s %s', sanitize_key($order_by), sanitize_key($order) ); // to insure

if( $search_query ){
	// clear $_LIMIT if in original query there is no search query, or it differs from current search query
	if( $reff = &$_SERVER['HTTP_REFERER'] ){
		$reffdata = [];
		wp_parse_str( parse_url( $reff, PHP_URL_QUERY ), $reffdata );
		if( empty( $reffdata['kcc_search'] ) || $reffdata['kcc_search'] !== $search_query ){
			$_LIMIT = '';
		}
	}

	$search_query = wp_unslash( $search_query );
	$s = '%' . $wpdb->esc_like( $search_query ) . '%';
	$sql = $wpdb->prepare( "SELECT * FROM $wpdb->kcc_clicks WHERE link_url LIKE %s OR link_name LIKE %s $_ORDER_BY $_LIMIT", $s, $s );
}
else{
	$sql = "SELECT * FROM $wpdb->kcc_clicks $_ORDER_BY $_LIMIT";
}

$links = $wpdb->get_results( $sql );

if( ! $links ){
	$alert = __( 'Nothing found.', 'kama-clic-counter' );
}
else{
	$found_rows_sql = preg_replace( '~ORDER BY.*~i', '', $sql );
	$found_rows_sql = str_replace( 'SELECT *', 'SELECT count(*)', $found_rows_sql );

	$found_rows = $wpdb->get_var( $found_rows_sql );
}

?>

<form style="margin:2em 0;" class="kcc_search" action="" method="get">
	<?php
	foreach( $_GET as $key => $val ){
		if( $key === 'kcc_search' ){
			continue;
		}
		echo '<input type="hidden" name="' . sanitize_key( $key ) . '" value="' . esc_attr( $val ) . '" />';
	}
	?>
	<span style="color:#B4B4B4">
		<a href="<?= esc_url( remove_query_arg('kcc_search') ) ?>"><?php _e('Clear out the filter:', 'kama-clic-counter'); ?></a>
	</span>
	<input type="text" class="kcc_search_input" name="kcc_search" placeholder="<?php _e('type any part of URL...', 'kama-clic-counter'); ?>" value="<?= esc_attr( $search_query ) ?>" onfocus="window.kcc_search = this.value;" onfocusout="if(window.kcc_search != this.value) jQuery('.kcc_search').submit();" />
</form>


<?php
if( ! empty( $found_rows ) && $found_rows > $limit ){
	$urip = esc_url( preg_replace( '@&paged=[0-9]*@', '', $_SERVER['REQUEST_URI'] ) );

	echo '<div class="kcc_pagination">';
	echo "<a href='" . $urip . '&paged=' . ( $paged - 1 ) . "'>← " . __( 'Here', 'kama-clic-counter' ) . "</a>-<a href='" . $urip . '&paged=' . ( $paged + 1 ) . "'>" . __( 'There', 'kama-clic-counter' ) . " →</a>: ";

	for( $i = 1; $i < ( $found_rows / $limit ) + 1; $i++ ){
		echo '<a class="' . ( $paged == $i ? 'current' : '' ) . '" href="' . $urip . '&paged=' . $i . '">' . $i . '</a>';
	}

	echo '</div>';
}
?>


<form name="kcc_stat" method="post" action="">

	<?php wp_nonce_field( 'bulk_action' ); ?>

	<?php
	function _kcc_head_text( $text, $col_name ) {
		$_ord = $_GET['order'] ?? '';
		$order2 = ( $_ord === 'ASC' ) ? 'DESC' : 'ASC';
		$ind = ( $_ord === 'ASC' ) ? ' ▾' : ' ▴';

		$out = '
		<a href="' . esc_url( add_query_arg( [
				'order_by' => $col_name,
				'order'    => $order2,
			] ) ) . '" title="' . __( 'Sort', 'kama-clic-counter' ) . '">
			' . $text . ' ' . ( @ $_GET['order_by'] === $col_name ? $ind : '' ) . '
		</a>';

		return $out;
	}
	?>

	<table class="widefat kcc">
		<thead>
		<tr>
			<td class="check-column" style='width:30px;'><input type="checkbox" /></td>
			<th style='width:30px;'><!--img --></th>
			<th><?= _kcc_head_text( __('File', 'kama-clic-counter'), 'link_name')?></th>
			<th><?= _kcc_head_text( __('Clicks', 'kama-clic-counter'), 'link_clicks')?></th>
			<th><?php _e('Clicks/day', 'kama-clic-counter') ?></th>
			<th><?php _e('Size', 'kama-clic-counter') ?></th>
			<?php if($this->opt->in_post){ ?>
				<th><?= _kcc_head_text( __('Post', 'kama-clic-counter'), 'in_post')?></th>
			<?php } ?>
			<th><?= _kcc_head_text( __('Attach', 'kama-clic-counter'), 'attach_id')?></th>
			<th style="width:80px;"><?= _kcc_head_text( __('Added', 'kama-clic-counter'), 'link_date')?></th>
			<th style="width:80px;"><?= _kcc_head_text( __('Last click', 'kama-clic-counter'), 'last_click_date')?></th>
			<th><?= _kcc_head_text( 'DW', 'downloads') ?></th>
		</tr>
		</thead>

		<tbody id="the-list">
		<?php

		$i = 0;
		foreach( $links as $link ){
			$alt = ( ++$i % 2 ) ? 'class="alternate"' : '';

			$is_link_in_post   = ( $this->opt->in_post && $link->in_post );
			$in_post           = $is_link_in_post ? get_post( $link->in_post ) : 0;
			$in_post_permalink = $in_post ? get_permalink( $in_post->ID ) : '';

			$row_actions = array_filter( [
				sprintf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'edit_link', $link->link_id ) ), __('Edit', 'kama-clic-counter') ),
				$in_post
					? sprintf( '<a target="_blank" href="%s" title="%s">%s</a>', $in_post_permalink, esc_attr( $in_post->post_title ), __('Post', 'kama-clic-counter') )
					: '',
				sprintf( '<a href="%s">URL</a>', esc_url( $link->link_url ) ),
				sprintf( '<span class="trash"><a class="submitdelete" href="%s">%s</a></span>', esc_url( $this->delete_link_url( $link->link_id ) ), __('Delete', 'kama-clic-counter') ),
				sprintf( '<span style="color:#999;">%s</span>', esc_html( $link->link_title ) ),
			] );
			?>
			<tr <?= $alt?>>
				<th scope="row" class="check-column"><input type="checkbox" name="delete_link_ids[]" value="<?= intval($link->link_id) ?>" /></th>

				<td>
					<a href="<?= esc_url( $link->link_url ) ?>">
						<img title="<?= __('Link', 'kama-clic-counter') ?>" class="icon" src="<?= Helpers::get_icon_url( $link->link_url ) ?>" />
					</a>
				</td>

				<td style="padding-left:0;">
					<a href="<?= esc_url( add_query_arg('kcc_search', preg_replace('~.*/([^\.]+).*~', '$1', $link->link_url) ) ); ?>" title="<?php _e('Find similar', 'kama-clic-counter') ?>"><?= $link->link_name; ?></a>
					<?= $is_link_in_post ? '<small> — '. __('from post' , 'kama-clic-counter') . '</small>' : '' ?>
					<div class='row-actions'>
						<?= implode( ' | ', $row_actions ) ?>
					</div>
				</td>

				<td><?= $link->link_clicks ?></td>
				<td><?= get_clicks_per_day( $link ) ?></td>
				<td><?= $link->file_size ?></td>
				<?php if( $this->opt->in_post ){ ?>
					<td><?= ($link->in_post && $in_post) ? '<a href="'. $in_post_permalink .'" title="'. esc_attr( $in_post->post_title ) .'">'. $link->in_post .'</a>' : '' ?></td>
				<?php } ?>
				<td><?= $link->attach_id ? sprintf( '<a href="%s">%s</a>', admin_url( "post.php?post={$link->attach_id}&action=edit" ), $link->attach_id ) : '' ?></td>
				<td><?= $link->link_date ?></td>
				<td><?= $link->last_click_date ?></td>
				<td><?= $link->downloads ? __( 'yes', 'kama-clic-counter' ) : '' ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<p style="margin-top:7px;"><input type='submit' class='button' value='<?php _e('DELETE selected links', 'kama-clic-counter') ?>' /></p>

</form>
