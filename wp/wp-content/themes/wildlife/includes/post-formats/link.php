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

	<?php $url = get_post_meta(get_the_ID(), 'tz_link_url', true); ?>
	<?php if(!is_singular()) : ?>
	<header class="post-header">
		<h2 class="post-title">
			<a target="_blank" href="<?php echo $url; ?>" title="<?php echo $url; ?>"><span><?php the_title(); ?></span></a>
		</h2>
	</header>
	<?php endif; ?>

	<!-- Post Content -->
	<div class="post_content">
		<?php the_content(''); ?>
		<div class="clear"></div>
	</div>
	<!-- //Post Content -->

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

</article><!--//.post-holder-->