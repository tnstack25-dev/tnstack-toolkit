<?php
/**
 * Hierarchical table of contents for posts.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', 'tnstack_toc_insert', 12 );
add_action( 'wp_enqueue_scripts', 'tnstack_toc_enqueue_styles' );

/**
 * @return array<string, mixed>
 */
function tnstack_toc_defaults() {
	return array(
		'theme'             => 'light',
		'heading_levels'    => array( 2, 3, 4 ),
		'minimum_headings'  => 3,
		'max_height'        => 360,
		'collapsible'       => 1,
		'collapsed_default' => 0,
	);
}

/**
 * @return array<string, mixed>
 */
function tnstack_toc_settings() {
	$settings = tnstack_module_get_settings( 'table-of-contents', tnstack_toc_defaults() );
	$levels   = isset( $settings['heading_levels'] ) && is_array( $settings['heading_levels'] )
		? array_map( 'absint', $settings['heading_levels'] )
		: array( 2, 3, 4 );

	$settings['heading_levels']    = array_values( array_intersect( array( 2, 3, 4, 5, 6 ), $levels ) );
	$settings['minimum_headings']  = min( 20, max( 1, absint( $settings['minimum_headings'] ?? 3 ) ) );
	$settings['max_height']        = min( 800, max( 200, absint( $settings['max_height'] ?? 360 ) ) );
	$settings['theme']             = in_array( $settings['theme'] ?? 'light', array( 'light', 'dark', 'auto' ), true ) ? $settings['theme'] : 'light';
	$settings['collapsible']       = ! empty( $settings['collapsible'] ) ? 1 : 0;
	$settings['collapsed_default'] = ! empty( $settings['collapsed_default'] ) ? 1 : 0;

	if ( empty( $settings['heading_levels'] ) ) {
		$settings['heading_levels'] = array( 2, 3, 4 );
	}

	return $settings;
}

/**
 * Enqueue the TOC stylesheet only on singular posts.
 */
function tnstack_toc_enqueue_styles() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$path = tnstack_core_path( 'assets/css/table-of-contents.css' );

	wp_enqueue_style(
		'tnstack-table-of-contents',
		tnstack_core_uri( 'assets/css/table-of-contents.css' ),
		array(),
		tnstack_core_asset_version( $path )
	);
}

/**
 * Render a nested ordered list.
 *
 * @param array<int, object> $nodes Heading tree nodes.
 * @param int                $depth Current depth.
 * @return string
 */
function tnstack_toc_render_nodes( $nodes, $depth = 1 ) {
	if ( empty( $nodes ) ) {
		return '';
	}

	$html = '<ol class="tnstack-toc__list tnstack-toc__list--depth-' . absint( $depth ) . '">';

	foreach ( $nodes as $node ) {
		$html .= '<li class="tnstack-toc__item tnstack-toc__item--h' . absint( $node->level ) . '" data-level="' . absint( $node->level ) . '" data-depth="' . absint( $depth ) . '">';
		$html .= '<a class="tnstack-toc__link" href="#' . esc_attr( $node->id ) . '">' . esc_html( $node->title ) . '</a>';
		$html .= tnstack_toc_render_nodes( $node->children, $depth + 1 );
		$html .= '</li>';
	}

	$html .= '</ol>';

	return $html;
}

/**
 * Build a semantic heading tree. A skipped level is attached to the nearest
 * preceding lower-level heading.
 *
 * @param array<int, array<string, mixed>> $headings Heading data.
 * @return array<int, object>
 */
function tnstack_toc_build_tree( $headings ) {
	$root = (object) array(
		'level'    => 1,
		'children' => array(),
	);

	/*
	 * Store the latest node at every real heading level. This keeps H3 under
	 * H2, H4 under H3, and falls back to the nearest preceding parent when an
	 * author skips a level (for example H2 followed directly by H4).
	 */
	$parents = array( 1 => $root );

	foreach ( $headings as $heading ) {
		$level = (int) $heading['level'];

		foreach ( array_keys( $parents ) as $parent_level ) {
			if ( $parent_level >= $level ) {
				unset( $parents[ $parent_level ] );
			}
		}

		$node = (object) array(
			'level'    => $level,
			'id'       => (string) $heading['id'],
			'title'    => (string) $heading['title'],
			'children' => array(),
		);

		$available_levels = array_keys( $parents );
		$parent_level     = max( $available_levels );
		$parent           = $parents[ $parent_level ];
		$parent->children[] = $node;
		$parents[ $level ]   = $node;
	}

	return $root->children;
}

/**
 * Insert the table of contents before the post content.
 *
 * @param string $content Post content.
 * @return string
 */
