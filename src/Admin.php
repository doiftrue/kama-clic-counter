<?php

namespace KamaClickCounter;

class Admin {

	/** @var string */
	public $msg = '';

	/** @var Options */
	private $opt;

	public function __construct( $options ) {
		$this->opt = $options;
	}

	public function init() {

		if( ! plugin()->manage_access ){
			return;
		}

		TinyMCE::init();

		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		add_action( 'delete_attachment', [ $this, 'delete_link_by_attach_id' ] );
		add_action( 'edit_attachment', [ $this, 'update_link_with_attach' ] );

		add_filter( 'plugin_action_links_' . plugin()->basename, [ $this, 'plugins_page_links' ] );

		add_filter( 'current_screen', [ $this, 'upgrade' ] );
	}

	public function upgrade(){
		$upgrader = new Upgrader();
		$upgrader->init();
	}

	/**
	 * Adds links to the statistics and settings pages from the plugins page.
	 * For WP hook.
	 */
	public function plugins_page_links( $actions ){

		$actions[] = sprintf( '<a href="%s">%s</a>', $this->admin_page_url( 'settings' ), __( 'Settings', 'kama-clic-counter' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', $this->admin_page_url(), __( 'Statistics', 'kama-clic-counter' ) );

		return $actions;
	}

	public function admin_menu(){

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
			[ $this, 'options_page_output' ]
		);

		add_action( "load-$hookname", [ $this, 'admin_page_load' ] );
	}

	public function admin_page_load(){

		// just in case...
		if( ! plugin()->manage_access ){
			return;
		}

		$_nonce = $_REQUEST['_wpnonce'] ?? '';

		// save_options
		if( isset( $_POST['save_options'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'save_options' ) && check_admin_referer( 'save_options' ) ){
				$this->msg = 'error: nonce failed';

				return;
			}

			$_POST = wp_unslash( $_POST );

			// sanitize
			$opt = $this->opt->get_def_options();
			foreach( $opt as $key => & $val ){
				$val = $_POST[ $key ] ?? '';

				is_string( $val ) && $val = trim( $val );

				if( $key === 'download_tpl' ){} // no sanitize... wp_kses($val, 'post');
				elseif( $key === 'url_exclude_patterns' ){} // no sanitize...
				elseif( is_array( $val ) ){
					$val = array_map( 'sanitize_key', $val );
				}
				else{
					$val = sanitize_key( $val );
				}
			}
			unset( $val );

			if( $this->opt->update_option( $opt ) ){
				$this->msg = __( 'Settings updated.', 'kama-clic-counter' );
			}
			else{
				$this->msg = __( 'Error: Failed to update the settings!', 'kama-clic-counter' );
			}
		}
		// reset options
		elseif( isset( $_POST['reset'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'save_options' ) && check_admin_referer( 'save_options' ) ){
				$this->msg = 'error: nonce failed';

				return;
			}

			$this->opt->reset_to_defaults();
			$this->msg = __( 'Settings reseted to defaults', 'kama-clic-counter' );
		}
		// update_link
		elseif( isset( $_POST['update_link'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'update_link' ) && check_admin_referer( 'update_link' ) ){
				$this->msg = 'error: nonce failed';

				return;
			}

			$data = wp_unslash( $_POST['up'] );
			$id   = (int) $data['link_id'];

			// очистка
			foreach( $data as $key => & $val ){
				if( is_string( $val ) ){
					$val = trim( $val );
				}

				if( $key === 'link_url' ){
					$val = Counter::del_http_protocol( strip_tags( $val ) );
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
		// bulk delete_links
		elseif( isset( $_POST['delete_link_ids'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'bulk_action' ) && check_admin_referer( 'bulk_action' ) ){
				$this->msg = 'error: nonce failed';

				return;
			}

			if( $this->delete_links( $_POST['delete_link_ids'] ) ){
				$this->msg = __( 'Selected objects deleted', 'kama-clic-counter' );
			}
			else{
				$this->msg = __( 'Nothing was deleted!', 'kama-clic-counter' );
			}
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

	public function admin_page_url( $args = [] ) {

		$url = admin_url( 'admin.php?page=' . plugin()->slug );

		if( $args ){
			if( 'settings' === $args ){
				$url = add_query_arg( [ 'subpage' => 'settings' ], $url );
			}
			else {
				$url = add_query_arg( $args, $url );
			}
		}

		return $url;
	}

	/**
	 * Callback for {@see add_options_page()} function parameter.
	 */
	public function options_page_output(){
		include plugin()->dir . '/admin/pages/admin.php';
	}

	/**
	 * @param int   $link_id
	 * @param array $data
	 *
	 * @return int|false
	 */
	private function update_link( $link_id, $data ) {
		global $wpdb;

		$link_id = (int) $link_id;
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

	public function delete_link_url( $link_id ): string {
		return add_query_arg( [ 'delete_link' => $link_id, '_wpnonce' =>wp_create_nonce('delete_link') ] );
	}

	/**
	 * Deleting links from the database by passed array ID or link ID.
	 *
	 * @param  array|int $array_ids IDs of links to be deleted.
	 */
	private function delete_links( $array_ids = [] ): bool {
		global $wpdb;

		$array_ids = array_filter( array_map( 'intval', (array) $array_ids ) );

		if( ! $array_ids ){
			return false;
		}

		return $wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id IN (" . implode( ',', $array_ids ) . ")" );
	}

	public function delete_link_by_attach_id( $attach_id ){
		global $wpdb;

		if( ! $attach_id ){
			return false;
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->kcc_clicks WHERE attach_id = %d", $attach_id ) );
	}

	/**
	 * Update the link if the attachment is updated.
	 */
	public function update_link_with_attach( $attach_id ){
		global $wpdb;

		$attdata = get_post( $attach_id );

		$new_data = wp_unslash( [
			'link_description' => $attdata->post_content,
			'link_title'       => $attdata->post_title,
			'link_date'        => $attdata->post_date,
		] );

		return $wpdb->update( $wpdb->kcc_clicks, $new_data, [ 'attach_id' => $attach_id ] );
	}

}
