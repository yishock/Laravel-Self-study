/**
 * 後台列表個人化設定（每頁獨立 pageKey）：欄寬、欄序、排序、每頁筆數。
 * 儲存方式：帳號同步（API／DB）或本機暫存（localStorage），可切換。
 */
(function (window, $) {
    'use strict';

    const SAVE_DELAY_MS = 600;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    if (!window.BackendListPreferencesStorage) {
        window.BackendListPreferencesStorage = {
            MODES: { SERVER: 'server', LOCAL: 'local' },
            getStorageMode: function (defaultMode) {
                return defaultMode || 'server';
            },
            setStorageMode: function () {},
            bindStorageToggle: function () {},
            loadPreferences: async function (apiBaseUrl, pageKey) {
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
            },
            savePreferences: async function (apiBaseUrl, pageKey, preferences) {
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
            },
            resetPreferences: async function (apiBaseUrl, pageKey) {
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
            },
        };
    }

    const Storage = window.BackendListPreferencesStorage;

    function getColumnKey(column, index) {
        if (column.name) {
            return String(column.name);
        }
        if (typeof column.data === 'string' && column.data !== '') {
            return column.data;
        }

        return `col_${index}`;
    }

    function mergeColumnOrder(defaultKeys, savedOrder) {
        const result = [];
        const seen = new Set();

        (savedOrder || []).forEach((key) => {
            if (defaultKeys.includes(key) && !seen.has(key)) {
                result.push(key);
                seen.add(key);
            }
        });

        defaultKeys.forEach((key) => {
            if (!seen.has(key)) {
                result.push(key);
            }
        });

        return result;
    }

    function reorderColumns(columnDefs, orderKeys) {
        const map = {};
        columnDefs.forEach((col, index) => {
            map[getColumnKey(col, index)] = col;
        });

        return orderKeys.map((key) => map[key]).filter(Boolean);
    }

    function applyWidths(columns, widths) {
        if (!widths || typeof widths !== 'object') {
            return columns;
        }

        return columns.map((col, index) => {
            const key = getColumnKey(col, index);
            const width = widths[key];
            if (!width) {
                return col;
            }

            return Object.assign({}, col, { width: `${parseInt(width, 10)}px` });
        });
    }

    function visualKeysFromTable(table, keyByColumnIndex) {
        if (table.colReorder) {
            return table.colReorder.order().map(function (idx) {
                return keyByColumnIndex[idx];
            });
        }

        return keyByColumnIndex.slice();
    }

    function resolveSortOrder(prefs, columns, defaultOrder) {
        const findOrderableIndexByKey = function (key) {
            for (let i = 0; i < columns.length; i++) {
                if (getColumnKey(columns[i], i) === key && columns[i].orderable !== false) {
                    return i;
                }
            }

            return -1;
        };

        if (prefs.sortKey) {
            const idx = findOrderableIndexByKey(prefs.sortKey);
            if (idx >= 0) {
                return [[idx, prefs.sortDir || 'asc']];
            }
        }

        if (prefs.order && prefs.order.length) {
            const idx = prefs.order[0][0];
            const dir = prefs.order[0][1];
            if (columns[idx] && columns[idx].orderable !== false) {
                return [[idx, dir]];
            }
        }

        if (defaultOrder && defaultOrder.length) {
            const idx = defaultOrder[0][0];
            const dir = defaultOrder[0][1];
            if (columns[idx] && columns[idx].orderable !== false) {
                return [[idx, dir]];
            }
            const fallbackIdx = findOrderableIndexByKey(getColumnKey(columns[idx], idx));
            if (fallbackIdx >= 0) {
                return [[fallbackIdx, dir]];
            }
        }

        return defaultOrder || [];
    }

    function collectPreferences(table, keyByColumnIndex) {
        const columnOrder = visualKeysFromTable(table, keyByColumnIndex).filter(Boolean);

        const columnWidths = {};
        table.columns().every(function (colIdx) {
            const key = keyByColumnIndex[colIdx];
            if (!key) {
                return;
            }
            const $header = $(table.column(colIdx).header());
            columnWidths[key] = Math.round($header.outerWidth());
        });

        const currentOrder = table.order();
        const visualKeys = visualKeysFromTable(table, keyByColumnIndex);
        let sortKey = null;
        let sortDir = 'asc';
        if (currentOrder.length) {
            sortDir = currentOrder[0][1];
            sortKey = visualKeys[currentOrder[0][0]] || null;
        }

        return {
            pageLength: table.page.len(),
            order: currentOrder,
            sortKey,
            sortDir,
            columnOrder,
            columnWidths,
        };
    }

    function scheduleSave(ctx) {
        clearTimeout(ctx.saveTimer);
        ctx.saveTimer = setTimeout(function () {
            const payload = collectPreferences(ctx.table, ctx.keyByColumnIndex);
            Storage.savePreferences(ctx.apiBaseUrl, ctx.pageKey, payload, ctx.storageMode).catch(function () {});
        }, SAVE_DELAY_MS);
    }

    function isThResizeZone(th, clientX) {
        const rect = th.getBoundingClientRect();
        return clientX >= rect.right - 14;
    }

    function setColReorderEnabled(table, enabled) {
        if (!table.colReorder) {
            return;
        }
        if (enabled && typeof table.colReorder.enable === 'function') {
            table.colReorder.enable(true);
        } else if (!enabled && typeof table.colReorder.disable === 'function') {
            table.colReorder.disable();
        }
    }

    function startColumnResize(table, ctx, $th, startX) {
        setColReorderEnabled(table, false);
        ctx.resizing = {
            $th: $th,
            startX: startX,
            startWidth: $th.outerWidth(),
        };
        $('body').addClass('dt-col-resizing');
    }

    function endColumnResize(table, ctx) {
        if (!ctx.resizing) {
            return;
        }
        ctx.resizing = null;
        $('body').removeClass('dt-col-resizing');
        setColReorderEnabled(table, true);
        scheduleSave(ctx);
    }

    function enableColumnResize(table, ctx) {
        const $table = $(table.table().node());
        const $header = $table.find('thead tr').first();

        $header.find('th').each(function () {
            const $th = $(this);
            const thEl = this;
            $th.addClass('dt-col-resizable');
            if (!$th.find('.dt-col-resize-handle').length) {
                $th.append('<span class="dt-col-resize-handle" aria-hidden="true"></span>');
            }
            if (thEl._listPrefsResizeCapture) {
                return;
            }
            thEl._listPrefsResizeCapture = true;
            thEl.addEventListener('mousedown', function (e) {
                if (e.which !== 1) {
                    return;
                }
                if (!isThResizeZone(thEl, e.clientX)) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                startColumnResize(table, ctx, $th, e.pageX);
            }, true);
        });

        $(document).off('mousemove.dtColResize mouseup.dtColResize');

        $(document).on('mousemove.dtColResize', function (e) {
            if (!ctx.resizing) {
                return;
            }
            e.preventDefault();
            const delta = e.pageX - ctx.resizing.startX;
            const newWidth = Math.max(48, ctx.resizing.startWidth + delta);
            const px = `${newWidth}px`;
            ctx.resizing.$th.css({ width: px, minWidth: px, maxWidth: px });
        });

        $(document).on('mouseup.dtColResize', function () {
            endColumnResize(table, ctx);
        });
    }

    function bindEvents(table, ctx) {
        table.on('order.dt length.dt column-reorder', function () {
            scheduleSave(ctx);
        });
    }

    function resolveStorageMode(options) {
        if (options.storageMode === Storage.MODES.LOCAL || options.storageMode === Storage.MODES.SERVER) {
            return options.storageMode;
        }

        return Storage.getStorageMode(options.defaultStorageMode || Storage.MODES.SERVER);
    }

  /**
   * @param {object} options
   * @param {string} options.pageKey
   * @param {string} [options.apiBaseUrl]
   * @param {jQuery} options.tableElement
   * @param {Array} options.columnDefs
   * @param {object} options.dataTableOptions
   * @param {boolean} [options.showResetButton]
   * @param {string} [options.storageMode] - 'server' | 'local'，不傳則讀取切換開關的全域設定
   * @param {string} [options.defaultStorageMode] - 首次使用預設，預設 server
   * @param {boolean} [options.showStorageToggle] - 是否綁定切換開關
   * @param {string} [options.storageToggleSelector]
   */
    async function init(options) {
        if (!Storage) {
            throw new Error('BackendListPreferencesStorage is required');
        }

        const pageKey = options.pageKey;
        const apiBaseUrl = options.apiBaseUrl || '';
        const storageMode = resolveStorageMode(options);
        const $tableEl = options.tableElement;
        const originalColumnDefs = options.columnDefs.slice();
        const defaultKeys = originalColumnDefs.map(getColumnKey);

        if (options.showStorageToggle) {
            Storage.bindStorageToggle({
                toggleSelector: options.storageToggleSelector,
                storageMode: storageMode,
                defaultStorageMode: options.defaultStorageMode,
            });
        }

        if ($.fn.DataTable.isDataTable($tableEl[0])) {
            $tableEl.DataTable().destroy();
        }

        const prefs = await Storage.loadPreferences(apiBaseUrl, pageKey, storageMode);
        const mergedOrder = mergeColumnOrder(defaultKeys, prefs.columnOrder);
        let columns = reorderColumns(originalColumnDefs, mergedOrder);
        columns = applyWidths(columns, prefs.columnWidths);
        const defaultSortOrder = options.dataTableOptions.order || [];

        const dtOptions = Object.assign({}, options.dataTableOptions, {
            columns,
            colReorder: options.dataTableOptions.colReorder !== false,
            ordering: options.dataTableOptions.ordering !== false,
            searching: options.dataTableOptions.searching !== false,
            pageLength: prefs.pageLength || options.dataTableOptions.pageLength || 10,
            order: resolveSortOrder(prefs, columns, defaultSortOrder),
        });

        const table = $tableEl.DataTable(dtOptions);

        const keyByColumnIndex = columns.map((col, colIdx) => getColumnKey(col, colIdx));

        const ctx = {
            pageKey,
            apiBaseUrl,
            storageMode,
            table,
            keyByColumnIndex,
            saveTimer: null,
            resetInProgress: false,
            resizing: null,
        };

        table.on('init.dt', function () {
            enableColumnResize(table, ctx);
        });

        if (table.settings()[0]._bInitComplete) {
            enableColumnResize(table, ctx);
        }

        bindEvents(table, ctx);

        if (options.showResetButton && options.resetButtonSelector) {
            $(options.resetButtonSelector)
                .off('click.listPrefsReset')
                .on('click.listPrefsReset', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (ctx.resetInProgress) {
                        return;
                    }

                    const dtSettings = table.settings()[0];
                    if (dtSettings && dtSettings.oApi) {
                        dtSettings.oApi._fnProcessingDisplay(dtSettings, false);
                    }

                    Swal.fire({
                        text: '確定恢復此列表的預設欄位配置？',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '確定',
                        cancelButtonText: '取消',
                        scrollbarPadding: false,
                        heightAuto: false,
                        returnFocus: false,
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            return;
                        }
                        ctx.resetInProgress = true;
                        Storage.resetPreferences(apiBaseUrl, pageKey, storageMode)
                            .then(function () {
                                window.location.reload();
                            })
                            .catch(function () {
                                ctx.resetInProgress = false;
                                Swal.fire({
                                    text: '恢復失敗，請稍後再試',
                                    icon: 'error',
                                    scrollbarPadding: false,
                                    heightAuto: false,
                                });
                            });
                    });
                });
        }

        return table;
    }

    window.BackendListPreferences = {
        init,
        getColumnKey,
        mergeColumnOrder,
    };
})(window, jQuery);
