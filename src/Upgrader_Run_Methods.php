<?php

namespace KamaClickCounter;

trait Upgrader_Run_Methods {

	protected function v4_1_0( array & $res ): void { // @phpstan-ignore-line
		$raw_opt = plugin()->opt->get_raw_options();

		// Move inline styles from template to "download_tpl_styles" option.
		$tpl = $raw_opt['download_tpl'] ?? '';
		$tpl_styles = $raw_opt['download_tpl_styles'] ?? '';
		if( ! $tpl_styles && $tpl && preg_match( '~<style[^>]*>([^<]+)</style>~', $tpl, $mm ) ){
			[ $full_style, $css_rules ] = $mm;
			$css_rules = preg_replace( '~^\s+~m', '', $css_rules );
			$new_opt = $raw_opt;
			$new_opt['download_tpl'] = str_replace( $full_style, '', $raw_opt['download_tpl'] );
			$new_opt['download_tpl_styles'] = wp_kses( $css_rules, 'strip' );

			$up = plugin()->opt->update_option( $new_opt );
			$up && $res['download_tpl_styles'] = 'download_tpl_styles option added from download_tpl';
		}

		// Add new db columns
		global $wpdb;
		$exists = $wpdb->get_var( "SHOW COLUMNS FROM $wpdb->kcc_clicks LIKE 'clicks_in_month'" );
		if( ! $exists ){
			$up = $wpdb->query( "ALTER TABLE $wpdb->kcc_clicks
				ADD COLUMN clicks_in_month   bigint(20) UNSIGNED NOT NULL default 0 COMMENT 'Current month clicks count' AFTER link_clicks,
				ADD COLUMN clicks_prev_month bigint(20) UNSIGNED NOT NULL default 0 COMMENT 'Previous month clicks count' AFTER clicks_in_month,
				ADD COLUMN clicks_history    text                NOT NULL AFTER clicks_prev_month"
			);
			$up && $res['db_columns'] = 'New columns added to db table';
		}
	}

	protected function v3_6_2( array & $res ): void { // @phpstan-ignore-line
		global $wpdb;
		if( ! version_compare( $this->prev_ver, '3.6.8.2', '<' ) ){
			return;
		}

		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'http://', '//')" );
		$wpdb->query( "UPDATE $wpdb->kcc_clicks SET link_url = REPLACE(link_url, 'https://', '//')" );
	}

}
