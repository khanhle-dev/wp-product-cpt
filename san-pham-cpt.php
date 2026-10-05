<?php
/**
 * Plugin Name: Sản phẩm (Custom Post Type)
 * Description: Tạo post type "Sản phẩm" + trường thông tin riêng + shortcode lưới card [san_pham_grid] + template trang chi tiết.
 * Version:     1.3.1
 * Author:      Kelv
 * Text Domain: spc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Đã có một bản plugin khác đang chạy (cài trùng ở thư mục khác) -> dừng để tránh lỗi "Cannot redeclare".
if ( defined( 'SPC_VER' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>Sản phẩm (CPT):</strong> đang có 2 bản plugin được cài. Hãy tắt và xoá bản cũ, chỉ giữ một bản.</p></div>';
	} );
	return;
}

define( 'SPC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPC_URL', plugin_dir_url( __FILE__ ) );
define( 'SPC_VER', '1.3.1' );

/* =========================================================
 * 1. ĐĂNG KÝ POST TYPE
 * ======================================================= */
function spc_register_cpt() {
	$labels = array(
		'name'               => 'Sản phẩm',
		'singular_name'      => 'Sản phẩm',
		'menu_name'          => 'Sản phẩm',
		'add_new'            => 'Thêm sản phẩm',
		'add_new_item'       => 'Thêm sản phẩm mới',
		'edit_item'          => 'Sửa sản phẩm',
		'new_item'           => 'Sản phẩm mới',
		'view_item'          => 'Xem sản phẩm',
		'all_items'          => 'Tất cả sản phẩm',
		'search_items'       => 'Tìm sản phẩm',
		'not_found'          => 'Chưa có sản phẩm nào',
		'not_found_in_trash' => 'Thùng rác trống',
		'featured_image'     => 'Ảnh sản phẩm',
		'set_featured_image' => 'Chọn ảnh sản phẩm',
	);

	register_post_type(
		'san_pham', // Không dùng "product" để tránh xung đột nếu sau này cài WooCommerce.
		array(
			'labels'        => $labels,
			'public'        => true,
			// Nếu đã chọn "Trang danh sách (Elementor)" trong Cài đặt thì tắt archive
			// để trang /san-pham/ do Elementor thiết kế được hiển thị.
			'has_archive'   => spc_use_page() ? false : 'san-pham',
			'rewrite'       => array( 'slug' => 'san-pham', 'with_front' => false ), // /san-pham/ten-san-pham/
			'menu_icon'     => 'dashicons-archive',
			'menu_position' => 5,
			'show_in_rest'  => true,                             // Dùng được block editor
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'spc_register_cpt' );

// Làm mới permalink khi bật/tắt plugin (tránh lỗi 404).
register_activation_hook( __FILE__, function () {
	spc_register_cpt();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/* =========================================================
 * 2. TRƯỜNG THÔNG TIN RIÊNG (META BOX)
 * ======================================================= */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'spc_details', 'Thông tin sản phẩm', 'spc_render_meta_box', 'san_pham', 'normal', 'high' );
} );

function spc_render_meta_box( $post ) {
	wp_nonce_field( 'spc_save_meta', 'spc_meta_nonce' );

	$subtitle = get_post_meta( $post->ID, '_spc_subtitle', true );
	$features = get_post_meta( $post->ID, '_spc_features', true );
	$gallery  = spc_get_gallery( $post->ID );
	?>
	<style>
		.spc-field{margin:0 0 18px}
		.spc-field label{display:block;font-weight:600;margin-bottom:6px}
		.spc-field input[type=text],.spc-field textarea{width:100%}
		.spc-field .description{margin-top:4px}
		#spc-gallery-list{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;padding:0;list-style:none}
		#spc-gallery-list li{position:relative;width:96px;height:96px;margin:0;cursor:move;border-radius:6px;overflow:hidden;background:#f0f0f1}
		#spc-gallery-list img{width:100%;height:100%;object-fit:cover;display:block}
		#spc-gallery-list .spc-gallery-del{position:absolute;top:4px;right:4px;width:22px;height:22px;border:0;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;font-size:15px;line-height:22px;padding:0;cursor:pointer}
		#spc-gallery-list .ui-sortable-placeholder{visibility:visible!important;border:2px dashed #c3c4c7;background:none}
	</style>

	<div class="spc-field">
		<label for="spc_subtitle">Tên phụ (tiếng Anh)</label>
		<input type="text" id="spc_subtitle" name="spc_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" placeholder="VD: Corrugated shipping cases">
		<p class="description">Dòng chữ nhỏ viết hoa dưới tiêu đề.</p>
	</div>

	<div class="spc-field">
		<label for="spc_features">Danh sách đặc điểm</label>
		<textarea id="spc_features" name="spc_features" rows="6" placeholder="Mỗi dòng một ý&#10;Thùng carton 3, 5, 7 lớp&#10;Thùng xuất khẩu"><?php echo esc_textarea( $features ); ?></textarea>
		<p class="description">Mỗi dòng một đặc điểm. Card hiển thị tối đa 5 dòng đầu.</p>
	</div>

	<div class="spc-field">
		<label>Thư viện ảnh (trang chi tiết)</label>
		<input type="hidden" id="spc-gallery" name="spc_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>">
		<ul id="spc-gallery-list">
			<?php foreach ( $gallery as $gid ) : ?>
				<li data-id="<?php echo (int) $gid; ?>"><?php echo wp_get_attachment_image( $gid, 'thumbnail' ); ?><button type="button" class="spc-gallery-del" aria-label="Xoá ảnh">&times;</button></li>
			<?php endforeach; ?>
		</ul>
		<button type="button" class="button" id="spc-gallery-add">Thêm ảnh</button>
		<p class="description">Hiển thị dạng lưới 3 cột dưới ảnh chính, bấm vào ảnh để phóng to. Kéo thả để sắp xếp.</p>
	</div>

	<p class="description">
		Ảnh chính = <strong>Ảnh sản phẩm</strong> (featured image) ·
		Mô tả ngắn = <strong>Tóm tắt / Excerpt</strong> ·
		Đoạn nội dung chi tiết = khung soạn thảo chính ·
		Thứ tự trên lưới = <strong>Thuộc tính → Thứ tự</strong>.
	</p>
	<?php
}

add_action( 'save_post_san_pham', function ( $post_id ) {
	if ( ! isset( $_POST['spc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['spc_meta_nonce'] ), 'spc_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_spc_subtitle', sanitize_text_field( wp_unslash( $_POST['spc_subtitle'] ?? '' ) ) );
	update_post_meta( $post_id, '_spc_features', sanitize_textarea_field( wp_unslash( $_POST['spc_features'] ?? '' ) ) );
	$gallery = array_values( array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['spc_gallery'] ?? '' ) ) ) ) );
	update_post_meta( $post_id, '_spc_gallery', $gallery );
} );

