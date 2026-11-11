(() => {
    const configs = window.esquinaFAInstances || {};
    const widgets = Array.from(document.querySelectorAll('[data-esquina-fa]'));

    if (!widgets.length) {
        return;
    }

    const dayMs = 24 * 60 * 60 * 1000;

    const syncSiblings = () => {
        const anyOpen = widgets.some((el) => el.classList.contains('is-open'));
        widgets.forEach((el) => {
            if (el.classList.contains('is-open')) {
                el.classList.remove('esquina-fa--aside');
                return;
            }
            el.classList.toggle('esquina-fa--aside', anyOpen);
        });
    };

    const initWidget = (widget) => {
        const id = widget.getAttribute('data-esquina-fa') || '';
        const config = configs[id] || {};
        const delay = Number.parseInt(config.delay, 10) || 0;
        const storageKey = config.storageKey || ('esquina_fa_closed_' + id + '_v1');
        const hide = String(config.hide || '7');
        const resetAt = Number(config.resetAt) || 0;
        const reopenTab = Boolean(config.reopenTab);

        const storageFor = (mode) => {
            try {
                return mode === 'session' ? window.sessionStorage : window.localStorage;
            } catch (error) {
                return null;
            }
        };

        const forget = () => {
            [storageFor('session'), storageFor('local')].forEach((storage) => {
                try {
                    if (storage) {
                        storage.removeItem(storageKey);
                    }
                } catch (error) {
                    // Almacenamiento bloqueado.
                }
            });
        };

        const isDismissed = () => {
            if (hide === 'never') {
                return false;
            }

            const storage = storageFor(hide);
            if (!storage) {
                return false;
            }

            let closedAt = 0;
            try {
                closedAt = Number.parseInt(storage.getItem(storageKey), 10) || 0;
            } catch (error) {
                return false;
            }

            if (!closedAt) {
                return false;
            }

            const days = Number.parseInt(hide, 10);
            const expired = hide !== 'session' && Date.now() - closedAt >= days * dayMs;

            if (closedAt < resetAt || expired) {
                forget();
                return false;
            }

            return true;
        };

        const dismissed = isDismissed();
        if (dismissed && !reopenTab) {
            return;
        }

        const toggle = widget.querySelector('.esquina-fa__toggle');
        const closeButton = widget.querySelector('.esquina-fa__close');
        const reopenButton = widget.querySelector('.esquina-fa__reopen');

        const isOpen = () => widget.classList.contains('is-open');

        const open = () => {
            // Cierra otros paneles abiertos para no solapar listas.
            widgets.forEach((other) => {
                if (other !== widget) {
                    other.classList.remove('is-open');
                    const otherToggle = other.querySelector('.esquina-fa__toggle');
                    if (otherToggle) {
                        otherToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            widget.classList.add('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
            syncSiblings();
        };

        const close = () => {
            widget.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            syncSiblings();
        };

        const persistClose = () => {
            if (hide === 'never') {
                return;
            }

            const storage = storageFor(hide);
            try {
                if (storage) {
                    storage.setItem(storageKey, String(Date.now()));
                }
            } catch (error) {
                // Sin almacenamiento solo se oculta en esta página.
            }

            window.setTimeout(() => {
                if (reopenTab) {
                    widget.classList.add('is-dismissed');
                    if (reopenButton) {
                        reopenButton.focus();
                    }
                } else {
                    widget.style.display = 'none';
                }
                syncSiblings();
            }, 200);
        };

        if (toggle) {
            toggle.addEventListener('click', () => {
                if (isOpen()) {
                    close();
                } else {
                    open();
                }
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', () => {
                close();
                persistClose();
            });
        }

        if (reopenButton) {
            reopenButton.addEventListener('click', () => {
                forget();
                widget.classList.remove('is-dismissed');
                open();
                if (toggle) {
                    toggle.focus();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) {
                close();
                if (toggle) {
                    toggle.focus();
                }
            }
        });

        document.addEventListener('click', (event) => {
            if (isOpen() && !widget.contains(event.target)) {
                close();
            }
        });

        if (dismissed) {
            widget.classList.add('is-dismissed');
        }

        window.setTimeout(() => {
            widget.hidden = false;
            window.requestAnimationFrame(() => {
                widget.style.opacity = '1';
            });
        }, delay * 1000);
    };

    widgets.forEach(initWidget);
})();
