<?php

namespace KamaClickCounter;

class Admin {

	public Admin_Page $admin_page;

	public function __construct() {
		$this->admin_page = new Admin_Page();
	}

	public function init(): void {
		if( ! plugin()->manage_access ){
			return;
		}

		TinyMCE::init();

		$this->admin_page->init();

		add_action( 'delete_attachment', [ $this, 'delete_link_by_attach_id' ] );
		add_action( 'edit_attachment', [ $this, 'update_link_with_attach' ] );
		add_filter( 'plugin_action_links_' . plugin()->basename, [ $this, 'plugins_page_links' ] );
		add_action( 'current_screen', [ $this, 'upgrade' ] );
	}

	public function upgrade(): void {
		( new Upgrader() )->init();
	}

	/**
	 * Adds links to the statistics and settings pages from the plugins page.
	 * For WP hook.
	 */
	public function plugins_page_links( $actions ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', plugin()->admin->admin_page_url( 'settings' ), __( 'Settings', 'kama-clic-counter' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', plugin()->admin->admin_page_url(), __( 'Statistics', 'kama-clic-counter' ) );

		return $actions;
	}

	public function admin_page_url( $args = [] ): string {
		$url = admin_url( 'admin.php?page=' . plugin()->slug );

		if( $args ){
			$url = ( 'settings' === $args )
				? add_query_arg( [ 'subpage' => 'settings' ], $url )
				: add_query_arg( $args, $url );
		}

		return (string) $url;
	}

	public function delete_link_url( $link_id ): string {
		return add_query_arg( [ 'delete_link' => $link_id, '_wpnonce' => wp_create_nonce( 'delete_link' ) ] );
	}

	public function delete_link_by_attach_id( $attach_id ) {
		global $wpdb;
		if( ! $attach_id ){
			return false;
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->kcc_clicks WHERE attach_id = %d", $attach_id ) );
	}

	/**
	 * Update the link if the attachment is updated.
	 */
	public function update_link_with_attach( $attach_id ) {
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
