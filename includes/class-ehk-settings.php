<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EHK_Settings {
	const OPTION = 'ehk_settings';
	const PAGE   = 'elementor-helper-kit';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function activate() {
		$defaults = array(
			'force_full_motion'          => 1,
			'elementor_css_first'       => 1,
			'hide_frontend_admin_bar'   => 1,
			'elementor_cache_toolbar'   => 1,
		);

		$settings = get_option( self::OPTION, false );

		if ( false === $settings || ! is_array( $settings ) ) {
			add_option( self::OPTION, $defaults );
			return;
		}

		// Preserve existing choices while making newly introduced options default to ON.
		update_option( self::OPTION, wp_parse_args( $settings, $defaults ) );
	}

	public static function get( $key, $default = null ) {
		$settings = get_option( self::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	public static function force_full_motion_enabled() {
		return (bool) self::get( 'force_full_motion', 1 );
	}

	public static function elementor_css_first_enabled() {
		return (bool) self::get( 'elementor_css_first', 1 );
	}

	public static function hide_frontend_admin_bar_enabled() {
		return (bool) self::get( 'hide_frontend_admin_bar', 1 );
	}

	public static function elementor_cache_toolbar_enabled() {
		return (bool) self::get( 'elementor_cache_toolbar', 1 );
	}

	public static function register_menu() {
		add_options_page(
			__( 'Elementor Helper Kit', 'elementor-helper-kit' ),
			__( 'Elementor Helper Kit', 'elementor-helper-kit' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'ehk_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(
					'force_full_motion'        => 1,
					'elementor_css_first'     => 1,
					'hide_frontend_admin_bar' => 1,
					'elementor_cache_toolbar' => 1,
				),
			)
		);
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'force_full_motion'        => empty( $input['force_full_motion'] ) ? 0 : 1,
			'elementor_css_first'     => empty( $input['elementor_css_first'] ) ? 0 : 1,
			'hide_frontend_admin_bar' => empty( $input['hide_frontend_admin_bar'] ) ? 0 : 1,
			'elementor_cache_toolbar' => empty( $input['elementor_cache_toolbar'] ) ? 0 : 1,
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$motion_enabled             = self::force_full_motion_enabled();
		$css_priority_enabled        = self::elementor_css_first_enabled();
		$hide_admin_bar_enabled      = self::hide_frontend_admin_bar_enabled();
		$cache_toolbar_enabled       = self::elementor_cache_toolbar_enabled();
		?>
		<div class="wrap ehk-settings-wrap">
			<h1><?php echo esc_html__( 'Elementor Helper Kit', 'elementor-helper-kit' ); ?></h1>
			<p class="description">ابزارهای کمکی برای توسعه و مدیریت راحت‌تر سایت‌های المنتوری.</p>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ehk_settings_group' ); ?>

				<div class="ehk-card">
					<div class="ehk-card-head">
						<div>
							<h2>Animation Effect</h2>
							<p>نادیده گرفتن تنظیم Reduce Motion ویندوز و سیستم‌عامل در فرانت‌اند سایت.</p>
						</div>
						<label class="ehk-switch">
							<input
								type="checkbox"
								name="<?php echo esc_attr( self::OPTION ); ?>[force_full_motion]"
								value="1"
								<?php checked( $motion_enabled ); ?>
							/>
							<span class="ehk-slider" aria-hidden="true"></span>
						</label>
					</div>
					<p class="ehk-note">
						<strong>روشن:</strong> Elementor، قالب و اسکریپت‌های فرانت‌اند تا حد امکان Reduced Motion سیستم را نادیده می‌گیرند و انیمیشن‌ها/Transitionها اجرا می‌شوند.<br>
						<strong>خاموش:</strong> هیچ Overrideای اعمال نمی‌شود و سایت دوباره به تنظیم دسترسی‌پذیری سیستم کاربر احترام می‌گذارد.
					</p>
				</div>

				<div class="ehk-card">
					<div class="ehk-card-head">
						<div>
							<h2>Elementor CSS Priority</h2>
							<p>لود کردن <code>frontend.min.css</code> المنتور قبل از فایل‌های CSS قالب تا استایل‌های قالب در تداخل‌های هم‌سطح اولویت داشته باشند.</p>
						</div>
						<label class="ehk-switch">
							<input
								type="checkbox"
								name="<?php echo esc_attr( self::OPTION ); ?>[elementor_css_first]"
								value="1"
								<?php checked( $css_priority_enabled ); ?>
							/>
							<span class="ehk-slider" aria-hidden="true"></span>
						</label>
					</div>
					<p class="ehk-note">
						<strong>روشن (پیش‌فرض):</strong> فایل پایه CSS المنتور قبل از اولین فایل CSS قالب چاپ می‌شود؛ بنابراین اگر specificity دو قانون برابر باشد، قانون قالب که بعدتر آمده اجرا می‌شود.<br>
						<strong>خاموش:</strong> هیچ تغییری در صف استایل‌های وردپرس داده نمی‌شود و ترتیب لود به حالت عادی Elementor/قالب برمی‌گردد.
					</p>
				</div>

				<div class="ehk-card">
					<div class="ehk-card-head">
						<div>
							<h2>Hide Frontend Admin Bar</h2>
							<p>مخفی کردن نوار مدیریت وردپرس در فرانت‌اند، معادل اجرای <code>show_admin_bar( false );</code> در قالب.</p>
						</div>
						<label class="ehk-switch">
							<input
								type="checkbox"
								name="<?php echo esc_attr( self::OPTION ); ?>[hide_frontend_admin_bar]"
								value="1"
								<?php checked( $hide_admin_bar_enabled ); ?>
							/>
							<span class="ehk-slider" aria-hidden="true"></span>
						</label>
					</div>
					<p class="ehk-note">
						<strong>روشن (پیش‌فرض):</strong> نوار Admin Bar فقط در فرانت‌اند برای کاربر لاگین‌شده نمایش داده نمی‌شود؛ نوار بالای خود wp-admin باقی می‌ماند.<br>
						<strong>خاموش:</strong> رفتار عادی وردپرس/قالب برای نمایش Admin Bar برمی‌گردد.
					</p>
				</div>

				<div class="ehk-card">
					<div class="ehk-card-head">
						<div>
							<h2>Elementor Cache Toolbar Button</h2>
							<p>افزودن دکمه سریع <strong>Elementor Cache</strong> به نوار بالای wp-admin برای اجرای مستقیم Clear Files &amp; Data.</p>
						</div>
						<label class="ehk-switch">
							<input
								type="checkbox"
								name="<?php echo esc_attr( self::OPTION ); ?>[elementor_cache_toolbar]"
								value="1"
								<?php checked( $cache_toolbar_enabled ); ?>
							/>
							<span class="ehk-slider" aria-hidden="true"></span>
						</label>
					</div>
					<p class="ehk-note">
						<strong>روشن (پیش‌فرض):</strong> در wp-admin یک میانبر مستقیم برای پاک‌کردن فایل‌ها و دیتای کش Elementor نمایش داده می‌شود.<br>
						<strong>خاموش:</strong> میانبر از Toolbar مخفی می‌شود؛ خود ابزار Elementor تغییری نمی‌کند.
					</p>
				</div>

				<div class="ehk-card">
					<h2>Elementor JSON Editor</h2>
					<p>این ابزار همیشه فعال است. برای برگه‌ها و نوشته‌های ساخته‌شده با Elementor، گزینه <strong>«ویرایش JSON المنتور»</strong> در اکشن‌های ردیف و متاباکس ویرایش نوشته نمایش داده می‌شود.</p>
				</div>

				<?php submit_button( 'ذخیره تنظیمات' ); ?>
			</form>
		</div>

		<style>
			.ehk-settings-wrap{max-width:920px}
			.ehk-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:22px 24px;margin:20px 0;box-shadow:0 1px 2px rgba(0,0,0,.03)}
			.ehk-card h2{margin:0 0 8px;font-size:18px}
			.ehk-card p{font-size:14px;line-height:1.8}
			.ehk-card-head{display:flex;align-items:center;justify-content:space-between;gap:24px}
			.ehk-card-head p{margin:0;color:#646970}
			.ehk-note{margin:18px 0 0;padding:14px 16px;background:#f6f7f7;border-radius:8px}
			.ehk-switch{position:relative;display:inline-block;width:54px;height:30px;flex:0 0 auto}
			.ehk-switch input{opacity:0;width:0;height:0}
			.ehk-slider{position:absolute;cursor:pointer;inset:0;background:#8c8f94;border-radius:999px;transition:.2s}
			.ehk-slider:before{content:"";position:absolute;width:22px;height:22px;left:4px;top:4px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:.2s}
			.ehk-switch input:checked + .ehk-slider{background:#2271b1}
			.ehk-switch input:checked + .ehk-slider:before{transform:translateX(24px)}
			.ehk-switch input:focus-visible + .ehk-slider{outline:2px solid #2271b1;outline-offset:2px}
		</style>
		<?php
	}
}
