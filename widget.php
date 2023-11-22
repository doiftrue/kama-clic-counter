<?php

// регистрация Kcc_Widget в WordPress
add_action( 'widgets_init', 'kccount_register_widget' );
function kccount_register_widget(){
	register_widget('Kcc_Widget');
}

class Kcc_Widget extends WP_Widget {

	// Регистрация видежта используя основной класс
	function __construct(){
		parent::__construct(
			'kcc_widget', // Base ID
			__('KCC: Top Downloads', 'kama-clic-counter'), // Name
			array( 'description' => __( 'Kama Click Counter Widget', 'kama-clic-counter' ), ) // Args
		);
	}


	/**
	 * Вывод виджета во Фронт-энде
	 *
	 * @param array $args Аргументы виджета.
	 * @param array $opts Сохраненные данные из настроек
	 */
	public function widget( $args, $opts ){
		global $wpdb;

		$opts = (object) $opts;
		$number = (int) $opts->number;
		$template = $opts->template;

		$out__fn = function( $wg_content ) use ( $args, $opts ) {

			$title = apply_filters( 'widget_title', $opts->title );

			$out = '';
			$out .= $args['before_widget'];
			$out .= $args['before_title'] . esc_html( $title ) . $args['after_title'];
			$out .= $wg_content;
			$out .= $args['after_widget'];

			return apply_filters( 'kcc_widget_out', $out );
		};

		$AND = '';

		if( $opts->last_date )
			$AND .= $wpdb->prepare("AND link_date > %s", $opts->last_date );

		if( isset( $opts->only_downloads ) )
			$AND .= " AND downloads != ''";

		$ORDER_BY = 'ORDER BY link_clicks DESC';
		if( 'clicks_per_day' === $opts->sort )
			$ORDER_BY = 'ORDER BY (link_clicks/DATEDIFF( CURDATE(), link_date )) DESC, link_clicks DESC';

		$sql = "SELECT * FROM $wpdb->kcc_clicks WHERE link_clicks > 0 $AND $ORDER_BY LIMIT $number";

		if( ! $results = $wpdb->get_results( $sql ) ){
			echo $out__fn( 'Error: empty SQL result' );
			return;
		}

		// out
		$lis = [];
		foreach( $results as $link ){

			$tpl = $template; // временный шаблон

			if( false !== strpos( $template, '[link_description') ){
				$ln = 70;
				$desc = ( mb_strlen( $link->link_description, 'utf-8' ) > $ln )
					? mb_substr ( $link->link_description , 0 , $ln, 'utf-8' ) . ' ...'
					: $link->link_description;

				$tpl = str_replace( '[link_description]', $desc, $tpl );
			}

			if( ! empty( $opts->use_post_url ) && $link->in_post ){
				$_url = get_permalink( $link->in_post );

				if( $thumb_url = get_the_post_thumbnail_url( $link->in_post, 'thumbnail') )
					$tpl = str_replace( '[icon_url]', $thumb_url, $tpl );
			}
			else {
				$_url = KCCounter()->get_kcc_url( $link->link_url, $link->in_post, $link->downloads );
			}

			$tpl = str_replace( '[link_url]', esc_url( $_url ), $tpl );

			// меняем остальное
			$lis[] = '<li>'. KCCounter()->tpl_replace_shortcodes( $tpl, $link ) .'</li>'."\n";
		}

		$wg_content = '
		<style>'. strip_tags( $opts->template_css ) .'</style>
		<ul class="kcc_widget">'. implode( '', $lis ) .'</ul>
		';

		echo $out__fn( $wg_content );
	}


