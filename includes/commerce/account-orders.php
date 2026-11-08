<?php
/**
 * Provide an owner-only WooCommerce orders view via QQ Account's public
 * qqa_account_sections filter. No plugin code is changed.
 * List: number, status, purchased item previews. Details: order line items,
 * pricing and shipping/customer contact information within the same tab.
 */
defined( 'ABSPATH' ) || exit;

function ehk_qqa_orders_url() {
	$page_id = absint( get_option( 'qqa_account_page_id' ) );
	$url = $page_id && 'publish' === get_post_status( $page_id ) ? get_permalink( $page_id ) : '';
	return add_query_arg( 'section', 'orders', $url ? $url : home_url( '/account/' ) );
}

function ehk_qqa_order_is_owned( $order, $user_id ) {
	return $order && is_a( $order, 'WC_Order' ) && $user_id > 0
		&& (int) $order->get_customer_id() === (int) $user_id;
}

function ehk_qqa_register_orders_section( $sections ) {
	if ( ! EHK_Settings::qq_account_orders_enabled() || ! is_user_logged_in() ) {
		return $sections;
	}
	$sections['orders'] = array(
		'label'    => 'سفارش‌های من',
		'icon'     => '<svg class="qqa-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M3 10h18 M8 3v6 M16 3v6"/></svg>',
		'order'    => 15,
		'menu'     => true,
		'callback' => 'ehk_qqa_render_orders_section',
	);
	return $sections;
}
add_filter( 'qqa_account_sections', 'ehk_qqa_register_orders_section', 30 );

