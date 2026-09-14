<?php
/**
 * Hide plugin updates for selected third-party plugins.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

const TNSTACK_BLOCKED_PLUGIN_UPDATES_OPTION = 'tnstack_blocked_plugin_updates';

function tnstack_get_blocked_plugin_updates() {
	$value = get_option( TNSTACK_BLOCKED_PLUGIN_UPDATES_OPTION, array() );
	return is_array( $value ) ? array_values( array_unique( array_map( 'plugin_basename', $value ) ) ) : array();
}

function tnstack_filter_blocked_plugin_updates( $value ) {
	if ( ! is_object( $value ) ) {
		return $value;
	}

	foreach ( tnstack_get_blocked_plugin_updates() as $plugin ) {
		if ( isset( $value->response[ $plugin ] ) ) {
			unset( $value->response[ $plugin ] );
		}
	}
	return $value;
}
add_filter( 'site_transient_update_plugins', 'tnstack_filter_blocked_plugin_updates', 1000 );

function tnstack_disable_selected_plugin_auto_update( $update, $item ) {
	$plugin = isset( $item->plugin ) ? plugin_basename( $item->plugin ) : '';
	return in_array( $plugin, tnstack_get_blocked_plugin_updates(), true ) ? false : $update;
}
add_filter( 'auto_update_plugin', 'tnstack_disable_selected_plugin_auto_update', 1000, 2 );

function tnstack_block_plugin_updates_register_settings() {
	register_setting(
		'tnstack_block_plugin_updates_group',
		TNSTACK_BLOCKED_PLUGIN_UPDATES_OPTION,
		array( 'sanitize_callback' => 'tnstack_block_plugin_updates_sanitize' )
	);
}
add_action( 'admin_init', 'tnstack_block_plugin_updates_register_settings' );
add_filter( 'option_page_capability_tnstack_block_plugin_updates_group', static function () { return 'manage_options'; } );

function tnstack_block_plugin_updates_sanitize( $input ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$installed = array_keys( get_plugins() );
	$selected  = is_array( $input ) ? array_map( 'plugin_basename', $input ) : array();
	$selected  = array_values( array_intersect( $selected, $installed ) );
	delete_site_transient( 'update_plugins' );
	return $selected;
}

function tnstack_block_plugin_updates_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugins = get_plugins();
	$blocked = tnstack_get_blocked_plugin_updates();
	$active  = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_unique( array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ) );
	}
	?>
	<div class="wrap ttk-block-updates">
		<h1>Chặn cập nhật plugin</h1>
		<p>Chọn các plugin không được hiển thị bản cập nhật và không được tự động cập nhật. Bỏ chọn để nhận cập nhật lại bình thường.</p>
		<div class="ttk-update-summary"><strong><span data-count-blocked><?php echo esc_html( count( $blocked ) ); ?></span> plugin đang bị chặn</strong><span><?php echo esc_html( count( $plugins ) ); ?> plugin đã cài</span></div>
		<form method="post" action="options.php">
			<?php settings_fields( 'tnstack_block_plugin_updates_group' ); ?>
			<input type="hidden" name="<?php echo esc_attr( TNSTACK_BLOCKED_PLUGIN_UPDATES_OPTION ); ?>[]" value="">
			<div class="ttk-update-tools"><input type="search" class="regular-text" data-plugin-search placeholder="Tìm theo tên hoặc đường dẫn plugin…"><select data-plugin-filter><option value="all">Tất cả plugin</option><option value="active">Đang bật</option><option value="inactive">Đang tắt</option><option value="blocked">Đang bị chặn</option><option value="allowed">Được cập nhật</option></select><button type="button" class="button" data-select-visible>Chọn kết quả đang hiện</button><button type="button" class="button" data-clear-all>Bỏ chọn tất cả</button></div>
			<table class="widefat striped"><thead><tr><td class="check-column"></td><th>Plugin</th><th>Phiên bản</th><th>Tác giả</th><th>Trạng thái</th></tr></thead><tbody>
			<?php foreach ( $plugins as $file => $data ) : ?>
			<?php $is_active = in_array( $file, $active, true ); $is_blocked = in_array( $file, $blocked, true ); ?>
			<tr data-plugin-row data-search="<?php echo esc_attr( strtolower( ( $data['Name'] ?: '' ) . ' ' . $file ) ); ?>" data-active="<?php echo $is_active ? '1' : '0'; ?>"><th class="check-column"><input type="checkbox" name="<?php echo esc_attr( TNSTACK_BLOCKED_PLUGIN_UPDATES_OPTION ); ?>[]" value="<?php echo esc_attr( $file ); ?>" <?php checked( $is_blocked ); ?>></th>
			<td><strong><?php echo esc_html( $data['Name'] ?: $file ); ?></strong><br><code><?php echo esc_html( $file ); ?></code></td><td><?php echo esc_html( $data['Version'] ); ?></td><td><?php echo wp_kses_post( $data['Author'] ); ?></td><td><?php echo in_array( $file, $active, true ) ? '<span style="color:#008a20">Đang bật</span>' : 'Đang tắt'; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<?php submit_button( 'Lưu danh sách chặn cập nhật' ); ?>
		</form>
	</div>
	<style>.ttk-block-updates{max-width:1200px}.ttk-update-summary{display:flex;justify-content:space-between;margin:18px 0;padding:18px 20px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1e3a8a}.ttk-update-tools{display:flex;flex-wrap:wrap;gap:9px;align-items:center;margin:16px 0}.ttk-update-tools input{min-width:300px}.ttk-block-updates tr[hidden]{display:none}.ttk-block-updates tbody tr:has(input:checked) td,.ttk-block-updates tbody tr:has(input:checked) th{background:#fff7ed}</style>
	<script>document.addEventListener('DOMContentLoaded',function(){var root=document.querySelector('.ttk-block-updates'),rows=Array.from(root.querySelectorAll('[data-plugin-row]')),search=root.querySelector('[data-plugin-search]'),filter=root.querySelector('[data-plugin-filter]'),count=root.querySelector('[data-count-blocked]');function updateCount(){count.textContent=rows.filter(function(row){return row.querySelector('input').checked;}).length;}function apply(){var q=search.value.toLowerCase().trim(),f=filter.value;rows.forEach(function(row){var checked=row.querySelector('input').checked,active=row.dataset.active==='1',match=!q||row.dataset.search.indexOf(q)!==-1;match=match&&(f==='all'||(f==='active'&&active)||(f==='inactive'&&!active)||(f==='blocked'&&checked)||(f==='allowed'&&!checked));row.hidden=!match;});}search.addEventListener('input',apply);filter.addEventListener('change',apply);root.querySelector('[data-select-visible]').addEventListener('click',function(){rows.forEach(function(row){if(!row.hidden)row.querySelector('input').checked=true;});updateCount();apply();});root.querySelector('[data-clear-all]').addEventListener('click',function(){rows.forEach(function(row){row.querySelector('input').checked=false;});updateCount();apply();});rows.forEach(function(row){row.querySelector('input').addEventListener('change',function(){updateCount();if(filter.value==='blocked'||filter.value==='allowed')apply();});});});</script>
	<?php
}
