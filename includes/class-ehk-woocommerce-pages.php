<?php
/** Explicit admin-only classic page recreation and optional QQ account routing. */
defined( 'ABSPATH' ) || exit;

class EHK_WooCommerce_Pages {
	const ACTION = 'ehk_recreate_woocommerce_pages';
	const LOCK = 'ehk_recreate_woocommerce_pages_lock';

	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_recreate' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_my_account' ), 9 );
	}

	public static function definitions() {
		return array(
			'cart' => array( 'title' => 'سبد خرید', 'shortcode' => '[woocommerce_cart]' ),
			'checkout' => array( 'title' => 'تسویه حساب', 'shortcode' => '[woocommerce_checkout]' ),
			'my-account' => array( 'title' => 'حساب کاربری', 'shortcode' => '[woocommerce_my_account]' ),
		);
	}

	private static function option_name( $slug ) {
		return 'woocommerce_' . ( 'my-account' === $slug ? 'myaccount' : $slug ) . '_page_id';
	}

	/** No page or WooCommerce setting changes before the preflight succeeds. */
	private static function previous_pages() {
		$previous = array();
		$protected = array_filter( array_map( 'absint', array(
			get_option( 'page_on_front' ), get_option( 'page_for_posts' ),
			get_option( 'wp_page_for_privacy_policy' ), get_option( 'qqa_account_page_id' ),
			get_option( 'woocommerce_shop_page_id' ), get_option( 'woocommerce_terms_page_id' ),
		) ) );
		foreach ( self::definitions() as $slug => $definition ) {
			$id = absint( get_option( self::option_name( $slug ) ) );
			$page = $id ? get_post( $id ) : null;
			if ( ! $page || 'trash' === $page->post_status ) {
				continue;
			}
			if ( 'page' !== $page->post_type || in_array( $id, $protected, true )
				|| 'account' === $page->post_name
				|| in_array( get_page_template_slug( $id ), array( 'qqa-account.php', 'page-account.php' ), true ) ) {
				return new WP_Error( 'protected_page', 'یکی از برگه‌های فعلی با برگه فروشگاه، صفحه اصلی یا QQ Account مشترک است. ابتدا این انتساب مشترک را در تنظیمات جدا کنید.' );
			}
			if ( ! current_user_can( 'delete_post', $id ) || ! current_user_can( 'edit_post', $id ) ) {
				return new WP_Error( 'page_permission', 'اجازه ویرایش یا حذف یکی از برگه‌های فعلی را ندارید.' );
			}
			$previous[ $id ] = clone $page;
		}
		foreach ( self::definitions() as $slug => $definition ) {
			$occupant = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $occupant && ! isset( $previous[ $occupant->ID ] ) ) {
				return new WP_Error( 'slug_conflict', sprintf( 'آدرس %s متعلق به برگه‌ای خارج از برگه‌های فعلی ووکامرس است. ابتدا آدرس یا انتساب آن را اصلاح کنید؛ این برگه حذف نشد.', $slug ) );
			}
		}
		return $previous;
	}

	/** Keep old content recoverable, even on sites with WordPress Trash disabled. */
	private static function trash_page( $page ) {
		if ( defined( 'EMPTY_TRASH_DAYS' ) && 0 === (int) EMPTY_TRASH_DAYS ) {
			$result = wp_update_post( array(
				'ID' => $page->ID,
				'post_status' => 'trash',
				'post_name' => $page->post_name . '__trashed-' . $page->ID,
				'meta_input' => array(
					'_wp_trash_meta_status' => $page->post_status,
					'_wp_trash_meta_time' => time(),
					'_wp_desired_post_slug' => $page->post_name,
				),
			), true );
		} else {
			$result = wp_trash_post( $page->ID );
		}
		if ( ! $result || is_wp_error( $result ) || 'trash' !== get_post_status( $page->ID ) ) {
			throw new RuntimeException( 'انتقال یکی از برگه‌های قبلی به سطل زباله انجام نشد.' );
		}
		// Some installations disable WordPress's automatic trashed-slug suffix.
		if ( get_post( $page->ID )->post_name === $page->post_name ) {
			$result = wp_update_post( array( 'ID' => $page->ID, 'post_name' => $page->post_name . '__trashed-' . $page->ID ), true );
			if ( ! $result || is_wp_error( $result ) ) {
				throw new RuntimeException( 'آدرس یکی از برگه‌های قبلی آزاد نشد.' );
			}
		}
	}

	private static function rollback( $created, $previous, $options ) {
		$ok = true;
		// Delete only the fresh pages made by this operation, freeing canonical slugs.
		foreach ( $created as $id ) {
			if ( ! wp_delete_post( $id, true ) ) {
				$ok = false;
			}
		}
		foreach ( $previous as $id => $page ) {
			if ( 'trash' === get_post_status( $id ) ) {
				wp_untrash_post( $id );
			}
			$result = wp_update_post( array( 'ID' => $id, 'post_status' => $page->post_status, 'post_name' => $page->post_name ), true );
			$restored = get_post( $id );
			if ( ! $result || is_wp_error( $result ) || ! $restored
				|| $restored->post_name !== $page->post_name || $restored->post_status !== $page->post_status ) {
				$ok = false;
			}
		}
		foreach ( $options as $key => $value ) {
			if ( null === $value ) {
				delete_option( $key );
			} else {
				update_option( $key, $value );
			}
			if ( get_option( $key, null ) !== $value ) {
				$ok = false;
			}
		}
		delete_transient( 'wc_cache_excluded_uris' );
		return $ok;
	}

	/** Returns the new page IDs or an actionable error; never runs on activation. */
	public static function recreate() {
		if ( ! class_exists( 'WooCommerce' ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
			return new WP_Error( 'unavailable', 'بازسازی برگه‌ها به ووکامرس فعال و دسترسی مدیر برای انتشار برگه نیاز دارد.' );
		}
		if ( ! add_option( self::LOCK, time(), '', false ) ) {
			return new WP_Error( 'busy', 'بازسازی دیگری در حال اجراست. پس از پایان آن دوباره تلاش کنید.' );
		}
		$created = array();
		$touched = array();
		$options = array();
		try {
			$previous = self::previous_pages();
			if ( is_wp_error( $previous ) ) {
				return $previous;
			}
			foreach ( self::definitions() as $slug => $definition ) {
				$key = self::option_name( $slug );
				$options[ $key ] = get_option( $key, null );
			}
			// Prepare all three fresh pages before touching any existing page.
			foreach ( self::definitions() as $slug => $definition ) {
				$id = wp_insert_post( array(
					'post_type' => 'page', 'post_status' => 'draft', 'post_parent' => 0,
					'post_title' => $definition['title'], 'post_name' => 'ehk-' . $slug . '-' . wp_generate_uuid4(),
					'post_content' => $definition['shortcode'], 'comment_status' => 'closed', 'ping_status' => 'closed',
					'meta_input' => array( '_wp_page_template' => 'default' ),
				), true );
				if ( is_wp_error( $id ) || ! $id ) {
					throw new RuntimeException( 'ساخت برگه جدید انجام نشد.' );
				}
				$created[ $slug ] = (int) $id;
			}
			foreach ( $previous as $page ) {
				$touched[ $page->ID ] = $page;
				self::trash_page( $page );
			}
			foreach ( $created as $slug => $id ) {
				$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish', 'post_name' => $slug ), true );
				$page = get_post( $id );
				if ( is_wp_error( $result ) || ! $result || ! $page || $page->post_name !== $slug || 'publish' !== $page->post_status ) {
					throw new RuntimeException( 'انتشار برگه با آدرس دقیق درخواستی انجام نشد.' );
				}
			}
			foreach ( $created as $slug => $id ) {
				$key = self::option_name( $slug );
				update_option( $key, $id );
				if ( (int) get_option( $key ) !== $id ) {
					throw new RuntimeException( 'ثبت برگه‌های جدید در تنظیمات ووکامرس انجام نشد.' );
				}
			}
			delete_transient( 'wc_cache_excluded_uris' );
			flush_rewrite_rules( false );
			return $created;
		} catch ( Throwable $error ) {
			$restored = self::rollback( $created, $touched, $options );
			return new WP_Error( 'recreate_failed', $error->getMessage() . ( $restored
				? ' وضعیت برگه‌ها و تنظیمات قبلی بازیابی شد.'
				: ' بازیابی کامل انجام نشد؛ برگه‌ها و تنظیمات ووکامرس را بررسی کنید.' ) );
		} finally {
			delete_option( self::LOCK );
		}
	}

	public static function handle_recreate() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( 'اجازه انجام این عملیات را ندارید.', '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION );
		$result = self::recreate();
		set_transient( 'ehk_wc_pages_notice_' . get_current_user_id(), array(
			'success' => ! is_wp_error( $result ),
			'message' => is_wp_error( $result ) ? $result->get_error_message() : 'برگه‌های cart، checkout و my-account با شورت‌کد ساخته و در ووکامرس تنظیم شدند. برگه‌های قبلی به سطل زباله منتقل شدند.',
		), 2 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . EHK_Settings::PAGE ) );
		exit;
	}

	/** Separate POST form: never nest this action inside the settings-save form. */
	public static function render_tool() {
		if ( ! class_exists( 'WooCommerce' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$key = 'ehk_wc_pages_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( is_array( $notice ) ) {
			delete_transient( $key );
			echo '<div class="notice ' . ( ! empty( $notice['success'] ) ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}
		?>
		<div class="ehk-card">
			<h2>ایجاد مجدد برگه‌های فروشگاه</h2>
			<p>برگه‌های فعلی سبد خرید، تسویه‌حساب و حساب کاربری به سطل زباله منتقل و برگه‌های تازه با قالب پیش‌فرض برگه و شورت‌کدهای ووکامرس ساخته می‌شوند. برگه اصلی فروشگاه و محصولات تغییر نمی‌کنند.</p>
			<p class="ehk-note"><code>cart → [woocommerce_cart]</code><br><code>checkout → [woocommerce_checkout]</code><br><code>my-account → [woocommerce_my_account]</code><br>برگه‌های جدید خودکار در تنظیمات ووکامرس انتخاب می‌شوند. این دکمه تنظیمات ذخیره‌نشده فرم بالا را ذخیره نمی‌کند.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<?php submit_button( 'ایجاد مجدد برگه‌های فروشگاه', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public static function account_redirect_target() {
		if ( ! EHK_Settings::qq_myaccount_redirect_enabled() || ! function_exists( 'is_account_page' ) || ! is_account_page()
			|| ( function_exists( 'qqa_is_account' ) && qqa_is_account() )
			|| 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return '';
		}
		// WooCommerce's password-reset links and logout action must keep working.
		if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'lost-password' ) || is_wc_endpoint_url( 'customer-logout' ) ) ) {
			return '';
		}
		$id = absint( get_option( 'qqa_account_page_id' ) );
		$page = $id ? get_post( $id ) : null;
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			$page = get_page_by_path( 'account', OBJECT, 'page' );
		}
		if ( ! $page || 'publish' !== $page->post_status || (int) $page->ID === (int) get_queried_object_id() ) {
			return '';
		}
		$target = wp_validate_redirect( get_permalink( $page->ID ), '' );
		if ( $target && EHK_Settings::qq_account_orders_enabled() && function_exists( 'is_wc_endpoint_url' ) ) {
			if ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ) {
				$target = add_query_arg( 'section', 'orders', $target );
				if ( is_wc_endpoint_url( 'view-order' ) ) {
					$target = add_query_arg( 'order_id', absint( get_query_var( 'view-order' ) ), $target );
				}
			}
		}
		return $target;
	}

	public static function redirect_my_account() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$target = self::account_redirect_target();
		if ( $target ) {
			wp_safe_redirect( $target, 302, 'Elementor Helper Kit' );
			exit;
		}
	}
}