function tnstack_toc_insert( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$settings = tnstack_toc_settings();
	$levels   = $settings['heading_levels'];
	$pattern  = '/<h(' . implode( '|', array_map( 'absint', $levels ) ) . ')([^>]*)>(.*?)<\/h\1>/is';

	preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

	$valid_matches = array_values(
		array_filter(
			$matches,
			static function ( $match ) {
				return '' !== trim( wp_strip_all_tags( $match[3] ?? '' ) );
			}
		)
	);

	if ( count( $valid_matches ) < $settings['minimum_headings'] ) {
		return $content;
	}

	$headings = array();
	$index    = 0;

	$content = preg_replace_callback(
		$pattern,
		static function ( $match ) use ( &$headings, &$index ) {
			$title = trim( wp_strip_all_tags( $match[3] ) );

			if ( '' === $title ) {
				return $match[0];
			}

			$index++;
			$level = (int) $match[1];
			$attrs = $match[2];
			$id    = '';

			if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
				$id = trim( wp_strip_all_tags( $id_match[2] ) );
			}

			if ( '' === $id ) {
				$id    = 'tnstack-toc-' . $index;
				$attrs = rtrim( $attrs ) . ' id="' . esc_attr( $id ) . '"';
			}

			$headings[] = array(
				'level' => $level,
				'id'    => $id,
				'title' => $title,
			);

			return '<h' . $level . $attrs . '>' . $match[3] . '</h' . $level . '>';
		},
		$content
	);

	if ( ! is_string( $content ) || empty( $headings ) ) {
		return is_string( $content ) ? $content : '';
	}

	$theme_class = 'tnstack-toc--' . sanitize_html_class( $settings['theme'] );
	$toc_style   = '--tnstack-toc-max-height:' . absint( $settings['max_height'] ) . 'px';
	$count_label = sprintf(
		/* translators: %d: number of headings. */
		_n( '%d mục', '%d mục', count( $headings ), 'tnstack-toolkit' ),
		count( $headings )
	);
	$list_html = tnstack_toc_render_nodes( tnstack_toc_build_tree( $headings ) );
	$title     = esc_html__( 'Mục lục', 'tnstack-toolkit' );
	$label     = esc_attr__( 'Mục lục', 'tnstack-toolkit' );

	if ( $settings['collapsible'] ) {
		$open = $settings['collapsed_default'] ? '' : ' open';
		$toc  = '<details class="tnstack-toc ' . esc_attr( $theme_class ) . '" style="' . esc_attr( $toc_style ) . '"' . $open . '>';
		$toc .= '<summary class="tnstack-toc__summary"><span class="tnstack-toc__title">' . $title . '</span><span class="tnstack-toc__count">' . esc_html( $count_label ) . '</span><span class="tnstack-toc__chevron" aria-hidden="true"></span></summary>';
		$toc .= '<nav class="tnstack-toc__nav" aria-label="' . $label . '">' . $list_html . '</nav>';
		$toc .= '</details>';
	} else {
		$toc  = '<nav class="tnstack-toc ' . esc_attr( $theme_class ) . '" style="' . esc_attr( $toc_style ) . '" aria-label="' . $label . '">';
		$toc .= '<div class="tnstack-toc__header"><span class="tnstack-toc__title">' . $title . '</span><span class="tnstack-toc__count">' . esc_html( $count_label ) . '</span></div>';
		$toc .= '<div class="tnstack-toc__nav">' . $list_html . '</div>';
		$toc .= '</nav>';
	}

	return $toc . $content;
}

/**
 * Render module settings.
 */
