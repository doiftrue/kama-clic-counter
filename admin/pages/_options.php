<?php
namespace KamaClickCounter;

defined( 'ABSPATH' ) || exit;

/**
 * @var Admin $this
 */

$def = $this->opt->get_def_options();
?>
<form method="POST" action="">

	<?php wp_nonce_field('save_options'); ?>

	<div class="kcc_block">
		<p><?php _e('Downloads template. This code replaces the shortcode <code>[download url="URL"]</code> in content:', 'kama-clic-counter') ?></p>

		<textarea style="width:70%;height:190px;float:left;margin-right:15px;" name="download_tpl" ><?= esc_textarea( $this->opt->download_tpl ) ?></textarea>

		<?= tpl_available_tags() ?>

		<p><?php _e('Default template (use as example):', 'kama-clic-counter'); ?></p>

		<textarea style="width:70%; height:50px; display:block;" disabled><?= esc_textarea( $def['download_tpl'] ) ?></textarea>
	</div>

	<div class="kcc_block">

		<div class="blk">
			<label>
				<input type="checkbox" name="hide_url" <?= $this->opt->hide_url ? 'checked' : ''?>>
				← <?php _e('hide link URL with link ID. Works only for download block.', 'kama-clic-counter') ?>
			</label>
		</div>

		<div class="blk">
			<div><?php _e('html class of the link of witch clicks we want to consider.', 'kama-clic-counter') ?></div>
			<input type="text" style="width:150px;" name="links_class" value="<?= esc_attr( $this->opt->links_class ) ?>" />
			<p class="description"><?php _e('Clicks on links with the same code <code>&lt;a class=&quot;count&quot; href=&quot;#&quot;&gt;link text&lt;/a&gt;</code> will be considered. Leave the field in order to disable this option - it save little server resourses.', 'kama-clic-counter') ?></p>
		</div>

		<div class="blk">
			<div><?php _e('How to display statistics for the links in content?', 'kama-clic-counter') ?></div>
			<select name="add_hits">
				<option value=""         <?php selected( $this->opt->add_hits, '') ?>        ><?php _e('don\'t show', 'kama-clic-counter') ?></option>
				<option value="in_title" <?php selected( $this->opt->add_hits, 'in_title') ?>><?php _e('in the title attribute', 'kama-clic-counter') ?></option>
				<option value="in_plain" <?php selected( $this->opt->add_hits, 'in_plain') ?>><?php _e('as text after link', 'kama-clic-counter') ?></option>
			</select>

			<p class="description"><?php _e('Disable this option and save 1 database query for each link!', 'kama-clic-counter') ?></p>
		</div>

		<div class="blk">
			<div><?php _e('Exclude filter', 'kama-clic-counter') ?></div>
			<textarea name="url_exclude_patterns" style="width:400px; height:40px;"><?= esc_textarea( $this->opt->url_exclude_patterns ) ?></textarea>
			<p class="description">
				<?php _e('If URL contain defined here substring, click on it will NOT BE count. Separate with comma or new line.', 'kama-clic-counter') ?>
				<br>
				<?php _e('Substring starting and ending with / becomes regular expression, ex: /^[0-9]+/.', 'kama-clic-counter') ?>
			</p>
		</div>

		<div class="blk">
			<label><input type="checkbox" name="in_post" <?php checked( (bool) $this->opt->in_post ) ?> />
				← <?php _e('distinguish clicks on the same links, but from different posts. Uncheck in order to count clicks in different posts in one place.', 'kama-clic-counter') ?></label>
		</div>

		<div class="blk">
			<label><input type="checkbox" name="widget" <?php checked( (bool) $this->opt->widget )?> />
				← <?php _e('enable WordPress widget?', 'kama-clic-counter') ?></label>
		</div>

		<div class="blk">
			<label><input type="checkbox" name="toolbar_item" <?php checked( (bool) $this->opt->toolbar_item ) ?> />
				← <?php _e('show link on stat in Admin Bar', 'kama-clic-counter') ?></label>
		</div>

		<?php
		if( plugin()->admin_access ){
			$_options = '';

			foreach( array_reverse( get_editable_roles() ) as $role => $details ){
				if( $role === 'administrator' || $role === 'subscriber' ){
					continue;
				}

				$_options .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $role ),
					in_array( $role, (array) $this->opt->access_roles ) ? ' selected="selected"' : '',
					translate_user_role( $details['name'] )
				);
			}

			echo '
			<div class="blk">
				<select multiple name="access_roles[]">
					'. $_options .'
				</select> ← '. __('Role names, except \'administrator\' which will have access to KCC stat and links manage.', 'kama-clic-counter') .'
			</div>';
		}
		?>
	</div>

	<div class="kcc_block">
		<input type="submit" name="save_options" class="button-primary" value="<?php _e('Save changes', 'kama-clic-counter') ?>" />
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="submit" name="reset" class="button" value="<?php _e('Reset to defaults', 'kama-clic-counter') ?>" onclick='return confirm("<?php _e('Sure to reset settings?', 'kama-clic-counter') ?>")' />
	</div>

</form>