// Script chọn ảnh từ thư viện Media.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $screen && 'san_pham' === $screen->post_type ) {
		wp_enqueue_media();
		wp_enqueue_script( 'spc-admin', SPC_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), SPC_VER, true );
	}
} );

// Thêm cột ảnh + thứ tự trong danh sách admin.
add_filter( 'manage_san_pham_posts_columns', function ( $cols ) {
	return array_slice( $cols, 0, 1, true )
		+ array( 'spc_thumb' => 'Ảnh' )
		+ array_slice( $cols, 1, 1, true )
		+ array( 'spc_order' => 'Thứ tự' )
		+ array_slice( $cols, 2, null, true );
} );
add_action( 'manage_san_pham_posts_custom_column', function ( $col, $post_id ) {
	if ( 'spc_thumb' === $col ) {
		echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
	}
	if ( 'spc_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	}
}, 10, 2 );

/* =========================================================
 * 3. CÀI ĐẶT CHUNG (SĐT, ZALO, LINK BÁO GIÁ)
 * ======================================================= */
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=san_pham', 'Cài đặt sản phẩm', 'Cài đặt', 'manage_options', 'spc-settings', 'spc_render_settings' );
} );

add_action( 'admin_init', function () {
	register_setting( 'spc_settings_group', 'spc_settings', array(
		'sanitize_callback' => function ( $in ) {
			return array(
				'phone'     => sanitize_text_field( $in['phone'] ?? '' ),
				'zalo'      => esc_url_raw( $in['zalo'] ?? '' ),
				'quote_url'    => esc_url_raw( $in['quote_url'] ?? '' ),
				'archive_page' => absint( $in['archive_page'] ?? 0 ),
			);
		},
	) );
} );

// Đổi trang danh sách -> cần làm mới permalink.
add_action( 'add_option_spc_settings', 'spc_schedule_flush' );
add_action( 'update_option_spc_settings', 'spc_schedule_flush' );
function spc_schedule_flush() {
	update_option( 'spc_need_flush', 1 );
}
/**
 * ID trang danh sách do người dùng tự làm (Elementor):
 * - trang chọn trong Cài đặt, hoặc
 * - bất kỳ Page nào đã xuất bản có đường dẫn /san-pham/ (tự nhận, tránh trùng URL).
 */
