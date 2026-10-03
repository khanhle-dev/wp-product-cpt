<?php
/**
 * Trang chi tiết sản phẩm.
 * Muốn chỉnh: copy file này vào thư mục theme con (astra-child/single-san_pham.php).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$pid       = get_the_ID();
	$title     = get_the_title();
	$subtitle  = get_post_meta( $pid, '_spc_subtitle', true );
	$features  = spc_get_features( $pid );
	$gallery   = spc_get_gallery( $pid );
	$phone     = spc_option( 'phone' );
	$zalo      = spc_zalo_url();
	$archive   = spc_list_url();
	?>

	<section class="spc-hero">
		<div class="spc-wrap">
			<nav class="spc-breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
				<span>/</span>
				<a href="<?php echo esc_url( $archive ); ?>">Sản phẩm</a>
				<span>/</span>
				<strong><?php echo esc_html( $title ); ?></strong>
			</nav>

			<p class="spc-eyebrow">Sản phẩm</p>
			<h1 class="spc-hero__title"><?php echo esc_html( $title ); ?></h1>

			<?php if ( has_excerpt() ) : ?>
				<p class="spc-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="spc-detail">
		<div class="spc-wrap spc-detail__grid">

			<div class="spc-detail__media" data-spc-lightbox>
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="spc-detail__main" href="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'full' ) ); ?>">
						<?php the_post_thumbnail( 'large', array( 'alt' => esc_attr( $title ) ) ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $gallery ) : ?>
					<div class="spc-gallery">
						<?php foreach ( $gallery as $gid ) : ?>
							<a class="spc-gallery__item" href="<?php echo esc_url( wp_get_attachment_image_url( $gid, 'full' ) ); ?>">
								<?php echo wp_get_attachment_image( $gid, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="spc-detail__info">
				<?php if ( $subtitle ) : ?>
					<p class="spc-detail__sub">/ <?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $features ) : ?>
					<ul class="spc-list spc-list--lg">
						<?php foreach ( $features as $f ) : ?>
							<li><?php echo esc_html( $f ); ?></li>
						<?php endforeach; ?>
					</ul>
					<hr class="spc-divider">
				<?php endif; ?>

				<div class="spc-content">
					<?php the_content(); ?>
				</div>

				<div class="spc-actions">
					<a class="spc-btn spc-btn--primary" href="<?php echo esc_url( spc_quote_url( $title ) ); ?>">
						Báo giá sản phẩm này <span aria-hidden="true">→</span>
					</a>

					<?php if ( $phone ) : ?>
						<a class="spc-btn spc-btn--ghost" href="tel:<?php echo esc_attr( spc_tel( $phone ) ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
							<?php echo esc_html( $phone ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $zalo ) : ?>
						<a class="spc-btn spc-btn--ghost" href="<?php echo esc_url( $zalo ); ?>" target="_blank" rel="noopener">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.5L3 21l2-5.4A8.4 8.4 0 1 1 21 11.5z"/><path d="M8 10h8M8 13.5h5"/></svg>
							Zalo
						</a>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</section>

	<?php
	// Sản phẩm khác — 3 sản phẩm, bỏ sản phẩm đang xem. Số 01/02… giữ theo thứ tự gốc.
	$others = new WP_Query( array(
		'post_type'      => 'san_pham',
		'posts_per_page' => 3,
		'post__not_in'   => array( $pid ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	) );
	if ( $others->have_posts() ) :
		?>
		<section class="spc-related">
			<div class="spc-wrap">
				<h2 class="spc-section-title spc-section-title--bar">Sản phẩm <span>khác</span></h2>
				<div class="spc-grid" style="--spc-cols:3">
					<?php
					while ( $others->have_posts() ) {
						$others->the_post();
						spc_render_card( get_the_ID() );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;

endwhile;

get_footer();
