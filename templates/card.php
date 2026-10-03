<?php
/**
 * Card sản phẩm — dùng trong shortcode [san_pham_grid] và trang danh sách.
 * Biến có sẵn: $post_id, $index
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$link     = get_permalink( $post_id );
$title    = get_the_title( $post_id );
$subtitle = get_post_meta( $post_id, '_spc_subtitle', true );
$excerpt  = get_the_excerpt( $post_id );
$features = array_slice( spc_get_features( $post_id ), 0, 5 );
?>
<article class="spc-card">
	<a class="spc-card__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
		<span class="spc-card__num"><?php echo esc_html( str_pad( (string) $index, 2, '0', STR_PAD_LEFT ) ); ?></span>
		<?php
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'loading' => 'lazy', 'alt' => esc_attr( $title ) ) );
		}
		?>
	</a>

	<div class="spc-card__body">
		<h3 class="spc-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>

		<?php if ( $subtitle ) : ?>
			<p class="spc-card__sub"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( $excerpt ) : ?>
			<p class="spc-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>

		<?php if ( $features ) : ?>
			<ul class="spc-list">
				<?php foreach ( $features as $f ) : ?>
					<li><?php echo esc_html( $f ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<a class="spc-card__more" href="<?php echo esc_url( $link ); ?>">Xem chi tiết <span aria-hidden="true">→</span></a>
	</div>
</article>
