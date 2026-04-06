(() => {
    const root = document.documentElement;
    const shell = document.querySelector('[data-shell]');
    const notificationDrawer = document.querySelector('[data-notification-drawer]');
    const notificationBackdrop = document.querySelector('.notification-backdrop');
    const switchers = document.querySelectorAll('[data-switcher]');
    const dropdowns = document.querySelectorAll('[data-dropdown]');
    const modals = document.querySelectorAll('[data-modal]');
    const slideOvers = document.querySelectorAll('[data-slide-over]');
    const themeKey = 'familyjam-theme';
    const themeNavbarSelectors = ['.main-header.navbar'];

    const applyTheme = (theme) => {
        const resolvedTheme = theme === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : theme;

        root.dataset.theme = theme;
        root.dataset.bsTheme = resolvedTheme;

        document.body.classList.toggle('dark-mode', resolvedTheme === 'dark');

        themeNavbarSelectors.forEach((selector) => {
            document.querySelectorAll(selector).forEach((navbar) => {
                navbar.classList.toggle('navbar-dark', resolvedTheme === 'dark');
                navbar.classList.toggle('navbar-light', resolvedTheme !== 'dark');
                navbar.classList.toggle('navbar-white', resolvedTheme !== 'dark');
            });
        });
    };

    const updateThemeToggleLabels = () => {
        const storedTheme = localStorage.getItem(themeKey) || 'system';
        const locale = root.lang === 'it' ? 'it' : 'en';
        const labels = {
            en: { dark: 'Dark', light: 'Light', system: 'System', prefix: 'Theme' },
            it: { dark: 'Scuro', light: 'Chiaro', system: 'Sistema', prefix: 'Tema' },
        };
        const localeLabels = labels[locale];
        const label = storedTheme === 'dark' ? localeLabels.dark : storedTheme === 'light' ? localeLabels.light : localeLabels.system;

        document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
            const labelTarget = toggle.querySelector('[data-theme-toggle-label]');
            if (labelTarget) {
                labelTarget.textContent = `${localeLabels.prefix}: ${label}`;
            } else if (!(toggle.querySelector('i'))) {
                toggle.textContent = `${localeLabels.prefix}: ${label}`;
            }
            toggle.setAttribute('aria-pressed', storedTheme === 'dark' ? 'true' : 'false');
            toggle.setAttribute('title', `${localeLabels.prefix}: ${label}`);
        });
    };

    const setTheme = (theme) => {
        localStorage.setItem(themeKey, theme);
        applyTheme(theme);
        updateThemeToggleLabels();
    };

    const storedTheme = localStorage.getItem(themeKey);
    const fallbackTheme = document.body.dataset.themePreference || 'system';
    const activeTheme = fallbackTheme !== 'system' ? fallbackTheme : (storedTheme || fallbackTheme);

    if (activeTheme) {
        localStorage.setItem(themeKey, activeTheme);
    }

    applyTheme(activeTheme);
    updateThemeToggleLabels();

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const current = localStorage.getItem(themeKey) || 'system';
            const next = current === 'dark' ? 'light' : current === 'light' ? 'system' : 'dark';
            setTheme(next);
        });
    });

    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        const currentTheme = localStorage.getItem(themeKey) || document.body.dataset.themePreference || 'system';

        if (currentTheme === 'system') {
            applyTheme('system');
        }
    });

    document.querySelectorAll('[data-shell-open]').forEach((button) => {
        button.addEventListener('click', () => shell?.classList.add('is-open'));
    });

    document.querySelectorAll('[data-shell-close]').forEach((button) => {
        button.addEventListener('click', () => shell?.classList.remove('is-open'));
    });

    const syncNotificationUi = (isOpen) => {
        document.body.classList.toggle('has-notification-drawer-open', isOpen);

        if (notificationDrawer instanceof HTMLElement) {
            notificationDrawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }

        if (notificationBackdrop instanceof HTMLElement) {
            notificationBackdrop.hidden = !isOpen;
            notificationBackdrop.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }

        document.querySelectorAll('[data-notification-toggle]').forEach((toggle) => {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    };

    const closeNotifications = () => {
        if (!notificationDrawer) {
            return;
        }

        notificationDrawer.hidden = true;
        syncNotificationUi(false);
    };

    const openNotifications = () => {
        if (!notificationDrawer) {
            return;
        }

        notificationDrawer.hidden = false;
        syncNotificationUi(true);
    };

    document.querySelectorAll('[data-notification-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            if (!notificationDrawer) {
                return;
            }

            const shouldOpen = notificationDrawer.hidden;
            if (shouldOpen) {
                openNotifications();
                return;
            }

            closeNotifications();
        });
    });

    document.querySelectorAll('[data-notification-close]').forEach((button) => {
        button.addEventListener('click', closeNotifications);
    });

    switchers.forEach((switcher) => {
        if (switcher.hasAttribute('data-dropdown')) {
            return;
        }

        const toggle = switcher.querySelector('[data-switcher-toggle]');
        const menu = switcher.querySelector('[data-switcher-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            menu.hidden = expanded;
        });
    });

    const closeDropdown = (dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
    };

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';

            dropdowns.forEach((otherDropdown) => {
                if (otherDropdown !== dropdown) {
                    closeDropdown(otherDropdown);
                }
            });

            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            menu.hidden = expanded;
        });
    });

    const closeDialogLike = (element) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        element.hidden = true;
        element.setAttribute('aria-hidden', 'true');
    };

    const openDialogLike = (element) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        element.hidden = false;
        element.setAttribute('aria-hidden', 'false');
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-modal-open');

            if (!target) {
                return;
            }

            openDialogLike(document.querySelector(target));
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            closeDialogLike(button.closest('[data-modal]'));
        });
    });

    document.querySelectorAll('[data-slide-over-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-slide-over-open');

            if (!target) {
                return;
            }

            openDialogLike(document.querySelector(target));
        });
    });

    document.querySelectorAll('[data-slide-over-close]').forEach((button) => {
        button.addEventListener('click', () => {
            closeDialogLike(button.closest('[data-slide-over]'));
        });
    });

    document.querySelectorAll('[data-alert-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = button.closest('[data-alert]');
            alert?.remove();
        });
    });

    document.querySelectorAll('.custom-file-input').forEach((input) => {
        input.addEventListener('change', () => {
            const label = input.closest('.custom-file')?.querySelector('.custom-file-label');
            const fileName = input.files && input.files[0] ? input.files[0].name : '';

            if (label) {
                label.textContent = fileName || (root.lang === 'it' ? 'Scegli file' : 'Choose file');
            }

            if (fileName && input.id === 'avatarImage') {
                const preview = document.querySelector('.profile-avatar-preview');
                if (preview instanceof HTMLImageElement && input.files && input.files[0]) {
                    preview.src = URL.createObjectURL(input.files[0]);
                }
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (notificationDrawer && !notificationDrawer.hidden) {
            const toggleClicked = event.target instanceof Element && event.target.closest('[data-notification-toggle]');
            const insideDrawer = event.target instanceof Element && event.target.closest('[data-notification-drawer]');
            const backdropClicked = event.target instanceof Element && event.target.closest('.notification-backdrop');

            if (backdropClicked || (!toggleClicked && !insideDrawer)) {
                closeNotifications();
            }
        }

        switchers.forEach((switcher) => {
            if (switcher.hasAttribute('data-dropdown')) {
                return;
            }

            const menu = switcher.querySelector('[data-switcher-menu]');
            const toggle = switcher.querySelector('[data-switcher-toggle]');

            if (!menu || !toggle) {
                return;
            }

            const insideSwitcher = event.target instanceof Element && event.target.closest('[data-switcher]');

            if (!insideSwitcher) {
                menu.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        dropdowns.forEach((dropdown) => {
            const insideDropdown = event.target instanceof Element && event.target.closest('[data-dropdown]');

            if (!insideDropdown || event.target.closest('[data-dropdown]') !== dropdown) {
                closeDropdown(dropdown);
            }
        });

        modals.forEach((modal) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target === modal || event.target.closest('[data-modal-close]')) {
                closeDialogLike(modal);
            }
        });

        slideOvers.forEach((slideOver) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target === slideOver || event.target.closest('[data-slide-over-close]')) {
                closeDialogLike(slideOver);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            shell?.classList.remove('is-open');
            closeNotifications();
            dropdowns.forEach(closeDropdown);
            modals.forEach(closeDialogLike);
            slideOvers.forEach(closeDialogLike);
        }
    });

    if (notificationDrawer) {
        syncNotificationUi(!notificationDrawer.hidden);
        closeNotifications();
    }

    const notificationMenus = document.querySelectorAll('[data-notification-menu]');
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderNotificationMenu = (menu, payload) => {
        const unreadCount = Number(payload.unreadCount || 0);
        const badge = menu.querySelector('[data-notification-badge]');
        const header = menu.querySelector('[data-notification-header]');
        const itemsTarget = menu.querySelector('[data-notification-items]');
        const centerLink = menu.querySelector('[data-notification-center-link]');
        const markAllForm = menu.querySelector('[data-notification-mark-all-form]');
        const markAllDivider = menu.querySelector('[data-notification-mark-all-divider]');
        const locale = document.documentElement.lang === 'it' ? 'it' : 'en';
        const labels = {
            it: {
                center: 'Centro notifiche',
                empty: 'Nessuna notifica non letta',
            },
            en: {
                center: 'Notification center',
                empty: 'No unread notifications',
            },
        };

        if (header) {
            header.textContent = unreadCount > 0 ? `${labels[locale].center} (${unreadCount})` : labels[locale].center;
        }

        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = String(unreadCount);
                badge.hidden = false;
            } else {
                badge.hidden = true;
            }
        } else if (unreadCount > 0) {
            const toggle = menu.querySelector('.nav-link');
            if (toggle) {
                const createdBadge = document.createElement('span');
                createdBadge.className = 'badge badge-warning navbar-badge';
                createdBadge.setAttribute('data-notification-badge', '');
                createdBadge.textContent = String(unreadCount);
                toggle.appendChild(createdBadge);
            }
        }

        if (centerLink && payload.centerUrl) {
            centerLink.setAttribute('href', payload.centerUrl);
        }

        if (markAllForm && payload.markAllUrl) {
            markAllForm.setAttribute('action', payload.markAllUrl);
            markAllForm.style.display = unreadCount > 0 ? '' : 'none';
        }

        if (markAllDivider) {
            markAllDivider.hidden = unreadCount <= 0;
        }

        if (!itemsTarget) {
            return;
        }

        const items = Array.isArray(payload.items) ? payload.items : [];

        if (items.length === 0) {
            itemsTarget.innerHTML = `<span class="dropdown-item text-muted" data-notification-empty>${labels[locale].empty}</span><div class="dropdown-divider"></div>`;
            return;
        }

        const centerUrl = payload.centerUrl || '#';
        itemsTarget.innerHTML = items.map((item) => {
            const href = escapeHtml(item.target_url || centerUrl);
            const title = escapeHtml(item.title || '');
            const createdAt = escapeHtml(item.created_at || '');

            return `<a href="${href}" class="dropdown-item"><i class="fas fa-circle text-warning mr-2"></i>${title}<span class="float-right text-muted text-sm">${createdAt}</span></a><div class="dropdown-divider"></div>`;
        }).join('');
    };

    const ensureToastStack = () => {
        let stack = document.querySelector('[data-live-toast-stack]');

        if (!(stack instanceof HTMLElement)) {
            stack = document.createElement('div');
            stack.setAttribute('data-live-toast-stack', '');
            stack.style.position = 'fixed';
            stack.style.top = '1rem';
            stack.style.right = '1rem';
            stack.style.zIndex = '1080';
            stack.style.display = 'grid';
            stack.style.gap = '.75rem';
            stack.style.maxWidth = '20rem';
            document.body.appendChild(stack);
        }

        return stack;
    };

    const showLiveNotificationToast = (item) => {
        if (!item || document.hidden) {
            return;
        }

        const locale = document.documentElement.lang === 'it' ? 'it' : 'en';
        const labels = {
            it: { title: 'Nuova notifica' },
            en: { title: 'New notification' },
        };
        const stack = ensureToastStack();
        const toast = document.createElement('div');
        toast.className = 'toast show bg-info text-white border-0';
        toast.setAttribute('role', 'status');
        toast.innerHTML = `
            <div class="toast-header bg-info text-white border-0">
                <strong class="mr-auto">${escapeHtml(labels[locale].title)}</strong>
                <button type="button" class="ml-2 mb-1 close text-white" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">${escapeHtml(item.title || '')}</div>
        `;

        const closeButton = toast.querySelector('.close');
        closeButton?.addEventListener('click', () => toast.remove());
        stack.appendChild(toast);
        window.setTimeout(() => toast.remove(), 5000);
    };

    notificationMenus.forEach((menu) => {
        const pollUrl = menu.getAttribute('data-notification-poll-url');

        if (!pollUrl) {
            return;
        }

        let polling = false;
        let previousUnreadCount = Number(menu.querySelector('[data-notification-badge]')?.textContent || 0);

        const pollNotifications = async () => {
            if (polling || document.hidden) {
                return;
            }

            polling = true;

            try {
                const response = await fetch(pollUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                renderNotificationMenu(menu, payload);

                const unreadCount = Number(payload.unreadCount || 0);
                if (unreadCount > previousUnreadCount && Array.isArray(payload.items) && payload.items.length > 0) {
                    showLiveNotificationToast(payload.items[0]);
                }
                previousUnreadCount = unreadCount;
            } catch (error) {
                console.warn('Notification polling failed', error);
            } finally {
                polling = false;
            }
        };

        pollNotifications();
        window.setInterval(pollNotifications, 30000);
    });

    const expenseForm = document.querySelector('[data-expense-form]');

    if (expenseForm) {
        const splitMethodField = expenseForm.querySelector('[data-split-method]');
        const totalField = expenseForm.querySelector('[data-expense-total]');
        const reviewTotal = expenseForm.querySelector('[data-expense-review-total]');
        const reviewMethod = expenseForm.querySelector('[data-expense-review-method]');
        const reviewPayers = expenseForm.querySelector('[data-expense-review-payers]');
        const reviewParticipants = expenseForm.querySelector('[data-expense-review-participants]');
        const reviewPayerTotal = expenseForm.querySelector('[data-expense-review-payer-total]');
        const reviewSplitTotal = expenseForm.querySelector('[data-expense-review-split-total]');
        const steps = Array.from(expenseForm.querySelectorAll('[data-expense-step]'));
        const stepButtons = Array.from(expenseForm.querySelectorAll('[data-expense-step-nav]'));
        let currentStepIndex = 0;
        const expenseLocale = root.lang === 'it' ? 'it' : 'en';
        const expenseLabels = {
            en: {
                equal: 'Equal split',
                exact: 'Exact amounts',
                percentage: 'Percentage split',
                shares: 'Shares split',
                autoEqual: 'Auto split equally',
                autoRemainder: 'Remainder assigned automatically',
                autoPercentages: 'Percentages must total 100%',
                autoShares: 'Shares converted automatically',
                owes: 'Owes',
                pays: 'Pays',
            },
            it: {
                equal: 'Ripartizione uguale',
                exact: 'Importi esatti',
                percentage: 'Ripartizione percentuale',
                shares: 'Ripartizione per quote',
                autoEqual: 'Ripartizione automatica uguale',
                autoRemainder: 'Residuo assegnato automaticamente',
                autoPercentages: 'Le percentuali devono totalizzare 100%',
                autoShares: 'Quote convertite automaticamente',
                owes: 'Deve',
                pays: 'Paga',
            },
        };
        const expenseCopy = expenseLabels[expenseLocale];

        const splitMethodLabels = {
            equal: expenseCopy.equal,
            exact: expenseCopy.exact,
            percentage: expenseCopy.percentage,
            shares: expenseCopy.shares,
        };

        const toCents = (value) => {
            const parsed = Number.parseFloat(String(value || '0').replace(',', '.'));
            return Number.isFinite(parsed) ? Math.round(parsed * 100) : 0;
        };

        const fromCents = (value) => (value / 100).toFixed(2);

        const currencyValue = () => {
            const currencyField = expenseForm.querySelector('input[name="currency"]');
            return currencyField instanceof HTMLInputElement ? (currencyField.value.toUpperCase() || 'EUR') : 'EUR';
        };

        const formatMoney = (cents) => `${fromCents(cents)} ${currencyValue()}`;

        const distributeCents = (totalCents, count) => {
            if (count <= 0) {
                return [];
            }

            const base = Math.floor(totalCents / count);
            let remainder = totalCents - (base * count);
            return Array.from({ length: count }, () => {
                const value = base + (remainder > 0 ? 1 : 0);
                remainder = Math.max(0, remainder - 1);
                return value;
            });
        };

        const enabledPayerRows = () => Array.from(expenseForm.querySelectorAll('[data-expense-payer-toggle]:checked'))
            .map((checkbox) => checkbox.closest('.expense-member-card'))
            .filter((row) => row instanceof HTMLElement);

        const enabledParticipantRows = () => Array.from(expenseForm.querySelectorAll('[data-expense-participant-toggle]:checked'))
            .map((checkbox) => checkbox.closest('.expense-member-card'))
            .filter((row) => row instanceof HTMLElement);

        const autoBalancePayers = () => {
            const rows = enabledPayerRows();
            const totalCents = toCents(totalField instanceof HTMLInputElement ? totalField.value : '0');

            if (rows.length === 0 || totalCents <= 0) {
                return;
            }

            const inputs = rows
                .map((row) => row.querySelector('[data-expense-payer-amount]'))
                .filter((input) => input instanceof HTMLInputElement);

            if (inputs.length === 1) {
                inputs[0].value = fromCents(totalCents);
                return;
            }

            const cents = inputs.map((input) => toCents(input.value));
            const filled = cents.filter((value) => value > 0).length;

            if (filled === 0) {
                distributeCents(totalCents, inputs.length).forEach((value, index) => {
                    inputs[index].value = fromCents(value);
                });
                return;
            }

            const emptyIndexes = cents.map((value, index) => value <= 0 ? index : -1).filter((value) => value >= 0);

            if (emptyIndexes.length === 1) {
                const assigned = cents.reduce((sum, value) => sum + value, 0);
                const remainder = Math.max(0, totalCents - assigned);
                inputs[emptyIndexes[0]].value = fromCents(remainder);
            }
        };

        const autoBalanceExact = (rows, totalCents) => {
            const inputs = rows
                .map((row) => row.querySelector('input[name*="[owed_amount]"]'))
                .filter((input) => input instanceof HTMLInputElement);

            if (inputs.length === 0) {
                return [];
            }

            if (inputs.length === 1) {
                inputs[0].value = fromCents(totalCents);
                return [totalCents];
            }

            const cents = inputs.map((input) => toCents(input.value));
            const filled = cents.filter((value) => value > 0).length;

            if (filled === 0) {
                const distributed = distributeCents(totalCents, inputs.length);
                distributed.forEach((value, index) => {
                    inputs[index].value = fromCents(value);
                });
                return distributed;
            }

            const emptyIndexes = cents.map((value, index) => value <= 0 ? index : -1).filter((value) => value >= 0);

            if (emptyIndexes.length === 1) {
                const assigned = cents.reduce((sum, value) => sum + value, 0);
                const remainder = Math.max(0, totalCents - assigned);
                inputs[emptyIndexes[0]].value = fromCents(remainder);
                cents[emptyIndexes[0]] = remainder;
            }

            return inputs.map((input) => toCents(input.value));
        };

        const autoBalancePercentages = (rows) => {
            const inputs = rows
                .map((row) => row.querySelector('input[name*="[percentage]"]'))
                .filter((input) => input instanceof HTMLInputElement);

            if (inputs.length === 0) {
                return [];
            }

            return inputs.map((input) => Math.round(Number.parseFloat(input.value || '0') * 100) || 0);
        };

        const autoBalanceShares = (rows) => {
            const inputs = rows
                .map((row) => row.querySelector('input[name*="[share_units]"]'))
                .filter((input) => input instanceof HTMLInputElement);

            if (inputs.length === 0) {
                return [];
            }

            const values = inputs.map((input) => Number.parseFloat(input.value || '0'));
            const hasAny = values.some((value) => Number.isFinite(value) && value > 0);

            if (!hasAny) {
                inputs.forEach((input) => {
                    input.value = '1.00';
                });
            }

            return inputs.map((input) => Number.parseFloat(input.value || '0') || 0);
        };

        const updateSplitPreviews = () => {
            const rows = enabledParticipantRows();
            const method = splitMethodField instanceof HTMLSelectElement ? splitMethodField.value : 'equal';
            const totalCents = toCents(totalField instanceof HTMLInputElement ? totalField.value : '0');
            let owedValues = [];
            let labels = [];

            if (rows.length === 0 || totalCents <= 0) {
                expenseForm.querySelectorAll('[data-expense-split-preview]').forEach((node) => {
                    node.textContent = `0.00 ${currencyValue()}`;
                });
                return;
            }

            if (method === 'equal') {
                owedValues = distributeCents(totalCents, rows.length);
                labels = rows.map(() => expenseCopy.autoEqual);
            } else if (method === 'exact') {
                owedValues = autoBalanceExact(rows, totalCents);
                labels = rows.map(() => expenseCopy.autoRemainder);
            } else if (method === 'percentage') {
                const percentages = autoBalancePercentages(rows);
                const totalBasis = percentages.reduce((sum, value) => sum + value, 0);

                if (totalBasis > 0) {
                    const rawValues = percentages.map((value) => (totalCents * value) / 10000);
                    owedValues = rawValues.map((value) => Math.floor(value));
                    let remainder = Math.round(rawValues.reduce((sum, value) => sum + value, 0)) - owedValues.reduce((sum, value) => sum + value, 0);
                    const fractions = rawValues.map((value, index) => ({ index, fraction: value - Math.floor(value) }))
                        .sort((left, right) => right.fraction - left.fraction || left.index - right.index);

                    for (let index = 0; index < fractions.length && remainder > 0; index += 1, remainder -= 1) {
                        owedValues[fractions[index].index] += 1;
                    }
                } else {
                    owedValues = rows.map(() => 0);
                }

                labels = rows.map(() => totalBasis === 10000 ? expenseCopy.percentage : expenseCopy.autoPercentages);
            } else if (method === 'shares') {
                const shares = autoBalanceShares(rows);
                const totalShares = shares.reduce((sum, value) => sum + value, 0) || 1;
                owedValues = shares.map((value) => Math.floor((totalCents * value) / totalShares));
                let remainder = totalCents - owedValues.reduce((sum, value) => sum + value, 0);
                for (let index = 0; index < owedValues.length && remainder > 0; index += 1, remainder -= 1) {
                    owedValues[index] += 1;
                }
                labels = rows.map(() => expenseCopy.autoShares);
            }

            rows.forEach((row, index) => {
                const preview = row.querySelector('[data-expense-split-preview]');
                const badge = row.querySelector('[data-expense-split-label]');
                if (preview) {
                    preview.textContent = `${expenseCopy.owes} ${formatMoney(owedValues[index] || 0)}`;
                }
                if (badge) {
                    badge.textContent = labels[index] || expenseCopy.autoEqual;
                }
            });

            if (reviewSplitTotal) {
                reviewSplitTotal.textContent = formatMoney(owedValues.reduce((sum, value) => sum + value, 0));
            }
        };

        const updatePayerPreviews = () => {
            autoBalancePayers();
            const rows = enabledPayerRows();
            const totals = rows.map((row) => {
                const input = row.querySelector('[data-expense-payer-amount]');
                return input instanceof HTMLInputElement ? toCents(input.value) : 0;
            });

            rows.forEach((row, index) => {
                const preview = row.querySelector('[data-expense-payer-preview]');
                if (preview) {
                    preview.textContent = `${expenseCopy.pays} ${formatMoney(totals[index] || 0)}`;
                }
            });

            if (reviewPayerTotal) {
                reviewPayerTotal.textContent = formatMoney(totals.reduce((sum, value) => sum + value, 0));
            }
        };

        const goToStep = (targetIndex) => {
            currentStepIndex = Math.max(0, Math.min(targetIndex, steps.length - 1));
            steps.forEach((step, index) => {
                step.hidden = index !== currentStepIndex;
            });
            stepButtons.forEach((button, index) => {
                button.classList.toggle('is-active', index === currentStepIndex);
                button.classList.toggle('is-complete', index < currentStepIndex);
            });
        };

        const applySplitMethod = () => {
            const currentMethod = splitMethodField instanceof HTMLSelectElement ? splitMethodField.value : 'equal';

            expenseForm.querySelectorAll('[data-split-input]').forEach((node) => {
                const wrapper = node instanceof HTMLElement ? node : null;

                if (!wrapper) {
                    return;
                }

                wrapper.hidden = wrapper.dataset.splitInput !== currentMethod;
            });

            if (reviewMethod) {
                reviewMethod.textContent = splitMethodLabels[currentMethod] || currentMethod;
            }

            updateSplitPreviews();
        };

        const updateReview = () => {
            const total = totalField instanceof HTMLInputElement && totalField.value !== '' ? Number(totalField.value) : 0;
            const enabledPayers = expenseForm.querySelectorAll('[data-expense-payer-toggle]:checked').length;
            const enabledParticipants = expenseForm.querySelectorAll('[data-expense-participant-toggle]:checked').length;

            if (reviewTotal) {
                reviewTotal.textContent = `${total.toFixed(2)} ${currencyValue()}`;
            }

            if (reviewPayers) {
                reviewPayers.textContent = String(enabledPayers);
            }

            if (reviewParticipants) {
                reviewParticipants.textContent = String(enabledParticipants);
            }

            updatePayerPreviews();
            updateSplitPreviews();
        };

        const validateCurrentStep = () => {
            const currentStep = steps[currentStepIndex];

            if (!(currentStep instanceof HTMLElement)) {
                return true;
            }

            const fields = Array.from(currentStep.querySelectorAll('input, select, textarea'));

            for (const field of fields) {
                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    if (field.closest('[hidden]')) {
                        continue;
                    }

                    if (!field.reportValidity()) {
                        return false;
                    }
                }
            }

            return true;
        };

        splitMethodField?.addEventListener('change', () => {
            applySplitMethod();
            updateReview();
        });

        stepButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                goToStep(index);
            });
        });

        expenseForm.querySelectorAll('[data-expense-step-next]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!validateCurrentStep()) {
                    return;
                }

                goToStep(currentStepIndex + 1);
            });
        });

        expenseForm.querySelectorAll('[data-expense-step-back]').forEach((button) => {
            button.addEventListener('click', () => {
                goToStep(currentStepIndex - 1);
            });
        });

        expenseForm.querySelectorAll('input, select, textarea').forEach((field) => {
            field.addEventListener('input', updateReview);
            field.addEventListener('change', updateReview);
        });

        applySplitMethod();
        updateReview();
        goToStep(0);
    }

    const recurringForm = document.querySelector('[data-recurring-form]');

    if (recurringForm) {
        const frequencyField = recurringForm.querySelector('[data-recurring-frequency]');
        const splitMethodField = recurringForm.querySelector('[data-split-method]');

        const applyRecurringFrequency = () => {
            const frequency = frequencyField instanceof HTMLSelectElement ? frequencyField.value : 'monthly';

            recurringForm.querySelectorAll('[data-recurring-field]').forEach((node) => {
                const field = node instanceof HTMLElement ? node : null;

                if (!field) {
                    return;
                }

                const type = field.dataset.recurringField;
                field.hidden = !(
                    (type === 'by_weekday' && frequency === 'weekly') ||
                    (type === 'day_of_month' && frequency === 'monthly') ||
                    (type === 'custom_unit' && frequency === 'custom')
                );
            });
        };

        const applySplitMethod = () => {
            const currentMethod = splitMethodField instanceof HTMLSelectElement ? splitMethodField.value : 'equal';

            recurringForm.querySelectorAll('[data-split-input]').forEach((node) => {
                const wrapper = node instanceof HTMLElement ? node : null;

                if (!wrapper) {
                    return;
                }

                wrapper.hidden = wrapper.dataset.splitInput !== currentMethod;
            });
        };

        frequencyField?.addEventListener('change', applyRecurringFrequency);
        splitMethodField?.addEventListener('change', applySplitMethod);

        applyRecurringFrequency();
        applySplitMethod();
    }

    const choreForm = document.querySelector('[data-chore-form]');

    if (choreForm) {
        const assignmentModeField = choreForm.querySelector('[data-chore-assignment-mode]');
        const recurringToggle = choreForm.querySelector('[data-chore-recurring-toggle]');
        const recurringFields = choreForm.querySelector('[data-chore-recurring-fields]');

        const applyAssignmentMode = () => {
            const mode = assignmentModeField instanceof HTMLSelectElement ? assignmentModeField.value : 'fixed';

            choreForm.querySelectorAll('[data-chore-assignment-field]').forEach((node) => {
                const field = node instanceof HTMLElement ? node : null;

                if (!field) {
                    return;
                }

                field.hidden = field.dataset.choreAssignmentField !== mode;
            });
        };

        const applyRecurringToggle = () => {
            const enabled = recurringToggle instanceof HTMLInputElement ? recurringToggle.checked : false;

            if (recurringFields instanceof HTMLElement) {
                recurringFields.hidden = !enabled;
            }
        };

        assignmentModeField?.addEventListener('change', applyAssignmentMode);
        recurringToggle?.addEventListener('change', applyRecurringToggle);

        applyAssignmentMode();
        applyRecurringToggle();
    }

    const shoppingQuickAddInput = document.querySelector('[data-shopping-quick-add-input]');

    if (shoppingQuickAddInput instanceof HTMLInputElement && window.innerWidth >= 768) {
        shoppingQuickAddInput.focus();
    }

    const createChart = (canvas, type, config) => {
        if (!(canvas instanceof HTMLCanvasElement) || typeof window.Chart !== 'function') {
            return;
        }

        const existing = window.Chart.getChart(canvas);
        if (existing) {
            existing.destroy();
        }

        new window.Chart(canvas, {
            type,
            data: config.data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type !== 'bar',
                        labels: { color: '#ced4da' },
                    },
                },
                scales: type === 'bar' ? {
                    x: {
                        ticks: { color: '#adb5bd' },
                        grid: { color: 'rgba(255,255,255,0.06)' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#adb5bd' },
                        grid: { color: 'rgba(255,255,255,0.06)' },
                    },
                } : {},
            },
        });
    };

    document.querySelectorAll('[data-expense-category-chart]').forEach((canvas) => {
        if (!(canvas instanceof HTMLCanvasElement)) {
            return;
        }

        const payload = canvas.dataset.expenseCategoryChart;
        if (!payload) {
            return;
        }

        const parsed = JSON.parse(payload);
        createChart(canvas, 'doughnut', {
            data: {
                labels: parsed.labels || [],
                datasets: [{
                    data: parsed.values || [],
                    backgroundColor: ['#3c8dbc', '#00a65a', '#f39c12', '#dd4b39', '#605ca8', '#39cccc', '#d81b60', '#001f3f'],
                    borderWidth: 0,
                }],
            },
        });
    });

    document.querySelectorAll('[data-expense-month-chart]').forEach((canvas) => {
        if (!(canvas instanceof HTMLCanvasElement)) {
            return;
        }

        const payload = canvas.dataset.expenseMonthChart;
        if (!payload) {
            return;
        }

        const parsed = JSON.parse(payload);
        createChart(canvas, 'bar', {
            data: {
                labels: parsed.labels || [],
                datasets: [{
                    label: root.lang === 'it' ? 'Spese' : 'Expenses',
                    data: parsed.values || [],
                    backgroundColor: '#3c8dbc',
                    borderRadius: 8,
                }],
            },
        });
    });
})();
