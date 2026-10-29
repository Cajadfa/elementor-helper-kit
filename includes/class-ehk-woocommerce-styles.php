<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional, theme-neutral CSS baseline for WooCommerce pages and shortcodes.
 */
class EHK_WooCommerce_Styles {
	const STYLESHEET = 'assets/css/woocommerce-minimal.css';
	private static $primary_css_added = false;
	private static $quantity_script_added = false;

	public static function init() {
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'filter_classic_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 99 );

		// Recheck explicitly assigned WooCommerce pages just before head styles print.
		add_action( 'wp_head', array( __CLASS__, 'ensure_configured_page_styles' ), 7 );
	}

	/**
	 * Read WooCommerce's configured page IDs (WooCommerce > Settings > Advanced).
	 * This also covers checkout/order-received and account endpoint URLs.
	 */
	private static function is_configured_woocommerce_page() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$page_id = (int) get_queried_object_id();
		if ( $page_id <= 0 ) {
			return false;
		}

		foreach ( array( 'cart', 'checkout', 'myaccount', 'shop', 'terms' ) as $page ) {
			$configured_id = (int) wc_get_page_id( $page );
			if ( $configured_id > 0 && $configured_id === $page_id ) {
				return true;
			}
		}

		return false;
	}

	public static function ensure_configured_page_styles() {
		if ( ! self::is_available() || ! self::is_configured_woocommerce_page() ) {
			return;
		}

		// On these pages, restore our style if an optimization/theme dequeued it.
		// wp_head priority 7 runs before WordPress prints head styles at priority 8.
		self::enqueue_styles();
	}

	private static function is_available() {
		return EHK_Settings::woocommerce_minimal_styles_enabled()
			&& class_exists( 'WooCommerce' )
			&& is_readable( EHK_DIR . self::STYLESHEET );
	}

	private static function quantity_script() {
		return <<<'EHK_QTY_JS'
/* Elementor Helper Kit — classic WooCommerce quantity stepper (progressive enhancement). */
(function () {
  'use strict';

  function numeric(value, fallback) {
    var result = Number.parseFloat(String(value).replace(',', '.'));
    return Number.isFinite(result) ? result : fallback;
  }

  function getInput(wrapper) {
    return wrapper.querySelector('input.qty:not([type="hidden"])');
  }

  function limits(input) {
    var min = numeric(input.getAttribute('min'), 1);
    var max = numeric(input.getAttribute('max'), Infinity);
    var step = numeric(input.getAttribute('step'), 1);
    return { min: min, max: max, step: step > 0 ? step : 1 };
  }

  function sync(wrapper) {
    var input = getInput(wrapper);
    if (!input) return;
    var bounds = limits(input);
    var value = numeric(input.value, bounds.min);
    var minus = wrapper.querySelector('.ehk-qty-minus');
    var plus = wrapper.querySelector('.ehk-qty-plus');
    if (minus) minus.disabled = input.disabled || input.readOnly || value <= bounds.min;
    if (plus) plus.disabled = input.disabled || input.readOnly || value >= bounds.max;
  }

  function button(direction, label, character) {
    var element = document.createElement('button');
    element.type = 'button';
    element.className = 'ehk-qty-step ' + (direction < 0 ? 'ehk-qty-minus' : 'ehk-qty-plus');
    element.setAttribute('data-ehk-step', String(direction));
    element.setAttribute('aria-label', label);
    element.textContent = character;
    return element;
  }

  function enhance(root) {
    var scope = root || document;
    scope.querySelectorAll('.woocommerce .quantity, .woocommerce-page .quantity').forEach(function (wrapper) {
      if (wrapper.classList.contains('ehk-qty-enhanced')) {
        sync(wrapper);
        return;
      }

      var input = getInput(wrapper);
      if (!input || input.disabled || input.readOnly) return;

      // Coexist with themes/extensions that already supply quantity controls.
      if (wrapper.querySelector('.plus, .minus, [data-quantity], .ehk-qty-step')) return;

      var minus = button(-1, 'کاهش تعداد', '−');
      var plus = button(1, 'افزایش تعداد', '+');
      wrapper.insertBefore(minus, input);
      input.insertAdjacentElement('afterend', plus);
      wrapper.classList.add('ehk-qty-enhanced');
      sync(wrapper);
    });
  }

  function changeQuantity(buttonElement) {
    var wrapper = buttonElement.closest('.quantity');
    var input = wrapper && getInput(wrapper);
    if (!input || input.disabled || input.readOnly || buttonElement.disabled) return;

    var bounds = limits(input);
    var direction = numeric(buttonElement.getAttribute('data-ehk-step'), 0);
    var current = numeric(input.value, bounds.min);
    var precision = Math.min(6, Math.max(
      (String(bounds.step).split('.')[1] || '').length,
      (String(current).split('.')[1] || '').length
    ));
    var scale = Math.pow(10, precision);
    var next = Math.round((current + direction * bounds.step) * scale) / scale;
    next = Math.min(bounds.max, Math.max(bounds.min, next));

    if (next === current) return;
    input.value = String(next);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    sync(wrapper);
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('.ehk-qty-step');
    if (trigger) changeQuantity(trigger);
  });

  document.addEventListener('input', function (event) {
    if (event.target.matches('.quantity input.qty')) sync(event.target.closest('.quantity'));
  });

  document.addEventListener('change', function (event) {
    if (event.target.matches('.quantity input.qty')) sync(event.target.closest('.quantity'));
  });

  function start() {
    enhance(document);
    if (window.jQuery && document.body) {
      window.jQuery(document.body).on('updated_wc_div wc_fragments_refreshed', function () {
        enhance(document);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
}());

/* Material floating labels, only for standard classic checkout text inputs. */
(function () {
  'use strict';

  function syncFloating(row, input) {
    if (!row || !input) return;
    var filled = String(input.value || '').trim().length > 0;
    row.classList.toggle('ehk-floating-active', document.activeElement === input || filled);
  }

  function initializeFloating() {
    document.querySelectorAll(
      '.woocommerce form.checkout.woocommerce-checkout .form-row, ' +
      '.woocommerce-page form.checkout.woocommerce-checkout .form-row'
    ).forEach(function (row) {
      var label = row.querySelector(':scope > label[for]');
      var wrapper = row.querySelector('.woocommerce-input-wrapper');
      var input = wrapper && wrapper.querySelector('input.input-text');
      if (!label || !input || !input.id || label.htmlFor !== input.id) return;
      if (label.classList.contains('screen-reader-text')) return;
      var type = String(input.type || '').toLowerCase();
      if (['text', 'email', 'tel', 'number', 'password', 'url'].indexOf(type) === -1) return;
      row.classList.add('ehk-floating-field');
      syncFloating(row, input);
    });
  }

  function onFieldEvent(event) {
    var element = event.target;
    if (!element || !element.matches || !element.matches('input.input-text')) return;
    var row = element.closest('.ehk-floating-field');
    if (row) syncFloating(row, element);
  }

  function enableCartUpdate(event) {
    var input = event.target;
    if (!input || !input.matches || !input.matches('form.woocommerce-cart-form .quantity input.qty')) return;
    var form = input.closest('form.woocommerce-cart-form');
    var update = form && form.querySelector('[name="update_cart"]');
    if (update) {
      update.disabled = false;
      update.classList.remove('disabled');
    }
  }

  function init() {
    initializeFloating();
    if (window.jQuery && document.body) {
      window.jQuery(document.body).on('updated_checkout', function () {
        initializeFloating();
      });
    }
  }

  ['focusin', 'focusout', 'input', 'change'].forEach(function (type) {
    document.addEventListener(type, onFieldEvent);
  });
  document.addEventListener('change', enableCartUpdate);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
EHK_QTY_JS;
	}

	private static function channel_luminance( $component ) {
		$channel = $component / 255;
		return $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	}

	private static function primary_color_css() {
		$color = EHK_Settings::woocommerce_primary_color();
		$red   = hexdec( substr( $color, 1, 2 ) );
		$green = hexdec( substr( $color, 3, 2 ) );
		$blue  = hexdec( substr( $color, 5, 2 ) );

		$luminance = 0.2126 * self::channel_luminance( $red )
			+ 0.7152 * self::channel_luminance( $green )
			+ 0.0722 * self::channel_luminance( $blue );

		$on_primary = $luminance > 0.179 ? '#111827' : '#ffffff';
		$hover      = sprintf( '#%02x%02x%02x', (int) round( $red * 0.86 ), (int) round( $green * 0.86 ), (int) round( $blue * 0.86 ) );

		return sprintf(
			':root{--wcm-primary:%s;--wcm-primary-hover:%s;--wcm-on-primary:%s;}',
			$color,
			$hover,
			$on_primary
		);
	}

	public static function filter_classic_styles( $styles ) {
		return self::is_available() ? array() : $styles;
	}

	public static function enqueue_styles() {
		if ( ! self::is_available() ) {
			return;
		}

		// Product shortcodes/widgets may appear on ordinary WordPress pages.
		wp_enqueue_style(
			'ehk-woocommerce-minimal',
			EHK_URL . self::STYLESHEET,
			array(),
			EHK_VERSION
		);

		if ( ! self::$primary_css_added ) {
			wp_add_inline_style( 'ehk-woocommerce-minimal', self::primary_color_css() );
			self::$primary_css_added = true;
		}

		if ( ! self::$quantity_script_added ) {
			wp_enqueue_script( 'jquery' );
			wp_add_inline_script( 'jquery-core', self::quantity_script() );
			self::$quantity_script_added = true;
		}
	}
}
