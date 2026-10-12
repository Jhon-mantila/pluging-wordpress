(() => {
    const widget = document.getElementById('esquina-fa');
    if (!widget) {
        return;
    }

    const config = window.esquinaFA || {};
    const delay = Number.parseInt(config.delay, 10) || 0;
    const storageKey = config.storageKey || 'esquina_fa_closed_v1';
    const hide = String(config.hide || '7');
    const resetAt = Number(config.resetAt) || 0;
    const reopenTab = Boolean(config.reopenTab);
    const dayMs = 24 * 60 * 60 * 1000;

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
                // Almacenamiento bloqueado: no hay nada que borrar.
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
        widget.classList.add('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    };

    const close = () => {
        widget.classList.remove('is-open');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
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
})();
