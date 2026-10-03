jQuery(function ($) {
	var frame;
	var $list = $('#spc-gallery-list');
	var $input = $('#spc-gallery');

	function sync() {
		$input.val($list.children('li').map(function () { return $(this).data('id'); }).get().join(','));
	}

	$list.sortable({ items: 'li', tolerance: 'pointer', update: sync });

	$('#spc-gallery-add').on('click', function (e) {
		e.preventDefault();
		if (frame) { frame.open(); return; }

		frame = wp.media({
			title: 'Thêm ảnh vào thư viện',
			button: { text: 'Thêm ảnh' },
			library: { type: 'image' },
			multiple: 'add'
		});

		frame.on('select', function () {
			frame.state().get('selection').each(function (att) {
				var a = att.toJSON();
				if ($list.children('li[data-id="' + a.id + '"]').length) { return; }
				var url = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
				$('<li>').attr('data-id', a.id)
					.append($('<img alt="">').attr('src', url))
					.append('<button type="button" class="spc-gallery-del" aria-label="Xoá ảnh">&times;</button>')
					.appendTo($list);
			});
			frame.state().get('selection').reset();
			sync();
		});

		frame.open();
	});

	$list.on('click', '.spc-gallery-del', function (e) {
		e.preventDefault();
		$(this).closest('li').remove();
		sync();
	});
});
