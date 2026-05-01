(function () {
	'use strict';

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function parseCfg(el) {
		try {
			return JSON.parse(el.getAttribute('data-config') || '{}');
		} catch (e) {
			return {};
		}
	}

	function stopVideos(scope) {
		(scope || document).querySelectorAll('video.esquina-fb-video').forEach(function (v) {
			try {
				v.pause();
			} catch (err) { /* ignore */ }
		});
	}

	/**
	 * Reproduce vídeos del grid en silencio (política de autoplay del navegador).
	 */
	function playCardVideos(layout) {
		if (!layout) return;

		layout.querySelectorAll('video.esquina-fb-video--card').forEach(function (v) {
			v.muted = true;
			v.defaultMuted = true;
			v.playsInline = true;
			v.loop = true;
			v.preload = 'auto';
			v.setAttribute('muted', '');
			v.setAttribute('playsinline', '');
			v.setAttribute('webkit-playsinline', '');

			function tryPlay() {
				var p = v.play();
				if (p && typeof p.catch === 'function') {
					p.catch(function () {});
				}
			}

			if (v.readyState >= 2) {
				tryPlay();
			} else {
				v.addEventListener('loadeddata', tryPlay, { once: true });
				v.addEventListener('canplay', tryPlay, { once: true });
			}
		});
	}

	function buildCard(post, role, idx) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className =
			'esquina-fb-card ' +
			(role === 'featured' ? 'esquina-fb-card--featured' : 'esquina-fb-card--small');
		btn.setAttribute('data-post-index', String(idx));
		btn.setAttribute('aria-label', post.message_preview ? post.message_preview : 'Ver publicación');

		var media = document.createElement('div');
		media.className = 'esquina-fb-card__media';

		if (post.media_type === 'video' && post.video) {
			var v = document.createElement('video');
			v.className = 'esquina-fb-video esquina-fb-video--card';
			v.setAttribute('playsinline', '');
			v.setAttribute('webkit-playsinline', '');
			v.setAttribute('muted', '');
			v.setAttribute('loop', '');
			v.setAttribute('preload', 'auto');
			v.playsInline = true;
			v.muted = true;
			v.loop = true;
			v.controls = false;
			v.poster = post.image || '';
			v.src = post.video;
			media.appendChild(v);
		} else if (post.image) {
			var img = document.createElement('img');
			img.src = post.image;
			img.alt = '';
			img.loading = 'lazy';
			img.decoding = 'async';
			media.appendChild(img);
		}

		var ov = document.createElement('div');
		ov.className = 'esquina-fb-card__overlay';
		btn.appendChild(media);
		btn.appendChild(ov);

		if (post.message_preview) {
			var ex = document.createElement('div');
			ex.className = 'esquina-fb-card__excerpt';
			ex.textContent = post.message_preview;
			btn.appendChild(ex);
		}

		return btn;
	}

	function openModal(root, post, strings) {
		var modal = qs(root, '.esquina-fb-modal');
		if (!modal) return;

		stopVideos(root);

		var mediaHost = qs(modal, '.esquina-fb-modal__media');
		var body = qs(modal, '.esquina-fb-modal__body');
		var dateEl = qs(modal, '.esquina-fb-modal__date');
		var msgEl = qs(modal, '.esquina-fb-modal__message');
		var linkEl = qs(modal, '.esquina-fb-modal__link');

		mediaHost.innerHTML = '';

		if (post.media_type === 'video' && post.video) {
			var v = document.createElement('video');
			v.className = 'esquina-fb-video esquina-fb-video--modal';
			v.setAttribute('controls', '');
			v.setAttribute('playsinline', '');
			v.setAttribute('webkit-playsinline', '');
			v.setAttribute('preload', 'auto');
			v.playsInline = true;
			v.poster = post.image || '';
			v.src = post.video;
			mediaHost.appendChild(v);

			var tryModalPlay = function () {
				v.muted = false;
				var p = v.play();
				if (p && typeof p.catch === 'function') {
					p.catch(function () {
						v.muted = true;
						var p2 = v.play();
						if (p2 && typeof p2.catch === 'function') {
							p2.catch(function () {});
						}
					});
				}
			};
			if (v.readyState >= 2) {
				tryModalPlay();
			} else {
				v.addEventListener('loadeddata', tryModalPlay, { once: true });
				v.addEventListener('canplay', tryModalPlay, { once: true });
			}
		} else if (post.image) {
			var img = document.createElement('img');
			img.src = post.image;
			img.alt = '';
			mediaHost.appendChild(img);
		}

		dateEl.textContent = post.created_human || '';
		msgEl.textContent = post.message || strings.no_message;
		linkEl.href = post.permalink_url || '#';
		linkEl.textContent = strings.view_on_facebook;

		modal.hidden = false;
		document.documentElement.style.overflow = 'hidden';

		var closeBtn = qs(modal, '.esquina-fb-modal__close');
		if (closeBtn) closeBtn.focus();
	}

	function closeModal(root) {
		var modal = qs(root, '.esquina-fb-modal');
		if (!modal) return;
		stopVideos(modal);
		modal.hidden = true;
		document.documentElement.style.overflow = '';
	}

	function renderPage(feed, state, cfg, strings) {
		var layout = qs(feed, '.esquina-fb-feed__layout');
		var pagerPrev = qs(feed, '[data-esquina-fb-prev]');
		var pagerNext = qs(feed, '[data-esquina-fb-next]');
		var pagerMeta = qs(feed, '[data-esquina-fb-meta]');

		layout.innerHTML = '';

		var slice = state.posts.slice(state.pageOffset, state.pageOffset + cfg.per_page);
		if (!slice.length) {
			layout.innerHTML =
				'<p class="esquina-fb-feed__error">' +
				(strings.no_posts || 'Sin publicaciones.') +
				'</p>';
			pagerPrev.disabled = true;
			pagerNext.disabled = true;
			if (pagerMeta) pagerMeta.textContent = '';
			return;
		}

		if (slice.length === 1) {
			layout.classList.add('is-single-feature');
		} else {
			layout.classList.remove('is-single-feature');
		}

		slice.forEach(function (post, i) {
			var role = i === 0 ? 'featured' : 'small';
			var globalIdx = state.pageOffset + i;
			var card = buildCard(post, role, globalIdx);
			card.addEventListener('click', function () {
				openModal(feed, post, strings);
			});
			layout.appendChild(card);
		});

		playCardVideos(layout);

		var totalPages = Math.max(1, Math.ceil(state.posts.length / cfg.per_page));
		var currentPage = Math.floor(state.pageOffset / cfg.per_page) + 1;

		if (pagerPrev) {
			pagerPrev.disabled = state.pageOffset <= 0;
		}

		var hasNextLocal = state.pageOffset + cfg.per_page < state.posts.length;
		var hasNextRemote = !!state.nextCursor;
		if (pagerNext) {
			pagerNext.disabled = !hasNextLocal && !hasNextRemote;
		}

		if (pagerMeta) {
			var moreHint = hasNextRemote ? ' · +' : '';
			pagerMeta.textContent =
				(strings.page_label || 'Página') +
				' ' +
				currentPage +
				' / ' +
				totalPages +
				moreHint;
		}
	}

	function fetchMore(feed, state, cfg, strings) {
		if (!state.nextCursor || state.loading) {
			return Promise.resolve();
		}

		state.loading = true;

		var body = new URLSearchParams();
		body.set('action', 'esquina_fb_more');
		body.set('nonce', cfg.nonce);
		body.set('page_id', cfg.page_id);
		body.set('limit', String(cfg.limit));
		body.set('after', state.nextCursor);

		return fetch(cfg.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (json) {
				state.loading = false;
				if (!json || !json.success) {
					state.nextCursor = '';
					return;
				}
				var data = json.data || {};
				(data.posts || []).forEach(function (p) {
					state.posts.push(p);
				});
				state.nextCursor = data.next_cursor || '';
			})
			.catch(function () {
				state.loading = false;
			});
	}

	function initFeed(feed) {
		var cfg = parseCfg(feed);
		var strings = cfg.strings || {};

		var state = {
			posts: cfg.posts || [],
			nextCursor: cfg.next_cursor || '',
			pageOffset: 0,
			loading: false,
		};

		cfg.per_page = Math.max(1, parseInt(cfg.per_page, 10) || 4);
		cfg.limit = Math.max(1, parseInt(cfg.limit, 10) || 25);

		renderPage(feed, state, cfg, strings);

		var prevBtn = qs(feed, '[data-esquina-fb-prev]');
		var nextBtn = qs(feed, '[data-esquina-fb-next]');

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				if (state.pageOffset <= 0) return;
				state.pageOffset = Math.max(0, state.pageOffset - cfg.per_page);
				stopVideos(feed);
				renderPage(feed, state, cfg, strings);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				var nextOffset = state.pageOffset + cfg.per_page;
				if (nextOffset < state.posts.length) {
					state.pageOffset = nextOffset;
					stopVideos(feed);
					renderPage(feed, state, cfg, strings);
					return;
				}
				if (state.nextCursor) {
					fetchMore(feed, state, cfg, strings).then(function () {
						if (nextOffset < state.posts.length) {
							state.pageOffset = nextOffset;
						}
						stopVideos(feed);
						renderPage(feed, state, cfg, strings);
					});
				}
			});
		}

		var modal = qs(feed, '.esquina-fb-modal');
		if (modal) {
			var closeEl = qs(modal, '.esquina-fb-modal__close');
			var backdropEl = qs(modal, '.esquina-fb-modal__backdrop');
			if (closeEl) {
				closeEl.addEventListener('click', function () {
					closeModal(feed);
				});
			}
			if (backdropEl) {
				backdropEl.addEventListener('click', function () {
					closeModal(feed);
				});
			}
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && !modal.hidden) {
					closeModal(feed);
				}
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.esquina-fb-feed').forEach(initFeed);
	});
})();
