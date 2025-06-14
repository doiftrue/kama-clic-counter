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
	 *
	 * @return void
	 */
	public function widget( $args, $opts ) {
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

		if( ! $results = $wpdb->get_results( $sql ) ){
			echo $out__fn( 'Error: empty SQL result' );

			return;
		}

		// out

		$lis = [];
		foreach( $results as $link ){

			$tpl = $template; // temporary template

			if( false !== strpos( $template, '[link_description' ) ){
				$ln = 70;
				$desc = ( mb_strlen( $link->link_description, 'utf-8' ) > $ln )
					? mb_substr( $link->link_description, 0, $ln, 'utf-8' ) . ' ...'
					: $link->link_description;

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
			$lis[] = '<li>' . plugin()->download_shortcode->tpl_replace_shortcodes( $tpl, $link ) . '</li>' . "\n";
		}

		$wg_content = '
		<style>' . strip_tags( $opts->template_css ) . '</style>
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

		$default_template_css = '
			.kcc_widget{ padding:15px; }
			.kcc_widget li{ margin-bottom:10px; list-style: none; }
			.kcc_widget li:after{ content:""; display:table; clear:both; }
			.kcc_widget img{ width:30px; float:left; margin:5px 10px 5px 0; }
			.kcc_widget p{ margin-left:40px; }
		';

		$default_template = '
			<img src="[icon_url]" alt="" />
			<a href="[link_url]">[link_title]</a> ([link_clicks])
			<p>[link_description]</p>
		';

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
	 */
	public function update( $new_instance, $old_instance ): array {
		$inst = [];
		$inst['title'] = $new_instance['title'] ? strip_tags( $new_instance['title'] ) : '';
		$inst['number'] = $new_instance['number'] ? (int) $new_instance['number'] : 5;
		$inst['last_date'] = preg_match( '~[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}~', $new_instance['last_date'] ) ? $new_instance['last_date'] : '';

		return array_merge( $inst, $new_instance );
	}

}