	/**
	 * Админ-часть виджета
	 */
	public function form( $inst ){

		$title        = @ $inst['title']     ? $inst[ 'title' ]     : __('Top Downloads', 'kama-clic-counter' );
		$number       = @ $inst['number']    ? $inst[ 'number' ]    : 5;
		$last_date    = @ $inst['last_date'] ? $inst[ 'last_date' ] : '';
		$template_css = @ $inst['template_css'] ? $inst[ 'template_css' ] : preg_replace('~^\t+~m', '', '.kcc_widget{ padding:15px; }
							.kcc_widget li{ margin-bottom:10px; list-style: none; }
							.kcc_widget li:after{ content:""; display:table; clear:both; }
							.kcc_widget img{ width:30px; float:left; margin:5px 10px 5px 0; }
							.kcc_widget p{ margin-left:40px; }');

		$template  = @ $inst['template']  ? $inst['template']    : '<img src="[icon_url]" alt="" />'. "\n"
				.'<a href="[link_url]">[link_title]</a> ([link_clicks])'. "\n"
				.'<p>[link_description]</p>';

		?>
		<p><label><?php _e( 'Title:', 'kama-clic-counter' ); ?>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>">
		</label></p>

		<p><label>
				<input type="text" class="widefat" style="width:40px;" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr( $number ); ?>"> ← <?php _e( 'how many links to show?', 'kama-clic-counter' ); ?>
		</label></p>

		<p><select name="<?php echo $this->get_field_name('sort'); ?>">
				<option value="all_clicks" <?php selected( @ $inst['sort'], 'all_clicks') ?>><?php _e( 'all clicks', 'kama-clic-counter' ); ?></option>
				<option value="clicks_per_day" <?php selected( @ $inst['sort'], 'clicks_per_day') ?>><?php _e( 'clicks per day', 'kama-clic-counter' ); ?></option>
		</select> ← <?php _e( 'how to sort the result?', 'kama-clic-counter' ); ?></p>

		<p><label>
				<input type="text" class="widefat" style="width:100px;" placeholder="YYYY-MM-DD" name="<?php echo $this->get_field_name( 'last_date' ); ?>" value="<?php echo esc_attr( $last_date ); ?>"> ← <?php _e( 'show links older then this data (ex. 2014-08-09)', 'kama-clic-counter' ); ?>
		</label></p>

		<p>
			<label>
				<input type="checkbox" name="<?php echo $this->get_field_name( 'only_downloads' ); ?>" value="1" <?php checked( @ $inst['only_downloads'], 1 ) ?>> ← <?php _e( 'display only downloads, but not all links?', 'kama-clic-counter' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="<?php echo $this->get_field_name('use_post_url'); ?>" value="1" <?php checked( @ $inst['use_post_url'], 1 ) ?>> ← <?php _e('Use URL to post with the link, and not URL of the link ', 'kama-clic-counter' ); ?>
			</label>
		</p>
		<hr>
		<p>
			<?php _e('Out template:', 'kama-clic-counter' ); ?>
			<textarea class="widefat" style="height:100px;" name="<?php echo $this->get_field_name( 'template' ); ?>"><?php echo $template; ?></textarea>
			<?php echo kcc_tpl_available_tags(); ?>
		</p>

		<p>
			<?php _e('Template CSS:', 'kama-clic-counter' ); ?>
			<textarea class="widefat" style="height:100px;" name="<?php echo $this->get_field_name( 'template_css' ); ?>"><?php echo $template_css; ?></textarea>
		</p>
		<?php
	}


	/**
	 * Сохранение настроек виджета. Здесь данные должны быть очищены и возвращены для сохранения их в базу данных.
	 */
	public function update( $new_instance, $old_instance ){
		$inst = array();
		$inst['title']     = $new_instance['title']  ? strip_tags( $new_instance['title'] ) : '';
		$inst['number']    = $new_instance['number'] ? (int) $new_instance['number']        : 5;
		$inst['last_date'] = preg_match('~[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}~', $new_instance['last_date'] ) ? $new_instance['last_date']     : '';
		// $inst['template']  = $new_instance['template'];

		$result = array_merge( $inst, $new_instance );

		return $result;
	}

}

