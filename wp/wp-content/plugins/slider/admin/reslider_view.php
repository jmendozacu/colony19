<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_once('reslide-edit-slider-view.php');
/**
 * print sliders list in main page
 *
 * @param $_row
 */
function reslide_sliders_view_list( $rows ) { ?>
	<?php reslide_free_version_banner(); ?>
	<div class="reslide_sliders_list_wrapper">
		<div class="reslide_sliders_list_header">
			<div class="id"><h3>&nbsp;&nbsp;<?php _e('ID', 'reslide'); ?></h3></div>
			<div class="name"><h3><?php _e('Name', 'reslide') ?></h3></div>
			<div class="count"><h3><?php _e('Slides Count', 'reslide'); ?></h3></div>
			<div class="attr"><h3><?php _e('Remove', 'reslide'); ?></h3></div>
		</div>
		<ul id="reslide_sliders_list">
			<li><a class="add-slider" title="<?php _e('Add new slider', 'reslide'); ?>"
				   href="<?php echo wp_nonce_url(admin_url('admin.php?page=reslider&task=addslider'), 'reslide_addslider'); ?>">
					<i class="fa fa-plus-circle"></i></a><i>
					&nbsp;&nbsp;<?php _e('The new slider will appear here', 'reslide'); ?></i></li>
			<?php
			foreach ($rows as $row) { ?>
				<li>
					<div class="id">&nbsp;&nbsp;#<?php echo $row->id; ?></div>
					<div class="name"><a
							href="<?php echo wp_nonce_url( admin_url('admin.php?page=reslider&task=editslider&id=' . $row->id),'reslide_editslider_'.$row->id ); ?>"><?php echo stripslashes_deep($row->title); ?></a>&nbsp;
					</div>
					<div class="count"><?php echo $row->count; ?></div>
					<div class="properties">
						<a title="delete" class="delete"
						   href="<?php echo wp_nonce_url( admin_url('admin.php?page=reslider&task=removeslider&id=' . $row->id), 'reslider_removeslider_' . $row->id ) ?>"><i
								class="fa fa-times"></i></a>
					</div>
				</li>
			<?php } ?>
		</ul>
	</div>
	<?php
}