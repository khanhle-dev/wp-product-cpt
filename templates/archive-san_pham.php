<?php
/**
 * Trang danh sách sản phẩm: /san-pham/
 * Muốn chỉnh: copy file này vào theme con (astra-child/archive-san_pham.php).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="spc-hero">
	<div class="spc-wrap">
		<nav class="spc-breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
			<span>/</span>
			<strong>Sản phẩm</strong>
		</nav>
		<p class="spc-eyebrow">Danh mục</p>
		<h1 class="spc-hero__title">Sản phẩm</h1>
		<p class="spc-hero__lead">Giải pháp bao bì giấy trọn gói — từ thùng carton vận chuyển đến hộp cao cấp và vật phẩm trưng bày.</p>
	</div>
</section>

<section class="spc-listing">
	<div class="spc-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="spc-grid" style="--spc-cols:4">
				<?php
				while ( have_posts() ) {
					the_post();
					spc_render_card( get_the_ID() );
				}
				?>
			</div>

			<div class="spc-pagination">
				<?php
				echo paginate_links( array(
					'prev_text' => '←',
					'next_text' => '→',
				) );
				?>
			</div>
		<?php else : ?>
			<p>Chưa có sản phẩm nào.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
