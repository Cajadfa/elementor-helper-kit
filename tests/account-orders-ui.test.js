'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const selectedOrder = {
  getAttribute(key) {
    if (key === 'data-ehk-order-open') return '101';
    if (key === 'data-ehk-order-status') return 'در حال انجام';
    return null;
  },
  focus() {},
};
const heading = { textContent:'سفارش‌های من' };
const status = { textContent:'', hidden:true };
const list = { hidden:false };
const panel = { hidden:true };
const back = { hidden:true, focus() {} };
const detail = {
  hidden:true,
  getAttribute(key) { return key === 'data-ehk-order-detail-id' ? '101' : null; }
};
const clickHandlers = {};
const root = {
  querySelector(selector) {
    return {
      '[data-ehk-orders-list]':list,
      '[data-ehk-order-selected]':panel,
      '[data-ehk-orders-heading]':heading,
      '[data-ehk-selected-status]':status,
      '[data-ehk-order-back]':back,
      '[data-ehk-order-open]':selectedOrder
    }[selector];
  },
  querySelectorAll(selector) {
    if (selector === '[data-ehk-order-open]') return [selectedOrder];
    if (selector === '[data-ehk-order-detail-id]') return [detail];
    throw new Error('Unexpected selector: '+selector);
  },
  addEventListener(type, fn) { clickHandlers[type]=fn; },
  contains(obj) { return obj===selectedOrder; },
  isConnected:true,
};
let url='https://example.test/account?section=orders';
const listeners={};
const window={
  location:{href:url},
  history:{pushState(_state,_title,next){url=next;window.location.href=next;}},
  addEventListener(name,fn){listeners[name]=fn;}
};
const document={querySelector(){return root;}};
vm.runInNewContext(fs.readFileSync('assets/js/account-orders.js','utf8'), {
  window, document, URL,
});
assert.equal(list.hidden,false);
assert.equal(panel.hidden,true);
assert.equal(detail.hidden,true);
assert.equal(heading.textContent,'سفارش‌های من');
assert.equal(status.hidden,true);
assert.equal(back.hidden,true);

let prevented=false;
clickHandlers.click({
  target:{closest(sel){return sel==='[data-ehk-order-open]'?selectedOrder:null;}},
  preventDefault(){prevented=true;}
});
assert.equal(prevented,true);
assert.equal(heading.textContent,'سفارش #۱۰۱');
assert.equal(status.textContent,'وضعیت: در حال انجام');
assert.equal(status.hidden,false);
assert.equal(back.hidden,false);
assert.equal(panel.hidden,false);
assert.equal(list.hidden,true);
assert.equal(detail.hidden,false);
assert.match(url,/section=orders&order_id=101/);

clickHandlers.click({
  target:{closest(sel){return sel==='[data-ehk-order-back]'?back:null;}},
  preventDefault(){}
});
assert.equal(panel.hidden,true);
assert.equal(list.hidden,false);
assert.equal(detail.hidden,true);
assert.equal(back.hidden,true);
assert.equal(status.hidden,true);
assert.equal(status.textContent,'');
assert.equal(heading.textContent,'سفارش‌های من');
assert.doesNotMatch(url,/order_id=/);

window.location.href='https://example.test/account?section=orders&order_id=101';
listeners.popstate();
assert.equal(panel.hidden,false);
assert.equal(status.hidden,false);
assert.equal(status.textContent,'وضعیت: در حال انجام');
assert.equal(heading.textContent,'سفارش #۱۰۱');

window.location.href='https://example.test/account?section=orders&order_id=202';
listeners.popstate();
assert.equal(panel.hidden,true);
assert.equal(detail.hidden,true);
assert.equal(status.hidden,true);
assert.equal(back.hidden,true);
assert.equal(heading.textContent,'سفارش‌های من');
const styles=fs.readFileSync('assets/css/account-orders.css','utf8');
assert.match(styles,/\.ehk-order-header-status\{/);
assert.match(styles,/background:linear-gradient\(125deg,#4b1d6d,#7844a0\)/);
assert.match(styles,/\.ehk-order-header-status\[hidden\]\{/);
assert.match(styles,/\.ehk-orders-header > \.ehk-order-back/);
assert.doesNotMatch(styles,/\.ehk-order-detail\s*>/);

// First redirected visit: the server-authorized banner remains on the chosen
// order and is removed permanently from the DOM after returning to the list.
let bannerRemoved=false;
const firstBanner={
  getAttribute(name){return name==='data-ehk-success-order-id'?'101':null;},
  remove(){bannerRemoved=true;}
};
const newHeading={textContent:''}, newStatus={textContent:'',hidden:true};
const newList={hidden:false}, newSelected={hidden:true}, newBack={hidden:true,focus(){}};
const newDetail={hidden:true,getAttribute(k){return k==='data-ehk-order-detail-id'?'101':null;}};
const secondHandlers={};
const newRoot={
  isConnected:true,
  querySelector(selector){
    return {
      '[data-ehk-orders-list]':newList,
      '[data-ehk-order-selected]':newSelected,
      '[data-ehk-orders-heading]':newHeading,
      '[data-ehk-selected-status]':newStatus,
      '[data-ehk-order-back]':newBack,
      '[data-ehk-orders-success-once]':firstBanner
    }[selector];
  },
  querySelectorAll(selector){
    if(selector==='[data-ehk-order-open]')return [selectedOrder];
    if(selector==='[data-ehk-order-detail-id]')return [newDetail];
    return [];
  },
  addEventListener(type,callback){secondHandlers[type]=callback;},
  contains(){return true;}
};
let nextUrl='https://example.test/account?section=orders&order_id=101';
const nextWindow={
  location:{href:nextUrl},
  history:{pushState(_a,_b,href){nextUrl=href;nextWindow.location.href=href;}},
  addEventListener(){}
};
vm.runInNewContext(fs.readFileSync('assets/js/account-orders.js','utf8'),{
  window:nextWindow,
  document:{querySelector(){return newRoot;}},
  URL
});
assert.equal(newSelected.hidden,false);
assert.equal(newHeading.textContent,'سفارش #۱۰۱');
assert.equal(bannerRemoved,false,'First redirect should show the success banner');
secondHandlers.click({
  target:{closest(selector){return selector==='[data-ehk-order-back]'?newBack:null;}},
  preventDefault(){}
});
assert.equal(bannerRemoved,true,'Returning to list permanently removes the one-time banner');
assert.equal(newSelected.hidden,true);

console.log('QQ owned-order heading, purple status pill, top back navigation and browser history passed');

