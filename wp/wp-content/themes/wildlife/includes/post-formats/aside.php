<article id="post-<?php the_ID(); ?>" <?php post_class('post__holder'); ?>>

	<?php

		$args = array(
			'meta_elements' => array(
				'start_unite', 
				'date',
				'end_unite'
			)
		);
		get_post_metadata($args);

	?>

	<!-- Post Content -->
	<div class="post_content">
		<?php the_content('<span>' . theme_locals('continue_reading') . '</span>'); ?>
		<!--// Post Content -->
		<div class="clear"></div>
	</div>

	<?php

				$args = array(
					'meta_elements' => array(
						'start_unite', 
						'author',
						'comment',
						'permalink',
						'end_unite'
					)
				);
				get_post_metadata($args);
			?>


	<?php //get_template_part('includes/post-formats/post-meta'); ?>

</article><!--//.post__holder-->