function tnstack_toc_render_admin() {
	if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
		return;
	}

	$settings = tnstack_toc_settings();
	$saved    = false;

	if ( isset( $_POST['tnstack_toc_save'] ) ) {
		check_admin_referer( 'tnstack_toc_settings' );

		$levels = isset( $_POST['heading_levels'] ) && is_array( $_POST['heading_levels'] )
			? array_map( 'absint', wp_unslash( $_POST['heading_levels'] ) )
			: array();
		$levels = array_values( array_intersect( array( 2, 3, 4, 5, 6 ), $levels ) );

		if ( empty( $levels ) ) {
			$levels = array( 2, 3, 4 );
		}

		$theme = isset( $_POST['theme'] ) ? sanitize_key( wp_unslash( $_POST['theme'] ) ) : 'light';
		$theme = in_array( $theme, array( 'light', 'dark', 'auto' ), true ) ? $theme : 'light';

		$settings = tnstack_module_update_settings(
			'table-of-contents',
			array(
				'theme'             => $theme,
				'heading_levels'    => $levels,
				'minimum_headings'  => min( 20, max( 1, absint( $_POST['minimum_headings'] ?? 3 ) ) ),
				'max_height'        => min( 800, max( 200, absint( $_POST['max_height'] ?? 360 ) ) ),
				'collapsible'       => ! empty( $_POST['collapsible'] ) ? 1 : 0,
				'collapsed_default' => ! empty( $_POST['collapsed_default'] ) ? 1 : 0,
			),
			tnstack_toc_defaults()
		);
		$saved = true;
	}
	?>
	<div class="wrap ttk-settings ttk-settings--toc">
		<header class="ttk-settings__hero">
			<span class="ttk-settings__hero-icon"><span class="dashicons dashicons-list-view"></span></span>
			<div>
				<span class="ttk-settings__eyebrow"><?php esc_html_e( 'TNStack Toolkit', 'tnstack-toolkit' ); ?></span>
				<h1><?php esc_html_e( 'Table of Contents', 'tnstack-toolkit' ); ?></h1>
				<p><?php esc_html_e( 'Tạo mục lục phân cấp, dễ đọc và phù hợp với giao diện sáng hoặc tối.', 'tnstack-toolkit' ); ?></p>
			</div>
		</header>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cài đặt mục lục.', 'tnstack-toolkit' ); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'tnstack_toc_settings' ); ?>
			<section class="ttk-settings__card">
				<div class="ttk-settings__card-header">
					<h2><?php esc_html_e( 'Hiển thị mục lục', 'tnstack-toolkit' ); ?></h2>
					<p><?php esc_html_e( 'Chọn giao diện, cấp tiêu đề và kích thước phù hợp với nội dung bài viết.', 'tnstack-toolkit' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="tnstack_toc_theme"><?php esc_html_e( 'Giao diện', 'tnstack-toolkit' ); ?></label></th>
					<td>
						<select name="theme" id="tnstack_toc_theme">
							<option value="light" <?php selected( $settings['theme'], 'light' ); ?>><?php esc_html_e( 'Sáng', 'tnstack-toolkit' ); ?></option>
							<option value="dark" <?php selected( $settings['theme'], 'dark' ); ?>><?php esc_html_e( 'Tối', 'tnstack-toolkit' ); ?></option>
							<option value="auto" <?php selected( $settings['theme'], 'auto' ); ?>><?php esc_html_e( 'Theo thiết bị', 'tnstack-toolkit' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cấp tiêu đề', 'tnstack-toolkit' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( array( 2, 3, 4, 5, 6 ) as $level ) : ?>
								<label><input type="checkbox" name="heading_levels[]" value="<?php echo esc_attr( $level ); ?>" <?php checked( in_array( $level, $settings['heading_levels'], true ) ); ?>> H<?php echo esc_html( $level ); ?></label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Mục con tự động được lồng theo cấp H2–H6.', 'tnstack-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="minimum_headings"><?php esc_html_e( 'Số tiêu đề tối thiểu', 'tnstack-toolkit' ); ?></label></th>
					<td><input type="number" id="minimum_headings" name="minimum_headings" value="<?php echo esc_attr( $settings['minimum_headings'] ); ?>" min="1" max="20" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="max_height"><?php esc_html_e( 'Chiều cao mục lục', 'tnstack-toolkit' ); ?></label></th>
					<td>
						<input type="number" id="max_height" name="max_height" value="<?php echo esc_attr( $settings['max_height'] ); ?>" min="200" max="800" step="10" class="small-text"> px
						<p class="description"><?php esc_html_e( 'Danh sách sẽ tự cuộn khi vượt quá chiều cao này.', 'tnstack-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Thu gọn', 'tnstack-toolkit' ); ?></th>
					<td>
						<fieldset>
							<label class="ttk-settings__check"><input type="checkbox" name="collapsible" value="1" <?php checked( $settings['collapsible'] ); ?>> <?php esc_html_e( 'Cho phép mở/thu gọn mục lục', 'tnstack-toolkit' ); ?></label>
							<label class="ttk-settings__check"><input type="checkbox" name="collapsed_default" value="1" <?php checked( $settings['collapsed_default'] ); ?>> <?php esc_html_e( 'Thu gọn mặc định khi tải trang', 'tnstack-toolkit' ); ?></label>
						</fieldset>
					</td>
				</tr>
				</table>
			</section>
			<div class="ttk-settings__actions">
				<?php submit_button( __( 'Lưu thay đổi', 'tnstack-toolkit' ), 'primary', 'tnstack_toc_save' ); ?>
			</div>
		</form>
	</div>
	<?php
}
