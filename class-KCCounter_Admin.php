<?php


class KCCounter_Admin extends KCCounter {

	function __construct(){

		parent::__construct();

		// no access
		if( ! $this->admin_access )
			return;

		require KCC_PATH . 'admin/admin-functions.php';

		add_action( 'admin_menu',        [ $this, 'admin_menu' ] );

		add_action( 'delete_attachment', [ $this, 'delete_link_by_attach_id' ] );
		add_action( 'edit_attachment',   [ $this, 'update_link_with_attach' ] );

		add_filter( 'plugin_action_links_'. plugin_basename(__FILE__), [ $this, 'plugins_page_links' ] );

		add_filter( 'current_screen', [ $this, 'upgrade' ] );

		include KCC_PATH . 'admin/mce/mce.php';
	}

	function upgrade(){
		require_once KCC_PATH . 'admin/upgrade.php';

		kccount_upgrade_init();
	}

	# Ссылки на страницы статистики и настроек со страницы плагинов
	function plugins_page_links( $actions ){

		$actions[] = '<a href="admin.php?page=' . KCC_NAME . '&options">' . __( 'Settings', 'kama-clic-counter' ) . '</a>';
		$actions[] = '<a href="admin.php?page=' . KCC_NAME . '">' . __( 'Statistics', 'kama-clic-counter' ) . '</a>';

		return $actions;
	}

	function admin_menu(){

		if( ! $this->admin_access ) return; // just in case

		// открываем для всех, сюда не должно доходить, если нет доступа!....
		$hookname = add_options_page( 'Kama Click Counter', 'Kama Click Counter', 'read', KCC_NAME, [
			$this,
			'options_page_output',
		] );

		add_action( "load-$hookname", [ $this, 'options_page_load' ] );
	}

	function options_page_load(){

		// just in case...
		if( ! $this->admin_access )
			return;

		$_nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

		// save_options
		if( isset( $_POST['save_options'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'save_options' ) && check_admin_referer( 'save_options' ) )
				return $this->msg = 'error: nonce failed';

			$_POST = wp_unslash( $_POST );

			// очистка
			$opt = $this->get_def_options();
			foreach( $opt as $key => & $val ){
				$val = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';

				is_string( $val ) && $val = trim( $val );

				if( $key === 'download_tpl' ){} // no sanitize... wp_kses($val, 'post');
				elseif( $key === 'url_exclude_patterns' ){} // no sanitize...
				elseif( is_array($val) )
					$val = array_map( 'sanitize_key', $val );
				else
					$val = sanitize_key( $val );
			}
			unset( $val );

			update_option( self::OPT_NAME, $opt );

			if( $this->opt = get_option( self::OPT_NAME ) )
				$this->msg = __('Settings updated.', 'kama-clic-counter');
			else
				$this->msg = __('Error: Failed to update the settings!', 'kama-clic-counter');
		}
		// reset options
		elseif( isset($_POST['reset']) ){

			if( ! wp_verify_nonce( $_nonce, 'save_options' ) && check_admin_referer( 'save_options' ) )
				return $this->msg = 'error: nonce failed';

			$this->set_def_options();
			$this->msg = __('Settings reseted to defaults', 'kama-clic-counter');
		}
		// update_link
		elseif( isset($_POST['update_link']) ){

			if( ! wp_verify_nonce( $_nonce, 'update_link' ) && check_admin_referer( 'update_link' ) )
				return $this->msg = 'error: nonce failed';

			$data = wp_unslash( $_POST['up'] );
			$id   = (int) $data['link_id'];

			// очистка
			foreach( $data as $key => & $val ){
				if( is_string($val) ) $val = trim($val);

				if( $key === 'link_url' )
					$val = KCCounter::del_http_protocol( strip_tags( $val ) );
				else
					$val = sanitize_text_field($val);
			}
			unset( $val );

			$this->msg = $this->update_link( $id, $data )
				? __('Link updated!', 'kama-clic-counter')
				: 'error: ' . __('Failed to update link!', 'kama-clic-counter');
		}
		// bulk_action delete_links
		elseif( isset( $_POST['delete_link_id'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'bulk_action' ) && check_admin_referer( 'bulk_action' ) )
				return $this->msg = 'error: nonce failed';

			if( $this->delete_links( $_POST['delete_link_id'] ) )
				$this->msg = __( 'Selected objects deleted', 'kama-clic-counter' );
			else
				$this->msg = __( 'Nothing was deleted!', 'kama-clic-counter' );
		}
		// delete single link handler
		elseif( isset( $_GET['delete_link'] ) ){

			if( ! wp_verify_nonce( $_nonce, 'delete_link' ) )
				return $this->msg = 'error: nonce failed';

			if( $this->delete_links( $_GET['delete_link'] ) )
				wp_redirect( remove_query_arg( array( 'delete_link', '_wpnonce') ) );
			else
				$this->msg = __('Nothing was deleted!', 'kama-clic-counter');
		}
	}

