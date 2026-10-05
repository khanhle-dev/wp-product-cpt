/* Lightbox cho thư viện ảnh trang chi tiết sản phẩm. */
(function () {
	var groups = document.querySelectorAll('[data-spc-lightbox]');
	if (!groups.length) { return; }

	var box, img, counter, items = [], index = 0, lastFocus;

	function build() {
		box = document.createElement('div');
		box.className = 'spc-lb';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.innerHTML =
			'<button type="button" class="spc-lb__close" aria-label="Đóng">&times;</button>' +
			'<button type="button" class="spc-lb__nav spc-lb__prev" aria-label="Ảnh trước">&#8249;</button>' +
			'<img class="spc-lb__img" alt="">' +
			'<button type="button" class="spc-lb__nav spc-lb__next" aria-label="Ảnh sau">&#8250;</button>' +
			'<div class="spc-lb__count"></div>';
		document.body.appendChild(box);
		img = box.querySelector('.spc-lb__img');
		counter = box.querySelector('.spc-lb__count');

		box.querySelector('.spc-lb__close').addEventListener('click', close);
		box.querySelector('.spc-lb__prev').addEventListener('click', function () { show(index - 1); });
		box.querySelector('.spc-lb__next').addEventListener('click', function () { show(index + 1); });
		box.addEventListener('click', function (e) { if (e.target === box) { close(); } });

		// Vuốt trái/phải trên điện thoại.
		var x0 = null;
		box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
		box.addEventListener('touchend', function (e) {
			if (null === x0) { return; }
			var dx = e.changedTouches[0].clientX - x0;
			if (Math.abs(dx) > 50) { show(index + (dx < 0 ? 1 : -1)); }
			x0 = null;
		});
	}

	function show(i) {
		index = (i + items.length) % items.length;
		var a = items[index], thumb = a.querySelector('img');
		img.src = a.href;
		img.alt = thumb ? thumb.alt : '';
		counter.textContent = (index + 1) + ' / ' + items.length;
		box.classList.toggle('spc-lb--single', items.length < 2);
	}

	function open(list, i) {
		if (!box) { build(); }
		items = list;
		lastFocus = document.activeElement;
		show(i);
		box.classList.add('is-open');
		document.documentElement.classList.add('spc-lb-lock');
		document.addEventListener('keydown', onKey);
		box.querySelector('.spc-lb__close').focus();
	}

	function close() {
		box.classList.remove('is-open');
		document.documentElement.classList.remove('spc-lb-lock');
		document.removeEventListener('keydown', onKey);
		img.removeAttribute('src');
		if (lastFocus) { lastFocus.focus(); }
	}

	function onKey(e) {
		if ('Escape' === e.key) { close(); }
		else if ('ArrowLeft' === e.key) { show(index - 1); }
		else if ('ArrowRight' === e.key) { show(index + 1); }
	}

	Array.prototype.forEach.call(groups, function (group) {
		// Chỉ lấy link ảnh có data-spc-lb (bỏ qua nút Báo giá / Zalo…); template cũ không có thì lấy mọi <a>.
		var links = Array.prototype.slice.call(group.querySelectorAll('a[data-spc-lb]'));
		if (!links.length) { links = Array.prototype.slice.call(group.querySelectorAll('a')); }
		links.forEach(function (a, i) {
			a.addEventListener('click', function (e) {
				e.preventDefault();
				open(links, i);
			});
		});
	});
})();
