<?php

namespace KamaClickCounter;

class Plugin {

	/** No end slash */
	public string $dir; /* readonly */

	/** No end slash */
	public string $url; /* readonly */

	public string $slug = 'kama-click-counter'; /* readonly */
	public string $name;                        /* readonly */
	public string $ver;                         /* readonly */
	public string $php_ver;                     /* readonly */

	/** WP basename: kama-clic-counter/kama_click_counter.php */
	public string $basename;

	/** Access to manage options (edit links) */
	public ?bool $manage_access;

	/** Access to admin options (change settings) */
	public bool $admin_access;

	public Options $opt;
	public Admin $admin;
	public Counter $counter;
	public Download_Shortcode $download_shortcode;

	public function __construct( string $main_file_path ) {
		$this->set_wpdb_tables();

		$this->basename = plugin_basename( $main_file_path );

		$this->dir = dirname( $main_file_path );
		$this->url = plugins_url( '', $main_file_path );

		$info = get_file_data( $main_file_path, [
			'name'    => 'Plugin Name',
			'version' => 'Version',
			'php_ver' => 'Requires PHP',
		] );
		$this->name    = $info['name'] ?? '';
		$this->ver = $info['version'] ?? '';
		$this->php_ver = $info['php_ver'] ?? '';

		$this->opt = new Options();
	}

	public function init(): void {
		if( ! $this->check_dependencies() ){
			return;
		}

		load_plugin_textdomain( 'kama-clic-counter', false, basename( $this->dir ) . '/languages' );

		$this->set_manage_access();
		$this->set_admin_access();

		if( is_admin() ){
			$this->admin = new Admin( $this->opt );
			$this->admin->init();
		}

		$this->counter = new Counter( $this->opt );
		$this->counter->init();

		// admin_bar
		if( $this->opt->toolbar_item && $this->manage_access ){
			add_action( 'admin_bar_menu', [ $this, 'add_toolbar_menu' ], 90 );
		}

		Widget::init();

		$this->download_shortcode = new Download_Shortcode();
		$this->download_shortcode->init();

		$Content_Replacer = new Content_Replacer();
		$Content_Replacer->init();
	}

	public function set_wpdb_tables(): void {
		global $wpdb;

		$wpdb->tables[] = 'kcc_clicks';
		$wpdb->kcc_clicks = $wpdb->prefix . 'kcc_clicks';
	}

	private function set_admin_access(): void {
		$this->admin_access = (bool) current_user_can( 'manage_options' );
	}

	private function set_manage_access(): void {
		$this->manage_access = apply_filters( 'kcc_manage_access', null );

		if( $this->manage_access !== null ){
			$this->manage_access = (bool) $this->manage_access;
			return;
		}

		$this->manage_access = (bool) current_user_can( 'manage_options' );

		if( ! $this->manage_access && $this->opt->access_roles ){
			foreach( wp_get_current_user()->roles as $role ){
				if( in_array( $role, $this->opt->access_roles, true ) ){
					$this->manage_access = true;
					break;
				}
			}
		}
	}

	public function add_toolbar_menu( $toolbar ): void {
		$toolbar->add_menu( [
			'id'    => 'kcc',
			'title' => 'Click Counter',
			'href'  => admin_url( 'admin.php?page=' . plugin()->slug ),
		] );
	}

	public function check_dependencies(): bool {
		if( version_compare( PHP_VERSION, $this->php_ver, '<=' ) ){
			Helpers::notice_message(
				'<b>Kama Click Counter</b> plugin requires PHP version <b>' . $this->php_ver . '</b> or higher. Please upgrade PHP or diactivate the plugin.',
				'error'
			);

			return false;
		}

		return true;
	}

	public function activation(): void {
		global $wpdb;

		if( ! $this->check_dependencies() ){
			return;
		}

		$charset_collate = ( ! empty( $wpdb->charset ) ) ? "DEFAULT CHARSET=$wpdb->charset" : '';
		$charset_collate .= ( ! empty( $wpdb->collate ) ) ? " COLLATE $wpdb->collate" : '';

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

		if( ! $this->opt->get_raw_options() ){
			$this->opt->reset_to_defaults();
		}
	}

}