function spc_list_page_id() {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$id  = 0;
	$sel = (int) spc_option( 'archive_page' );
	if ( $sel && 'publish' === get_post_status( $sel ) ) {
		$id = $sel;
	} else {
		$page = get_page_by_path( 'san-pham', OBJECT, 'page' );
		if ( $page && 'publish' === $page->post_status ) {
			$id = (int) $page->ID;
		}
	}
	return $id;
}
function spc_use_page() {
	return (bool) spc_list_page_id();
}

// Khi trạng thái "dùng Page / dùng archive" thay đổi (tạo, xuất bản, xoá page san-pham…)
// thì tự làm mới permalink — không cần vào Cài đặt → Đường dẫn tĩnh.
add_action( 'init', function () {
	$state = spc_use_page() ? 'page' : 'archive';
	if ( get_option( 'spc_archive_state' ) !== $state ) {
		update_option( 'spc_archive_state', $state );
		flush_rewrite_rules();
		return;
	}
	if ( get_option( 'spc_need_flush' ) ) {
		delete_option( 'spc_need_flush' );
		flush_rewrite_rules();
	}
}, 99 );

function spc_render_settings() {
	$o = get_option( 'spc_settings', array() );
	?>
	<div class="wrap">
		<h1>Cài đặt sản phẩm</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'spc_settings_group' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="spc_phone">Số điện thoại</label></th>
					<td><input class="regular-text" id="spc_phone" name="spc_settings[phone]" value="<?php echo esc_attr( $o['phone'] ?? '' ); ?>" placeholder="096 321 0621"></td>
				</tr>
				<tr>
					<th><label for="spc_zalo">Link Zalo</label></th>
					<td><input class="regular-text" id="spc_zalo" name="spc_settings[zalo]" value="<?php echo esc_attr( $o['zalo'] ?? '' ); ?>" placeholder="https://zalo.me/0963210621">
					<p class="description">Để trống sẽ tự dùng https://zalo.me/&lt;số điện thoại&gt;.</p></td>
				</tr>
				<tr>
					<th><label for="spc_quote">Trang báo giá / liên hệ</label></th>
					<td><input class="regular-text" id="spc_quote" name="spc_settings[quote_url]" value="<?php echo esc_attr( $o['quote_url'] ?? '' ); ?>" placeholder="https://tenmien.vn/lien-he/">
					<p class="description">Nút "Báo giá sản phẩm này" sẽ trỏ về đây, kèm ?san-pham=Tên sản phẩm.</p></td>
				</tr>
				<tr>
					<th><label for="spc_archive_page">Trang danh sách (Elementor)</label></th>
					<td>
						<?php
						wp_dropdown_pages( array(
							'name'              => 'spc_settings[archive_page]',
							'id'                => 'spc_archive_page',
							'selected'          => (int) ( $o['archive_page'] ?? 0 ),
							'show_option_none'  => '— Dùng template có sẵn của plugin —',
							'option_none_value' => 0,
						) );
						?>
						<p class="description">Chọn một Page (slug <code>san-pham</code>) bạn tự thiết kế bằng Elementor, chèn shortcode <code>[san_pham_grid]</code> vào đó.<br>
						Nếu để trống, plugin tự nhận Page có đường dẫn /san-pham/ (nếu đã xuất bản). Khi có Page, trang danh sách tự động của plugin được tắt để không trùng URL.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* =========================================================
 * 4. HÀM TIỆN ÍCH
 * ======================================================= */
function spc_option( $key, $default = '' ) {
	$o = get_option( 'spc_settings', array() );
	return ( isset( $o[ $key ] ) && '' !== $o[ $key ] ) ? $o[ $key ] : $default;
}

function spc_get_features( $post_id ) {
	$raw = (string) get_post_meta( $post_id, '_spc_features', true );
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );
}

/**
 * Danh sách ID ảnh thư viện của sản phẩm.
 * Sản phẩm cũ chưa lưu thư viện thì lấy tạm ảnh nhà máy (trường cũ) làm ảnh đầu tiên.
 */
function spc_get_gallery( $post_id ) {
	if ( ! metadata_exists( 'post', $post_id, '_spc_gallery' ) ) {
		$legacy = (int) get_post_meta( $post_id, '_spc_factory_image', true );
		return $legacy ? array( $legacy ) : array();
	}
	$ids = (array) get_post_meta( $post_id, '_spc_gallery', true );
	return array_values( array_filter( array_map( 'absint', $ids ), 'wp_attachment_is_image' ) );
}

