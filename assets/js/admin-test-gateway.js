/* Admin-only WooCommerce classic checkout test gateway. No external requests. */
(function () {
  'use strict';

  var gatewayId = 'ehk_admin_test';
  var dialog = document.getElementById('ehk-test-gateway-dialog');
  if (!dialog) return;

  var bypass = false;
  var lastFocus = null;
  var visible = false;

  function form() {
    return document.querySelector('form.checkout.woocommerce-checkout');
  }

  function gatewaySelected(checkout) {
    if (!checkout) return false;
    var selected = checkout.querySelector('input[name="payment_method"]:checked');
    if (!selected) selected = checkout.querySelector('input[name="payment_method"][type="hidden"]');
    return !!selected && selected.value === gatewayId;
  }

  function field(checkout) {
    var control = checkout.querySelector('input[name="ehk_test_outcome"]');
    if (!control) {
      control = document.createElement('input');
      control.type = 'hidden';
      control.name = 'ehk_test_outcome';
      checkout.appendChild(control);
    }
    return control;
  }

  function show() {
    var checkout = form();
    if (!checkout || !gatewaySelected(checkout) || visible) return;
    field(checkout).value = '';
    lastFocus = document.activeElement;
    visible = true;
    dialog.hidden = false;
    dialog.setAttribute('aria-hidden', 'false');
    var first = dialog.querySelector('[data-ehk-test-result]');
    if (first) first.focus();
  }

  function close() {
    if (!visible) return;
    visible = false;
    dialog.hidden = true;
    dialog.setAttribute('aria-hidden', 'true');
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  }

  function run(result) {
    if (result !== 'success' && result !== 'failure') return;
    var checkout = form();
    if (!checkout || !gatewaySelected(checkout)) {
      close();
      return;
    }

    field(checkout).value = result;
    close();
    var submit = checkout.querySelector('#place_order');
    if (!submit || submit.disabled) return;
    bypass = true;
    try {
      // Reuse WooCommerce's native checkout validation and AJAX submission.
      submit.click();
    } finally {
      bypass = false;
    }
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || typeof target.closest !== 'function') return;
    var submit = target.closest('#place_order');
    var checkout = form();
    if (!bypass && submit && checkout && checkout.contains(submit) && gatewaySelected(checkout)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      show();
    }
  }, true);

  // Capture keyboard (Enter) submissions, too.
  document.addEventListener('submit', function (event) {
    if (!bypass && event.target === form() && gatewaySelected(event.target)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      show();
    }
  }, true);

  dialog.addEventListener('click', function (event) {
    var choice = event.target.closest('[data-ehk-test-result]');
    if (choice) {
      event.preventDefault();
      run(choice.getAttribute('data-ehk-test-result'));
    } else if (event.target.closest('[data-ehk-test-close]')) {
      event.preventDefault();
      close();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (!visible) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    if (event.key !== 'Tab') return;
    var controls = Array.prototype.slice.call(dialog.querySelectorAll('button'));
    if (!controls.length) return;
    var first = controls[0];
    var last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
}());
