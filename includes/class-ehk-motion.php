<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EHK_Motion {
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_early_motion_shim' ), -9999 );
	}

	public static function print_early_motion_shim() {
		if ( ! EHK_Settings::force_full_motion_enabled() ) {
			return;
		}

		// Optional emergency switch for developers.
		if ( defined( 'EHK_DISABLE_FULL_MOTION' ) && EHK_DISABLE_FULL_MOTION ) {
			return;
		}
		?>
		<script id="elementor-helper-kit-motion-shim">
		(function () {
			'use strict';

			if (window.__elementorHelperKitFullMotion) {
				return;
			}
			window.__elementorHelperKitFullMotion = true;

			var reducedMotionPattern = /prefers-reduced-motion\s*:\s*reduce/i;
			var anyMotionPattern = /prefers-reduced-motion/i;
			var nativeMatchMedia = typeof window.matchMedia === 'function'
				? window.matchMedia.bind(window)
				: null;

			/*
			 * Make JavaScript behave as if the visitor did not request reduced motion.
			 * This must run before Elementor and animation libraries inspect matchMedia().
			 */
			if (nativeMatchMedia) {
				window.matchMedia = function (query) {
					var originalQuery = String(query || '');

					if (!anyMotionPattern.test(originalQuery)) {
						return nativeMatchMedia(originalQuery);
					}

					var forcedQuery = originalQuery
						.replace(/\(\s*prefers-reduced-motion\s*:\s*reduce\s*\)/ig, '(min-width: 999999px)')
						.replace(/\(\s*prefers-reduced-motion\s*:\s*no-preference\s*\)/ig, '(min-width: 0px)');

					var forcedList = nativeMatchMedia(forcedQuery);

					return {
						matches: forcedList.matches,
						media: originalQuery,
						onchange: null,
						addListener: function () {},
						removeListener: function () {},
						addEventListener: function () {},
						removeEventListener: function () {},
						dispatchEvent: function () { return false; }
					};
				};
			}

			/*
			 * Delete CSS @media blocks that specifically target reduced motion.
			 * Deleting the block allows the original theme/widget declarations to remain.
			 */
			function scrubRuleContainer(container) {
				var rules;

				try {
					rules = container.cssRules;
				} catch (error) {
					// Cross-origin stylesheet without CSSOM/CORS access.
					return;
				}

				if (!rules) {
					return;
				}

				for (var i = rules.length - 1; i >= 0; i--) {
					var rule = rules[i];
					var mediaText = '';

					try {
						if (rule.media && rule.media.mediaText) {
							mediaText = rule.media.mediaText;
						} else if (rule.conditionText) {
							mediaText = rule.conditionText;
						}
					} catch (error) {}

					if (mediaText && reducedMotionPattern.test(mediaText)) {
						try {
							container.deleteRule(i);
							continue;
						} catch (error) {}
					}

					if (rule && rule.cssRules && typeof rule.deleteRule === 'function') {
						scrubRuleContainer(rule);
					}
				}
			}

			function scrubAllStylesheets() {
				var sheets = document.styleSheets;
				for (var i = 0; i < sheets.length; i++) {
					scrubRuleContainer(sheets[i]);
				}
			}

			function watchStylesheetNode(node) {
				if (!node || node.nodeType !== 1) {
					return;
				}

				var tagName = node.tagName ? node.tagName.toLowerCase() : '';

				if (tagName === 'link' && String(node.rel).toLowerCase() === 'stylesheet') {
					node.addEventListener('load', scrubAllStylesheets, { once: true });
				}

				if (tagName === 'style') {
					setTimeout(scrubAllStylesheets, 0);
				}

				if (node.querySelectorAll) {
					var nested = node.querySelectorAll('link[rel="stylesheet"], style');
					for (var i = 0; i < nested.length; i++) {
						watchStylesheetNode(nested[i]);
					}
				}
			}

			function patchInsertRule(prototype) {
				if (!prototype || typeof prototype.insertRule !== 'function' || prototype.__ehkInsertRulePatched) {
					return;
				}

				var nativeInsertRule = prototype.insertRule;
				prototype.insertRule = function () {
					var result = nativeInsertRule.apply(this, arguments);
					scrubRuleContainer(this);
					return result;
				};
				prototype.__ehkInsertRulePatched = true;
			}

			try {
				patchInsertRule(window.CSSStyleSheet && window.CSSStyleSheet.prototype);
				patchInsertRule(window.CSSGroupingRule && window.CSSGroupingRule.prototype);
			} catch (error) {}

			if (typeof MutationObserver !== 'undefined') {
				var observer = new MutationObserver(function (mutations) {
					for (var i = 0; i < mutations.length; i++) {
						var addedNodes = mutations[i].addedNodes;
						for (var j = 0; j < addedNodes.length; j++) {
							watchStylesheetNode(addedNodes[j]);
						}
					}
				});

				observer.observe(document.documentElement, {
					childList: true,
					subtree: true
				});
			}

			scrubAllStylesheets();
			document.addEventListener('DOMContentLoaded', scrubAllStylesheets);
			window.addEventListener('load', scrubAllStylesheets);
			setTimeout(scrubAllStylesheets, 50);
			setTimeout(scrubAllStylesheets, 250);
			setTimeout(scrubAllStylesheets, 1000);
		})();
		</script>
		<?php
	}
}
