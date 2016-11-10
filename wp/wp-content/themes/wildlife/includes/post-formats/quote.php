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

	<?php 
		$quote  = get_post_meta(get_the_ID(), 'tz_quote', true);
		$author = get_post_meta(get_the_ID(), 'tz_author_quote', true); 
	?>

	<?php if (!empty($quote)) : ?>
		<div class="quote-wrap clearfix">
			<blockquote>
				<?php echo $quote; ?>
			</blockquote>
		<?php if (!empty($author)) { ?>
			<cite>&mdash; <?php echo $author; ?></cite>
		<?php }?>
		</div>
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