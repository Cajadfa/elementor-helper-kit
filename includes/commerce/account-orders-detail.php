<?php
/**
 * Server-rendered private WooCommerce order information for the QQ Account tab.
 *
 * Never enqueue or expose these fragments without checking customer ownership.
 * WooCommerce's order/item APIs support HPOS without direct SQL or post meta.
 */
defined( 'ABSPATH' ) || exit;

/**
 * A concise description based solely on metadata SAVED on this order item
 * (not the live bottle/post, whose capacity/title may have changed).
 * Older products without perfume metadata keep a plain quantity label.
 */
function ehk_qqa_order_item_summary( $item ) {
	$quantity = max( 1, (int) $item->get_quantity() );
	$summary  = ehk_fa_digits( $quantity ) . ' عدد';

	if ( ! method_exists( $item, 'get_meta' ) ) {
		return $summary;
	}
	$fill = $item->get_meta( '_pps_fill_mg', true );
	if ( ! is_scalar( $fill ) || '' === (string) $fill ) {
		$fill = $item->get_meta( 'مقدار عطر', true );
	}
	$fill_text = is_scalar( $fill ) ? wp_strip_all_tags( (string) $fill ) : '';
	$mg = '';
	if ( preg_match( '/[0-9۰-۹٠-٩]+/u', $fill_text, $match ) ) {
		$mg = ehk_fa_digits( $match[0] );
	}
	if ( '' !== $mg ) {
		$summary .= ' عطر ' . $mg . ' میلی‌گرمی';
	}

	$bottle = $item->get_meta( 'شیشه انتخابی', true );
	$bottle = is_scalar( $bottle ) ? trim( wp_strip_all_tags( (string) $bottle ) ) : '';
	if ( '' === $bottle ) {
		// Some historical orders have only an internal bottle ID, without
		// a saved bottle name. Never replace history with a current title.
		return $summary;
	}
	$parts = preg_split( '/\s+[—–]\s+/u', $bottle, 2 );
	$bottle = trim( $parts[0] );
	if ( '' !== $bottle ) {
		$summary .= ' با ' . ( preg_match( '/^شیشه(?:\s|$)/u', $bottle ) ? $bottle : 'شیشه ' . $bottle );
	}
	return ehk_fa_digits( $summary );
}

/**
 * Short purchased-item names shown on the order list.
 * Returns only names/quantities of LINE ITEMS on the authorized order.
 */
function ehk_qqa_orders_preview( $order ) {
	if ( ! EHK_Settings::qq_account_orders_enabled() || ! ehk_qqa_order_is_owned( $order, get_current_user_id() ) ) {
		return;
	}
	$items = $order->get_items( 'line_item' );
	if ( ! $items ) {
		echo '<span class="ehk-orders-preview-empty">بدون کالای ثبت‌شده</span>';
		return;
	}
	echo '<span class="ehk-orders-item-previews">';
	$visible = 0;
	foreach ( $items as $item ) {
		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			continue;
		}
		++$visible;
		echo '<span class="ehk-orders-preview-item"><span class="ehk-orders-preview-name">' . esc_html( ehk_fa_digits( $item->get_name() ) ) . '</span>';
		echo '<small class="ehk-orders-preview-summary">' . esc_html( ehk_qqa_order_item_summary( $item ) ) . '</small></span>';
		if ( $visible === 4 ) {
			$remaining = count( $items ) - $visible;
			if ( $remaining > 0 ) {
				echo '<span class="ehk-orders-preview-more">و ' . esc_html( ehk_fa_digits( $remaining ) ) . ' مورد دیگر</span>';
			}
			break;
		}
	}
	echo '</span>';
}

/**
 * Render the full invoice overview and delivery information only to its owner.
 * All fields are retrieved from WC_Order objects, not from request parameters.
 */
