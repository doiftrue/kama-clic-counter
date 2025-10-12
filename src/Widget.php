<?php

namespace KamaClickCounter;

use WP_Widget;

class Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'kcc_widget',
			__( 'KCC: Top Downloads', 'kama-clic-counter' ),
			[ 'description' => __( 'Kama Click Counter Widget', 'kama-clic-counter' ), ]
		);
	}

	public static function init(): void {
		if( ! plugin()->opt->widget ){
			return;
		}

		add_action( 'widgets_init', static function() {
			register_widget( self::class );
		} );
	}

	/**
	 * Widget output on front.
	 *
	 * @param array $args  Widget Arguments.
	 * @param array $opts  Saved data from widget settings.
	 */
	public function widget( $args, $opts ): void {
		global $wpdb;

		if( ! $opts ){
			echo '<p>Kama Click Counter Widget: No widget options saved. Save widget option to display it.</p>';

			return;
		}

		$opts = (object) $opts;
		$number = (int) $opts->number;
		$template = $opts->template;

		$out__fn = static function( $wg_content ) use ( $args, $opts ) {
			$title = apply_filters( 'widget_title', $opts->title );

			$out = '';
			$out .= $args['before_widget'];
			$out .= $args['before_title'] . esc_html( $title ) . $args['after_title'];
			$out .= $wg_content;
			$out .= $args['after_widget'];

			return apply_filters( 'kcc_widget_out', $out );
		};

		$AND = '';

		if( $opts->last_date ){
			$AND .= $wpdb->prepare( "AND link_date > %s", $opts->last_date );
		}

		if( isset( $opts->only_downloads ) ){
			$AND .= " AND downloads != ''";
		}

		$ORDER_BY = 'ORDER BY link_clicks DESC';
		if( 'clicks_per_day' === $opts->sort ){
			$ORDER_BY = 'ORDER BY (link_clicks/DATEDIFF( CURDATE(), link_date )) DESC, link_clicks DESC';
		}

		$sql = "SELECT * FROM $wpdb->kcc_clicks WHERE link_clicks > 0 $AND $ORDER_BY LIMIT $number";
		$links = $wpdb->get_results( $sql );
		$links = array_map( static fn( $ln ) => new Link_Item( $ln ), (array) $links );
		if( ! $links ){
			echo $out__fn( 'Error: empty SQL result' );
			return;
		}

		/// OUTPUT

		$lis = [];
		foreach( $links as $link ){
			$tpl = $template; // temporary template

			if( false !== strpos( $template, '[link_description' ) ){
				$width = 70;
				$desc = wp_kses_post( $link->link_description );
				$desc = mb_strimwidth( $desc, 0, $width, ' ...', 'utf-8' );
				$tpl = str_replace( '[link_description]', $desc, $tpl );
			}

			if( ! empty( $opts->use_post_url ) && $link->in_post ){
				$_url = get_permalink( $link->in_post );

				if( $thumb_url = get_the_post_thumbnail_url( $link->in_post, 'thumbnail' ) ){
					$tpl = str_replace( '[icon_url]', $thumb_url, $tpl );
				}
			}
			else{
				$_url = plugin()->counter->get_kcc_url( $link->link_url, $link->in_post, $link->downloads );
			}

			$tpl = str_replace( '[link_url]', esc_url( $_url ), $tpl );

			// change the rest
			$lis[] = '<li class="kcc_widget__item">' . plugin()->download_shortcode->tpl_replace_shortcodes( $tpl, $link ) . '</li>' . "\n";
		}

		$wg_content = '
		<style id="kcc-widget">' . esc_html( $opts->template_css ) . '</style>
		<ul class="kcc_widget">' . implode( '', $lis ) . '</ul>
		';

		echo $out__fn( $wg_content );
	}

	/**
	 * Admin part of the widget
	 *
	 * @param array $instance  The settings for the particular instance of the widget.
	 *
	 * @return string|void Default return is 'noform'.
	 */
	public function form( $instance ) {
		$default_template_css = <<<'CSS'
			.kcc_widget{ display:flex; flex-direction:column; gap:1.3em; }
			.kcc_widget li{ display:flex; align-items:center; gap:1em; list-style:none; margin:0; padding:0; }
			.kcc_widget img{ align-self:flex-start; width:2rem; }
			.kcc_widget p{ margin:0; margin-top:.5em; font-size:90%; opacity:.7; }
			CSS;

		$default_template = <<<'HTML'
			<img src="[icon_url]" alt="" />
			<div class="kcc_widget__item_info">
				<a href="[link_url]">[link_title]</a> <small>([link_clicks])</small>
				<p>[link_description]</p>
			</div>
			HTML;

		$title          = $instance['title'] ?? __( 'Top Downloads', 'kama-clic-counter' );
		$number         = $instance['number'] ?? 5;
		$last_date      = $instance['last_date'] ?? '';
		$template_css   = $instance['template_css'] ?? preg_replace( '~^\t+~m', '', trim( $default_template_css ) );
		$template       = $instance['template'] ?? preg_replace( '~^\t+~m', '', trim( $default_template ) );
		$sort           = $instance['sort'] ?? '';
		$only_downloads = (int) ( $instance['only_downloads'] ?? 0 );
		$use_post_url   = (int) ( $instance['use_post_url'] ?? 0 );
		?>
		<p>
			<label><?= __( 'Title:', 'kama-clic-counter' ) ?>
				<input type="text" class="widefat" name="<?= $this->get_field_name( 'title' ) ?>" value="<?= esc_attr( $title ) ?>">
			</label>
		</p>

		<p>
			<label>
				<input type="text" class="widefat" style="width:40px;"
				       name="<?= $this->get_field_name( 'number' ) ?>"
				       value="<?= esc_attr( $number ) ?>">
				← <?= __( 'how many links to show?', 'kama-clic-counter' ) ?>
			</label>
		</p>

		<p>
			<select name="<?= $this->get_field_name( 'sort' ) ?>">
				<option value="all_clicks" <?php selected( $sort, 'all_clicks' ) ?>><?= __( 'all clicks', 'kama-clic-counter' ) ?></option>
				<option value="clicks_per_day" <?php selected( $sort, 'clicks_per_day' ) ?>><?= __( 'clicks per day', 'kama-clic-counter' ) ?></option>
			</select> ← <?= __( 'how to sort the result?', 'kama-clic-counter' ) ?>
		</p>

		<p>
			<label>
				<input type="text" class="widefat" style="width:100px;" placeholder="YYYY-MM-DD"
				       name="<?= $this->get_field_name( 'last_date' ) ?>"
				       value="<?= esc_attr( $last_date ) ?>">
				← <?= __( 'show links older then this data (ex. 2014-08-09)', 'kama-clic-counter' ) ?>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox" name="<?= $this->get_field_name( 'only_downloads' ) ?>" value="1" <?php checked( $only_downloads, 1 ) ?>> ← <?= __( 'display only downloads, but not all links?', 'kama-clic-counter' ) ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?= $this->get_field_name('use_post_url') ?>" value="1" <?php checked( $use_post_url, 1 ) ?>> ← <?= __('Use URL to post with the link, and not URL of the link ', 'kama-clic-counter' ) ?>
			</label>
		</p>
		<hr>
		<p>
			<?= __('Out template:', 'kama-clic-counter' ) ?>
			<textarea class="widefat" style="height:100px;" name="<?= $this->get_field_name( 'template' ) ?>"><?= esc_textarea( $template ) ?></textarea>
			<?= tpl_available_tags() ?>
		</p>

		<p>
			<?= __('Template CSS:', 'kama-clic-counter' ) ?>
			<textarea class="widefat" style="height:100px;" name="<?= $this->get_field_name( 'template_css' ) ?>"><?= esc_textarea( $template_css ) ?></textarea>
		</p>
		<?php
	}

	/**
	 * Saves the widget settings.
	 * Here the data should be cleared and returned to be saved to the database.
	 *
	 * @param array $new_data  New settings for this instance as input by the user via WP_Widget::form().
	 * @param array $old_data  Old settings for this instance.
	 */
	public function update( $new_data, $old_data ): array {
		$sanitized = [
			'title'          => wp_kses_post( $new_data['title'] ?? '' ),
			'number'         => (int) ( $new_data['number'] ?? 5 ),
			'sort'           => sanitize_text_field( $new_data['sort'] ?? '' ),
			'last_date'      => preg_match( '~\d{4}-\d{1,2}-\d{1,2}~', $new_data['last_date'] ) ? $new_data['last_date'] : '',
			'only_downloads' => (int) ( $new_data['only_downloads'] ),
			'use_post_url'   => (int) ( $new_data['use_post_url'] ),
			'template'       => wp_kses_post( $new_data['template'] ?? '' ),
			'template_css'   => sanitize_textarea_field( $new_data['template_css'] ?? '' ),
		];

		return array_merge( $new_data, $sanitized );
	}

}

