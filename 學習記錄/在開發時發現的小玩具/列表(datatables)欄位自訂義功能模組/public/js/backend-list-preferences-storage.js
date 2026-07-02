/**
 * 列表欄位設定儲存：帳號同步（API／DB）或本機暫存（localStorage）。
 */
(function (window) {
    'use strict';

    const MODE_KEY = 'backend_list_prefs:storage_mode';
    const PREFS_PREFIX = 'backend_list_prefs:';

    const MODES = {
        SERVER: 'server',
        LOCAL: 'local',
    };

    function prefsKey(pageKey) {
        return PREFS_PREFIX + pageKey;
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function getStorageMode(defaultMode) {
        try {
            const saved = localStorage.getItem(MODE_KEY);
            if (saved === MODES.SERVER || saved === MODES.LOCAL) {
                return saved;
            }
        } catch (e) {
            /* ignore */
        }

        return defaultMode || MODES.SERVER;
    }

    function setStorageMode(mode) {
        try {
            localStorage.setItem(MODE_KEY, mode);
        } catch (e) {
            /* ignore */
        }
    }

    function readLocal(pageKey) {
        try {
            const raw = localStorage.getItem(prefsKey(pageKey));
            if (!raw) {
                return {};
            }
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function writeLocal(pageKey, preferences) {
        try {
            localStorage.setItem(prefsKey(pageKey), JSON.stringify(preferences));
        } catch (e) {
            /* ignore */
        }
    }

    function removeLocal(pageKey) {
        try {
            localStorage.removeItem(prefsKey(pageKey));
        } catch (e) {
            /* ignore */
        }
    }

    async function loadPreferences(apiBaseUrl, pageKey, storageMode) {
        if (storageMode === MODES.LOCAL) {
            return readLocal(pageKey);
        }

        if (!apiBaseUrl) {
            return {};
        }

        try {
            const response = await fetch(`${apiBaseUrl}/${encodeURIComponent(pageKey)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                return {};
            }
            const json = await response.json();

            return json.preferences || {};
        } catch (e) {
            return {};
        }
    }

    async function savePreferences(apiBaseUrl, pageKey, preferences, storageMode) {
        if (storageMode === MODES.LOCAL) {
            writeLocal(pageKey, preferences);
            return { preferences };
        }

        if (!apiBaseUrl) {
            throw new Error('apiBaseUrl required for server storage');
        }

        const response = await fetch(`${apiBaseUrl}/${encodeURIComponent(pageKey)}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ preferences }),
        });

        if (!response.ok) {
            throw new Error('save failed');
        }

        return response.json();
    }

    async function resetPreferences(apiBaseUrl, pageKey, storageMode) {
        if (storageMode === MODES.LOCAL) {
            removeLocal(pageKey);
            return {};
        }

        if (!apiBaseUrl) {
            throw new Error('apiBaseUrl required for server storage');
        }

        const response = await fetch(`${apiBaseUrl}/${encodeURIComponent(pageKey)}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('reset failed');
        }

        return response.json();
    }

    function bindStorageToggle(options) {
        const selector = options.toggleSelector || '#list-prefs-storage-mode';
        const $input = window.jQuery ? window.jQuery(selector) : null;

        if (!$input || !$input.length || !$input.is('input')) {
            return;
        }

        const storageMode = options.storageMode || getStorageMode(options.defaultStorageMode);
        const $toggle = $input.closest('.list-prefs-storage-toggle');
        const $localLabel = $toggle.find('[data-storage-mode="local"]');
        const $serverLabel = $toggle.find('[data-storage-mode="server"]');

        function syncLabels(isServer) {
            $localLabel.toggleClass('is-active', !isServer);
            $serverLabel.toggleClass('is-active', isServer);
        }

        $input.prop('checked', storageMode === MODES.SERVER);
        syncLabels(storageMode === MODES.SERVER);

        $input
            .off('change.listPrefsStorage')
            .on('change.listPrefsStorage', function () {
                const nextMode = this.checked ? MODES.SERVER : MODES.LOCAL;
                if (nextMode === storageMode) {
                    return;
                }

                const confirmText =
                    nextMode === MODES.LOCAL
                        ? '改為「本機暫存」後，設定只保存在此瀏覽器，換電腦或清除瀏覽資料會消失。確定切換？'
                        : '改為「帳號同步」後，設定會存入資料庫並跟著帳號走。確定切換？';

                const proceed = function () {
                    setStorageMode(nextMode);
                    window.location.reload();
                };

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        text: confirmText,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '確定',
                        cancelButtonText: '取消',
                        scrollbarPadding: false,
                        heightAuto: false,
                        returnFocus: false,
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            proceed();
                        } else {
                            $input.prop('checked', storageMode === MODES.SERVER);
                            syncLabels(storageMode === MODES.SERVER);
                        }
                    });
                } else if (window.confirm(confirmText)) {
                    proceed();
                } else {
                    $input.prop('checked', storageMode === MODES.SERVER);
                    syncLabels(storageMode === MODES.SERVER);
                }
            });
    }

    window.BackendListPreferencesStorage = {
        MODES,
        getStorageMode,
        setStorageMode,
        loadPreferences,
        savePreferences,
        resetPreferences,
        bindStorageToggle,
    };
})(window);