function spc_tel( $phone ) {
	return preg_replace( '/[^0-9+]/', '', $phone );
}

function spc_zalo_url() {
	$zalo = spc_option( 'zalo' );
	if ( $zalo ) {
		return $zalo;
	}
	$phone = spc_tel( spc_option( 'phone' ) );
	return $phone ? 'https://zalo.me/' . $phone : '';
}

function spc_quote_url( $title ) {
	$url = spc_option( 'quote_url', home_url( '/lien-he/' ) );
	return add_query_arg( 'san-pham', rawurlencode( $title ), $url );
}

/** Link trang danh sách sản phẩm (trang Elementor nếu có, không thì archive). */
function spc_list_url() {
	$page = spc_list_page_id();
	if ( $page ) {
		return get_permalink( $page );
	}
	return get_post_type_archive_link( 'san_pham' ) ?: home_url( '/san-pham/' );
}

/**
 * Vị trí cố định của sản phẩm theo "Thứ tự" (1, 2, 3…),
 * để một sản phẩm luôn mang cùng một số 01/02… ở mọi nơi.
 */
function spc_position( $post_id ) {
	static $map = null;
	if ( null === $map ) {
		$ids = get_posts( array(
			'post_type'      => 'san_pham',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'no_found_rows'  => true,
		) );
		$map = array_flip( $ids );
	}
	return isset( $map[ $post_id ] ) ? $map[ $post_id ] + 1 : 0;
}

/** In một card sản phẩm. */
function spc_render_card( $post_id, $index = null ) {
	if ( null === $index ) {
		$index = spc_position( $post_id );
	}
	include SPC_DIR . 'templates/card.php';
}

/* =========================================================
 * 5. SHORTCODE LƯỚI CARD  —  [san_pham_grid]
 *    Thuộc tính: limit="8" columns="4" ids="12,15,20"
 * ======================================================= */
add_shortcode( 'san_pham_grid', function ( $atts ) {
	$a = shortcode_atts( array(
		'limit'   => 8,
		'columns' => 4,
		'ids'     => '',
	), $atts, 'san_pham_grid' );

	$args = array(
		'post_type'      => 'san_pham',
		'posts_per_page' => (int) $a['limit'],
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	);
	if ( $a['ids'] ) {
		$args['post__in'] = array_map( 'absint', explode( ',', $a['ids'] ) );
		$args['orderby']  = 'post__in';
	}

	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '';
	}

	ob_start();
	echo '<div class="spc-grid" style="--spc-cols:' . max( 1, (int) $a['columns'] ) . '">';
	while ( $q->have_posts() ) {
		$q->the_post();
		spc_render_card( get_the_ID() );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
} );

/* =========================================================
 * 6. TEMPLATE TRANG CHI TIẾT + TRANG DANH SÁCH
 *    Muốn sửa HTML: copy file trong /templates vào theme con (astra-child),
 *    plugin sẽ ưu tiên file trong theme.
 * ======================================================= */
add_filter( 'template_include', function ( $template ) {
	if ( is_singular( 'san_pham' ) ) {
		return locate_template( 'single-san_pham.php' ) ?: SPC_DIR . 'templates/single-san_pham.php';
	}
	if ( is_post_type_archive( 'san_pham' ) ) {
		return locate_template( 'archive-san_pham.php' ) ?: SPC_DIR . 'templates/archive-san_pham.php';
	}
	return $template;
} );

// Trang danh sách: 12 sản phẩm/trang, sắp theo "Thứ tự".
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_post_type_archive( 'san_pham' ) ) {
		$q->set( 'posts_per_page', 12 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
} );

// Astra: bỏ sidebar trên các trang sản phẩm.
add_filter( 'astra_page_layout', function ( $layout ) {
	return ( is_singular( 'san_pham' ) || is_post_type_archive( 'san_pham' ) ) ? 'no-sidebar' : $layout;
} );

add_filter( 'body_class', function ( $classes ) {
	if ( is_singular( 'san_pham' ) || is_post_type_archive( 'san_pham' ) ) {
		$classes[] = 'spc-body';
	}
	return $classes;
} );

/* =========================================================
 * 7. CSS + FONT
 * ======================================================= */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'spc-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'spc-style', SPC_URL . 'assets/style.css', array(), SPC_VER );
	if ( is_singular( 'san_pham' ) ) {
		wp_enqueue_script( 'spc-lightbox', SPC_URL . 'assets/lightbox.js', array(), SPC_VER, true );
	}
} );
