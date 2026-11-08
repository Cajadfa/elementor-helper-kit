/**
 * QQ Account order tab navigation. All invoice and address fragments are
 * server-rendered ONLY after WC_Order customer ownership checks. The client
 * can only show/hide those fragments, never request a different customer's
 * details. Supports QQ's AJAX section navigation and browser back/forward.
 */
(() => {
  'use strict';
  let current = null;
  const mounted = new WeakSet();
  const toPersian = value => String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
  const setup = root => {
    if (!root || mounted.has(root)) return;
    const list = root.querySelector('[data-ehk-orders-list]');
    const selected = root.querySelector('[data-ehk-order-selected]');
    const heading = root.querySelector('[data-ehk-orders-heading]');
    const status = root.querySelector('[data-ehk-selected-status]');
    const back = root.querySelector('[data-ehk-order-back]');
    let successNotice = root.querySelector('[data-ehk-orders-success-once]');
    if (!list || !selected || !heading || !status || !back) return;
    mounted.add(root);
    const panels = [...root.querySelectorAll('[data-ehk-order-detail-id]')];
    const getOrder = orderId => {
      if (!/^[1-9][0-9]*$/.test(orderId || '')) return null;
      return [...root.querySelectorAll('[data-ehk-order-open]')].find(
        button => button.getAttribute('data-ehk-order-open') === orderId
      ) || null;
    };
    const setSelected = (orderId, updateUrl = false) => {
      const button = getOrder(orderId);
      const panel = panels.find(node => node.getAttribute('data-ehk-order-detail-id') === orderId);
      panels.forEach(node => { node.hidden = node !== panel; });
      // The server has already consumed this first-view message. Once the
      // customer leaves the new order, never show the banner again via SPA
      // back/forward navigation without a fresh server-authorized flash.
      if (successNotice && (!button || !panel ||
        successNotice.getAttribute('data-ehk-success-order-id') !== orderId)) {
        successNotice.remove();
        successNotice = null;
      }
      if (button && panel) {
        heading.textContent = 'سفارش #' + toPersian(orderId);
        status.textContent = 'وضعیت: ' + (button.getAttribute('data-ehk-order-status') || 'نامشخص');
        status.hidden = false;
        back.hidden = false;
        list.hidden = true;
        selected.hidden = false;
        back.focus({ preventScroll: true });
      } else {
        heading.textContent = 'سفارش‌های من';
        status.hidden = true;
        status.textContent = '';
        back.hidden = true;
        selected.hidden = true;
        list.hidden = false;
      }
      if (updateUrl) {
        const url = new URL(window.location.href);
        url.searchParams.set('section', 'orders');
        if (button && panel) url.searchParams.set('order_id', orderId);
        else url.searchParams.delete('order_id');
        window.history.pushState(null, '', url.href);
      }
    };
    root.addEventListener('click', event => {
      const trigger = event.target.closest('[data-ehk-order-open]');
      if (trigger && root.contains(trigger)) {
        event.preventDefault();
        setSelected(trigger.getAttribute('data-ehk-order-open'), true);
        return;
      }
      if (event.target.closest('[data-ehk-order-back]')) {
        event.preventDefault();
        setSelected('', true);
        root.querySelector('[data-ehk-order-open]')?.focus({ preventScroll: true });
      }
    });
    const restore = () => {
      const params = new URL(window.location.href).searchParams;
      setSelected(params.get('section') === 'orders' ? params.get('order_id') || '' : '');
    };
    current = { root, restore };
    restore();
  };
  const mount = () => {
    const root = document.querySelector('[data-ehk-account-orders]');
    if (root) setup(root);
  };
  window.addEventListener('popstate', () => {
    mount();
    if (current && current.root.isConnected !== false) current.restore();
  });
  mount();
  // QQ Account may insert the section after the initial script execution.
  if (typeof MutationObserver !== 'undefined' && document.body) {
    const observer = new MutationObserver(mount);
    observer.observe(document.body, { childList: true, subtree: true });
    window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
  }
})();