	function admin_page_url(){
		return admin_url( 'admin.php?page=' . KCC_NAME );
	}

	function options_page_output(){
		include KCC_PATH . 'admin/options-page.php';
	}

	function set_def_options(){
		update_option( self::OPT_NAME, $this->get_def_options() );

		return $this->opt = get_option( self::OPT_NAME );
	}

	function update_link( $id, $data ){
		global $wpdb;

		if( $id = (int) $id )
			$query = $wpdb->update( $wpdb->kcc_clicks, $data, [ 'link_id' =>$id ] );

		// обновление вложения, если оно есть
		if( $data['attach_id'] > 0 ){
			$wpdb->update( $wpdb->posts,
				[ 'post_title' => $data['link_title'], 'post_content' => $data['link_description'] ],
				[ 'ID' => $data['attach_id'] ]
			);
		}

		return $query;
	}

	function delete_link_url( $link_id ){
		return add_query_arg( [ 'delete_link' =>$link_id, '_wpnonce' =>wp_create_nonce('delete_link') ] );
	}

	/**
	 * Удаление ссылок из БД по переданному массиву ID или ID ссылки
	 * @param  array/int [$array_ids = array()] ID ссылок котоыре нужно удалить
	 * @return boolean  Удалено ли
	 */
	function delete_links( $array_ids = [] ){
		global $wpdb;

		$array_ids = array_filter( array_map( 'intval', (array) $array_ids ) );

		if( ! $array_ids )
			return false;

		return $wpdb->query( "DELETE FROM $wpdb->kcc_clicks WHERE link_id IN (" . implode( ',', $array_ids ) . ")" );
	}

	## Удаление ссылки по ID вложения
	function delete_link_by_attach_id( $attach_id ){
		global $wpdb;

		if( ! $attach_id )
			return false;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->kcc_clicks WHERE attach_id = %d", $attach_id ) );
	}

	## Обновление ссылки, если обновляется вложение
	function update_link_with_attach( $attach_id ){
		global $wpdb;

		$attdata = get_post( $attach_id );

		$new_data = wp_unslash( [
			'link_description' => $attdata->post_content,
			'link_title'       => $attdata->post_title,
			'link_date'        => $attdata->post_date,
		] );

		return $wpdb->update( $wpdb->kcc_clicks, $new_data, [ 'attach_id' => $attach_id ] );
	}

	function activation(){
		global $wpdb;

		$charset_collate  = (! empty( $wpdb->charset )) ? "DEFAULT CHARSET=$wpdb->charset" : '';
		$charset_collate .= (! empty( $wpdb->collate )) ? " COLLATE $wpdb->collate" : '';

		// Создаем таблицу если такой еще не существует
		$sql = "CREATE TABLE $wpdb->kcc_clicks (
			link_id           bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			attach_id         bigint(20) UNSIGNED NOT NULL default 0,
			in_post           bigint(20) UNSIGNED NOT NULL default 0,
			link_clicks       bigint(20) UNSIGNED NOT NULL default 1,
			link_name         varchar(191)        NOT NULL default '',
			link_title        text                NOT NULL ,
			link_description  text                NOT NULL ,
			link_date         date                NOT NULL default '1970-01-01',
			last_click_date   date                NOT NULL default '1970-01-01',
			link_url          text                NOT NULL ,
			file_size         varchar(100)        NOT NULL default '',
			downloads         ENUM('','yes')      NOT NULL default '',
			PRIMARY KEY  (link_id),
			KEY in_post (in_post),
			KEY downloads (downloads),
			KEY link_url (link_url(191))
		) $charset_collate";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql );

		if( ! get_option( self::OPT_NAME ) ){
			$this->set_def_options();
		}
	}


}
