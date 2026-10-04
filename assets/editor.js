jQuery(function ($) {
	'use strict';

	var initialized = wp.codeEditor.initialize($('#ehk-json-code'), EHK_JSON.settings || {});
	var cm = initialized.codemirror;
	var $status = $('#ehk-json-status');
	var dirty = false;

	cm.setSize(null, 'calc(100vh - 190px)');

	cm.on('change', function () {
		dirty = true;
		$status.text('تغییرات ذخیره نشده').attr('class', 'warn');
	});

	window.addEventListener('beforeunload', function (event) {
		if (dirty) {
			event.preventDefault();
			event.returnValue = '';
		}
	});

	function parse() {
		try {
			return JSON.parse(cm.getValue());
		} catch (error) {
			$status.text('JSON خطا دارد: ' + error.message).attr('class', 'err');
			return null;
		}
	}

	$('#ehk-json-format').on('click', function () {
		var data = parse();
		if (data !== null) {
			cm.setValue(JSON.stringify(data, null, 2));
		}
	});

	$('#ehk-json-minify').on('click', function () {
		var data = parse();
		if (data !== null) {
			cm.setValue(JSON.stringify(data));
		}
	});

	var backups = JSON.parse($('#ehk-json-backups').text() || '[]');
	$('#ehk-json-backup').on('change', function () {
		var value = this.value;
		if (value === '' || typeof backups[value] === 'undefined') {
			return;
		}

		if (!window.confirm('بکاپ داخل ادیتور بارگذاری شود؟ (تا ذخیره نکنید اعمال نمی‌شود)')) {
			return;
		}

		try {
			cm.setValue(JSON.stringify(JSON.parse(backups[value]), null, 2));
		} catch (error) {
			cm.setValue(backups[value]);
		}
	});

	function save() {
		if (parse() === null) {
			return;
		}

		$status.text('در حال ذخیره...').attr('class', '');

		$.post(EHK_JSON.ajax, {
			action: 'ehk_json_save',
			nonce: EHK_JSON.nonce,
			post: EHK_JSON.post,
			json: cm.getValue()
		})
			.done(function (response) {
				if (response.success) {
					dirty = false;
					$status.text(response.data).attr('class', 'ok');
				} else {
					$status.text(response.data || 'خطا').attr('class', 'err');
				}
			})
			.fail(function (xhr) {
				$status.text('خطای سرور: ' + xhr.status).attr('class', 'err');
			});
	}

	$('#ehk-json-save').on('click', save);

	$(document).on('keydown', function (event) {
		if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
			event.preventDefault();
			save();
		}
	});
});
