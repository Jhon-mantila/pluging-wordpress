(function () {
	'use strict';

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function qsa(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}

	function parseCfg(el) {
		try {
			return JSON.parse(el.getAttribute('data-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	function buildPreviewSrc(videoId, isShort) {
		var q =
			'autoplay=1&mute=1&playsinline=1&controls=0&modestbranding=1&rel=0&iv_load_policy=3&fs=0&disablekb=1';
		if (isShort) {
			q += '&loop=1&playlist=' + encodeURIComponent(videoId);
		}
		return 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?' + q;
	}

	function buildModalSrc(videoId) {
		return (
			'https://www.youtube.com/embed/' +
			encodeURIComponent(videoId) +
			'?autoplay=1&rel=0&modestbranding=1&playsinline=1'
		);
	}

	function clearPreview(previewEl) {
		previewEl.innerHTML = '';
	}

	function setPreview(previewEl, videoId, isShort) {
		clearPreview(previewEl);
		var iframe = document.createElement('iframe');
		iframe.setAttribute('title', '');
		iframe.setAttribute('loading', 'eager');
		iframe.setAttribute(
			'allow',
			'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
		);
		iframe.src = buildPreviewSrc(videoId, isShort);
		previewEl.appendChild(iframe);
	}

	function bindCard(root, card, isShort) {
		var previewWrap = qs(card, '.esquina-yt__preview');
		var videoId = card.getAttribute('data-video-id');
		var title = card.getAttribute('data-video-title') || '';
		var hoverTimer = null;

		if (!previewWrap || !videoId) return;

		card.addEventListener('mouseenter', function () {
			if (hoverTimer) clearTimeout(hoverTimer);
			hoverTimer = setTimeout(function () {
				card.classList.add('is-previewing');
				setPreview(previewWrap, videoId, isShort);
			}, 200);
		});

		card.addEventListener('mouseleave', function () {
			if (hoverTimer) {
				clearTimeout(hoverTimer);
				hoverTimer = null;
			}
			card.classList.remove('is-previewing');
			clearPreview(previewWrap);
		});

		card.addEventListener('click', function () {
			clearPreview(previewWrap);
			card.classList.remove('is-previewing');
			openModal(root, videoId, title, isShort);
		});
	}

	function openModal(root, videoId, title, isShort) {
		var modal = qs(root, '.esquina-yt-modal');
		var embedHost = modal ? qs(modal, '.esquina-yt-modal__embed') : null;
		var titleEl = modal ? qs(modal, '.esquina-yt-modal__heading') : null;
		var linkEl = modal ? qs(modal, '.esquina-yt-modal__link') : null;

		if (!modal || !embedHost) return;

		qsa(root, 'video, iframe').forEach(function (el) {
			try {
				if (el.tagName === 'VIDEO') el.pause();
			} catch (err) { /* ignore */ }
		});

		embedHost.innerHTML = '';
		var iframe = document.createElement('iframe');
		iframe.setAttribute('title', title || 'YouTube');
		iframe.setAttribute('allowfullscreen', '');
		iframe.setAttribute(
			'allow',
			'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
		);
		iframe.src = buildModalSrc(videoId);
		embedHost.appendChild(iframe);

		if (titleEl) titleEl.textContent = title || '';
		if (linkEl) {
			linkEl.href = 'https://www.youtube.com/watch?v=' + encodeURIComponent(videoId);
		}

		modal.hidden = false;
		document.documentElement.style.overflow = 'hidden';
		var closeBtn = qs(modal, '.esquina-yt-modal__close');
		if (closeBtn) closeBtn.focus();
	}

	function closeModal(root) {
		var modal = qs(root, '.esquina-yt-modal');
		var embedHost = modal ? qs(modal, '.esquina-yt-modal__embed') : null;
		if (!modal) return;
		if (embedHost) embedHost.innerHTML = '';
		modal.hidden = true;
		document.documentElement.style.overflow = '';
	}

	function bindModal(root) {
		var modal = qs(root, '.esquina-yt-modal');
		if (!modal) return;

		var closeEl = qs(modal, '.esquina-yt-modal__close');
		var backdrop = qs(modal, '.esquina-yt-modal__backdrop');
		if (closeEl) closeEl.addEventListener('click', function () { closeModal(root); });
		if (backdrop) backdrop.addEventListener('click', function () { closeModal(root); });

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !modal.hidden) {
				closeModal(root);
			}
		});
	}

	function bindCards(root, isShort) {
		qsa(root, '.esquina-yt__card').forEach(function (card) {
			bindCard(root, card, isShort);
		});
	}

	function initLoadMore(root, cfg) {
		var btn = qs(root, '[data-esquina-yt-load-more]');
		if (!btn || !cfg.session) return;

		var pageToken = cfg.next_page_token || '';
		var hasMore = !!cfg.has_more;
		var loading = false;

		function setBtnState() {
			if (!hasMore) {
				btn.hidden = true;
				return;
			}
			btn.hidden = false;
			btn.disabled = loading;
			btn.textContent = loading
				? (cfg.strings && cfg.strings.loading) || 'Cargando…'
				: (cfg.strings && cfg.strings.load_more) || 'Cargar más videos';
		}

		setBtnState();

		btn.addEventListener('click', function () {
			if (loading || !hasMore) return;
			loading = true;
			setBtnState();

			var body = new URLSearchParams();
			body.set('action', 'esquina_yt_more');
			body.set('nonce', cfg.nonce);
			body.set('session', cfg.session);
			body.set('page_token', pageToken);
			body.set('batch', String(cfg.batch || 6));

			fetch(cfg.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
			})
				.then(function (r) { return r.json(); })
				.then(function (json) {
					loading = false;
					if (!json || !json.success) {
						setBtnState();
						return;
					}
					var data = json.data || {};
					var rail = qs(root, '.esquina-yt__rail');
					if (rail && data.html) {
						var wrap = document.createElement('div');
						wrap.innerHTML = data.html;
						var isShort = root.classList.contains('esquina-yt--short');
						qsa(wrap, '.esquina-yt__card').forEach(function (card) {
							rail.appendChild(card);
							bindCard(root, card, isShort);
						});
					}
					pageToken = data.next_page_token || '';
					hasMore = !!data.has_more;
					cfg.next_page_token = pageToken;
					cfg.has_more = hasMore;
					setBtnState();
				})
				.catch(function () {
					loading = false;
					setBtnState();
				});
		});
	}

	function initBlock(root) {
		var cfg = parseCfg(root);
		var isShort = root.classList.contains('esquina-yt--short');

		bindCards(root, isShort);
		bindModal(root);
		initLoadMore(root, cfg);
	}

	document.addEventListener('DOMContentLoaded', function () {
		qsa(document, '.esquina-yt').forEach(initBlock);
	});
})();
