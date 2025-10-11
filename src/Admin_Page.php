<?php

namespace KamaClickCounter;

class Admin_Page {

	public string $msg = '';

	public function __construct() {
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, '_add_options_page' ] );
	}

	public function _add_options_page(): void {
		// just in case
		if( ! plugin()->manage_access ){
			return;
		}

		// open to everyone, it shouldn't come here if you can't access!
		$hookname = add_options_page(
			'Kama Click Counter',
			'Kama Click Counter',
			'read',
			plugin()->slug,
			static fn() => include plugin()->dir . '/admin/pages/admin-page.php'
		);

		add_action( "load-$hookname", [ $this, '_on_admin_page_load' ] );
	}

	public function _on_admin_page_load(): void {
		// just in case...
		if( ! plugin()->manage_access ){
			return;
		}

		$_nonce = $_REQUEST['_wpnonce'] ?? '';

		// save_options
		if( isset( $_POST['save_options'] ) ){
			check_admin_referer( 'save_options' );

			$this->handle_save_options();
		}
		// reset options
		elseif( isset( $_POST['reset'] ) ){
			check_admin_referer( 'save_options' );

			plugin()->opt->reset_to_defaults();
			$this->msg = __( 'Settings reseted to defaults', 'kama-clic-counter' );
		}
		// update_link
		elseif( isset( $_POST['update_link'] ) ){
			check_admin_referer( 'update_link' );

			$this->handle_update_link();
		}
		// bulk delete_links
		elseif( isset( $_POST['delete_link_ids'] ) ){
			check_admin_referer( 'bulk_action' );

			$this->msg = $this->delete_links( $_POST['delete_link_ids'] )
				? __( 'Selected objects deleted', 'kama-clic-counter' )
				: __( 'Nothing was deleted!', 'kama-clic-counter' );
		}
		// delete single link
		elseif( isset( $_GET['delete_link'] ) ){
			if( ! wp_verify_nonce( $_nonce, 'delete_link' ) ){
				$this->msg = 'error: nonce failed';
				return;
			}

			if( $this->delete_links( $_GET['delete_link'] ) ){
				wp_redirect( remove_query_arg( [ 'delete_link', '_wpnonce' ] ) );
			}
			else{
				$this->msg = __( 'Nothing was deleted!', 'kama-clic-counter' );
			}
		}
	}

	private function handle_save_options(): void {
		$POST = wp_unslash( $_POST );
		$new_options = [];
		foreach( plugin()->opt->get_def_options() as $key => $_ ){
			$new_options[ $key ] = $POST[ $key ] ?? plugin()->opt->$key;
		}

		$this->msg = plugin()->opt->update_option( $new_options )
			? __( 'Settings updated.', 'kama-clic-counter' )
			: __( 'Error: Failed to update the settings!', 'kama-clic-counter' );
	}

	private function handle_update_link(): void {
		$data = wp_unslash( $_POST['up'] );
		$id = (int) $data['link_id'];

		// sanitize
		foreach( $data as $key => & $val ){
			if( is_string( $val ) ){
				$val = trim( $val );
			}

			if( $key === 'link_url' ){
				$val = Counter::del_http_protocol( strip_tags( $val ) );
			}
			elseif( $key === 'link_description' ){
				$val = wp_kses_post( $val );
			}
			else{
				$val = sanitize_text_field( $val );
			}
		}
		unset( $val );

		$this->msg = $this->update_link( $id, $data )
			? __( 'Link updated!', 'kama-clic-counter' )
			: 'error: ' . __( 'Failed to update link!', 'kama-clic-counter' );
	}

	/**
	 * @return int|false
	 */
	private function update_link( int $link_id, array $data ) {
		global $wpdb;

		if( $link_id ){
			$query = $wpdb->update( $wpdb->kcc_clicks, $data, [ 'link_id' => $link_id ] );
		}

		$link_title = sanitize_text_field( $data['link_title'] );
		$link_description = sanitize_textarea_field( $data['link_description'] );

		// update the attachment, if any
		if( $data['attach_id'] > 0 ){
			$wpdb->update( $wpdb->posts,
				[ 'post_title' => $link_title, 'post_content' => $link_description ],
				[ 'ID' => (int) $data['attach_id'] ]
			);
		}

		return $query ?? false;
	}

	/**
	 * Deleting links from the database by passed array ID or link ID.
	 *
	 * @param array|int $array_ids  IDs of links to be deleted.
	 */
	private function delete_links( $array_ids = [] ): bool {
		global $wpdb;

		$array_ids = array_filter( array_map( 'intval', (array) $array_ids ) );

		if( ! $array_ids ){
			return false;
		}

		return $wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id IN (" . implode( ',', $array_ids ) . ")" );
	}

}