function ehk_qqa_render_orders_section( $id = '', $section = array() ) {
	?>
	<div class="ehk-account-orders" data-ehk-account-orders>
		<div class="RoW qq-section__header ehk-orders-header">
			<h3 class="QE" data-ehk-orders-heading>سفارش‌های من</h3>
			<span class="ehk-order-header-status" data-ehk-selected-status role="status" aria-live="polite" hidden></span>
			<button type="button" class="ehk-order-back qq-button qq-button--secondary" data-ehk-order-back hidden aria-label="بازگشت به سفارش‌ها">
				<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
				<span>بازگشت به سفارش‌ها</span>
			</button>
		</div>
		<div class="tp_box qq-form max ehk-orders-surface">
		<?php
		$user_id = get_current_user_id();
		if ( ! EHK_Settings::qq_account_orders_enabled() || ! $user_id || ! function_exists( 'wc_get_order_status_name' ) ) {
			echo '<p class="ehk-orders-empty">امکان نمایش سفارش‌ها در حال حاضر وجود ندارد.</p>';
			echo '</div></div>';
			return;
		}
		$page = isset( $_GET['orders_page'] ) && is_scalar( $_GET['orders_page'] )
			? max( 1, absint( wp_unslash( $_GET['orders_page'] ) ) ) : 1;
		$page = min( $page, 10000 );
		$results = wc_get_orders( array(
			'customer' => $user_id,
			'status'   => array_keys( wc_get_order_statuses() ),
			'limit'    => 15,
			'page'     => $page,
			'paginate' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'return'   => 'objects',
		) );
		$orders = is_object( $results ) && isset( $results->orders ) && is_array( $results->orders ) ? $results->orders : array();
		$total_pages = is_object( $results ) && isset( $results->max_num_pages ) ? max( 1, (int) $results->max_num_pages ) : 1;
		// Direct links to an order work even if it is on a different list page.
		// Never expose an order unless it belongs to the authenticated user.
		$requested_id = isset( $_GET['order_id'] ) && is_scalar( $_GET['order_id'] )
			? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
		$requested_order = $requested_id ? wc_get_order( $requested_id ) : false;
		if ( ! ehk_qqa_order_is_owned( $requested_order, $user_id ) ) {
			$requested_order = false;
		}
		?>
		<div class="ehk-orders-list" data-ehk-orders-list>
			<?php if ( ! $orders ) : ?>
				<p class="ehk-orders-empty">هنوز سفارشی ثبت نکرده‌اید.</p>
			<?php else : ?>
				<div class="ehk-orders-rows">
					<?php foreach ( $orders as $order ) :
						if ( ! ehk_qqa_order_is_owned( $order, $user_id ) ) {
							continue;
						}
						$order_id = (int) $order->get_id();
						$status = wc_get_order_status_name( $order->get_status() );
						?>
						<button type="button" class="ehk-order-row" data-ehk-order-open="<?php echo esc_attr( $order_id ); ?>" data-ehk-order-status="<?php echo esc_attr( $status ); ?>" aria-label="<?php echo esc_attr( 'مشاهده اقلام و جزئیات سفارش شماره ' . $order_id ); ?>">
							<span class="ehk-order-row-main">
								<span class="ehk-order-number" dir="rtl">سفارش #<?php echo esc_html( ehk_fa_digits( $order_id ) ); ?></span>
								<?php if ( $order->get_date_created() ) : ?>
									<span class="ehk-order-row-date"><?php echo esc_html( ehk_order_jalali_date( $order->get_date_created() ) ); ?></span>
								<?php endif; ?>
								<?php ehk_qqa_orders_preview( $order ); ?>
							</span>
							<span class="ehk-order-row-total">
								<small>مبلغ کل</small>
								<strong><?php echo wp_kses_post( ehk_order_fa_markup( $order->get_formatted_order_total() ) ); ?></strong>
							</span>
							<span class="ehk-order-status"><?php echo esc_html( $status ); ?></span>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>
						</button>
					<?php endforeach; ?>
				</div>
				<?php if ( $total_pages > 1 ) : ?>
					<nav class="ehk-orders-pagination" aria-label="صفحات سفارش‌ها">
						<?php if ( $page > 1 ) : ?>
							<a class="qq-button qq-button--secondary" href="<?php echo esc_url( add_query_arg( array( 'section' => 'orders', 'orders_page' => $page - 1 ), remove_query_arg( array( 'orders_page', 'order_id' ), ehk_qqa_orders_url() ) ) ); ?>">صفحه قبل</a>
						<?php endif; ?>
						<span><?php echo esc_html( ehk_fa_digits( $page ) . ' / ' . ehk_fa_digits( $total_pages ) ); ?></span>
						<?php if ( $page < $total_pages ) : ?>
							<a class="qq-button qq-button--secondary" href="<?php echo esc_url( add_query_arg( array( 'section' => 'orders', 'orders_page' => $page + 1 ), remove_query_arg( array( 'orders_page', 'order_id' ), ehk_qqa_orders_url() ) ) ); ?>">صفحه بعد</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php if ( $requested_order ) : ?>
			<button type="button" hidden data-ehk-order-open="<?php echo esc_attr( $requested_order->get_id() ); ?>" data-ehk-order-status="<?php echo esc_attr( wc_get_order_status_name( $requested_order->get_status() ) ); ?>"></button>
		<?php endif; ?>
		<div class="ehk-order-selected" data-ehk-order-selected hidden>
			<?php
			// One-time receipt message appears only in this owner's selected
			// order, never on the general orders list or unrelated orders.
			if ( $requested_order ) {
				echo ehk_order_account_first_confirmation( $requested_order, $user_id );
			}
			?>
			<div class="ehk-orders-detail-panels" data-ehk-order-detail-panels>
				<?php
				$rendered = array();
				foreach ( $orders as $order ) {
					if ( ! ehk_qqa_order_is_owned( $order, $user_id ) ) {
						continue;
					}
					$rendered[ (int) $order->get_id() ] = true;
					ehk_qqa_orders_render_full( $order );
				}
				if ( $requested_order && ! isset( $rendered[ (int) $requested_order->get_id() ] ) ) {
					ehk_qqa_orders_render_full( $requested_order );
				}
				?>
			</div>
		</div>
		</div>
	</div>
	<?php
}

function ehk_qqa_orders_assets() {
	if ( ! EHK_Settings::qq_account_orders_enabled() || ! is_user_logged_in()
		|| ! function_exists( 'qqa_is_account' ) || ! qqa_is_account() ) {
		return;
	}
	$uri = EHK_URL;
	wp_enqueue_style( 'ehk-account-orders', $uri . 'assets/css/account-orders.css', array( 'qoqnoos-account' ), EHK_VERSION );
	wp_enqueue_script( 'ehk-account-orders', $uri . 'assets/js/account-orders.js', array(), EHK_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'ehk_qqa_orders_assets', 140 );