function ehk_qqa_orders_render_full( $order ) {
	if ( ! EHK_Settings::qq_account_orders_enabled() || ! ehk_qqa_order_is_owned( $order, get_current_user_id() ) ) {
		return;
	}
	$order_id = (int) $order->get_id();
	?>
	<section class="ehk-orders-full" data-ehk-order-detail-id="<?php echo esc_attr( $order_id ); ?>" hidden aria-label="<?php echo esc_attr( 'جزئیات سفارش ' . $order_id ); ?>">
		<h4 class="ehk-orders-section-title">اقلام خریداری‌شده</h4>
		<div class="ehk-orders-purchased-items">
		<?php foreach ( $order->get_items( 'line_item' ) as $item ) :
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$product = $item->get_product();
			$image = $product && $product->exists()
				? $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'class' => 'ehk-order-product-image' ) )
				: ( function_exists( 'wc_placeholder_img' ) ? wc_placeholder_img( 'woocommerce_thumbnail' ) : '' );
			$metadata = function_exists( 'wc_display_item_meta' )
				? wc_display_item_meta( $item, array( 'echo' => false ) ) : '';
			?>
			<article class="ehk-orders-purchased-item">
				<?php if ( $image ) : ?><div class="ehk-orders-purchased-thumb"><?php echo wp_kses_post( $image ); ?></div><?php endif; ?>
				<div class="ehk-orders-purchased-info">
					<strong class="ehk-orders-product-title"><?php echo esc_html( ehk_fa_digits( $item->get_name() ) ); ?></strong>
					<?php if ( $metadata ) : ?><div class="ehk-orders-purchased-meta"><?php echo wp_kses_post( ehk_order_fa_markup( $metadata ) ); ?></div><?php endif; ?>
					<span class="ehk-orders-product-qty">تعداد: <?php echo esc_html( ehk_fa_digits( $item->get_quantity() ) ); ?></span>
				</div>
				<div class="ehk-orders-purchased-price"><?php echo wp_kses_post( ehk_order_fa_markup( $order->get_formatted_line_subtotal( $item ) ) ); ?></div>
			</article>
		<?php endforeach; ?>
		</div>
		<div class="ehk-orders-order-total"><span>مبلغ کل سفارش</span><strong><?php echo wp_kses_post( ehk_order_fa_markup( $order->get_formatted_order_total() ) ); ?></strong></div>

		<div class="ehk-orders-address-grid">
			<section class="ehk-orders-address-card">
				<h4 class="ehk-orders-section-title">اطلاعات ارسال و تحویل</h4>
				<?php
				$shipping = $order->get_formatted_shipping_address();
				$billing = $order->get_formatted_billing_address();
				$address = $shipping ? $shipping : $billing;
				?>
				<?php if ( $address ) : ?>
					<address><?php echo wp_kses_post( ehk_order_fa_markup( $address ) ); ?></address>
				<?php else : ?>
					<p class="ehk-orders-not-available">آدرسی برای این سفارش ثبت نشده است.</p>
				<?php endif; ?>
				<?php if ( $order->get_billing_phone() ) : ?>
					<p class="ehk-orders-contact"><span>شماره تماس</span><b dir="ltr"><?php echo esc_html( ehk_fa_digits( $order->get_billing_phone() ) ); ?></b></p>
				<?php endif; ?>
				<?php if ( $order->get_billing_email() ) : ?>
					<p class="ehk-orders-contact"><span>ایمیل</span><b dir="ltr"><?php echo esc_html( $order->get_billing_email() ); ?></b></p>
				<?php endif; ?>
				<?php if ( $order->get_shipping_method() ) : ?>
					<p class="ehk-orders-contact"><span>روش ارسال</span><b><?php echo esc_html( ehk_fa_digits( $order->get_shipping_method() ) ); ?></b></p>
				<?php endif; ?>
				<?php if ( $order->get_customer_note() ) : ?>
					<p class="ehk-orders-customer-note"><span>توضیحات تحویل</span><?php echo nl2br( esc_html( ehk_fa_digits( $order->get_customer_note() ) ) ); ?></p>
				<?php endif; ?>
			</section>
			<section class="ehk-orders-address-card">
				<h4 class="ehk-orders-section-title">اطلاعات سفارش</h4>
				<div class="ehk-orders-facts">
					<div><span>شماره سفارش</span><strong>#<?php echo esc_html( ehk_fa_digits( $order_id ) ); ?></strong></div>
					<div><span>وضعیت سفارش</span><strong><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></strong></div>
					<?php if ( $order->get_date_created() ) : ?>
						<div><span>زمان ثبت سفارش</span><strong><?php echo esc_html( ehk_order_jalali_date( $order->get_date_created(), true ) ); ?></strong></div>
					<?php endif; ?>
					<?php if ( $order->get_payment_method_title() ) : ?>
						<div><span>روش پرداخت</span><strong><?php echo esc_html( ehk_fa_digits( $order->get_payment_method_title() ) ); ?></strong></div>
					<?php endif; ?>
				</div>
			</section>
		</div>
	</section>
	<?php
}

