/**
 * 表單明細表格欄位個人化（非 DataTables）：可見欄寬、欄序，固定 # 欄。
 * 儲存方式：帳號同步（API／DB）或本機暫存（localStorage），可切換。
 */
(function (window, $) {
    'use strict';

    const SAVE_DELAY_MS = 600;
    const Storage = window.BackendListPreferencesStorage;

    function mergeColumnOrder(defaultKeys, savedOrder) {
        const result = [];
        const seen = new Set();

        (savedOrder || []).forEach(function (key) {
            if (defaultKeys.includes(key) && !seen.has(key)) {
                result.push(key);
                seen.add(key);
            }
        });

        defaultKeys.forEach(function (key) {
            if (!seen.has(key)) {
                result.push(key);
            }
        });

        return result;
    }

    function buildFullOrder(defaultFullOrder, visibleOrder, fixedKey, adjustableVisibleKeys) {
        const adjustableSet = new Set(adjustableVisibleKeys);
        const visibleQueue = visibleOrder.filter(function (k) {
            return adjustableSet.has(k);
        });
        let queueIdx = 0;
        const result = [];

        defaultFullOrder.forEach(function (key) {
            if (key === fixedKey) {
                result.push(key);
                return;
            }
            if (!adjustableSet.has(key)) {
                result.push(key);
                return;
            }
            if (queueIdx < visibleQueue.length) {
                result.push(visibleQueue[queueIdx]);
                queueIdx += 1;
            }
        });

        return result;
    }

    function tagRowCells($row, keys) {
        $row.children('th, td').each(function (index) {
            if (keys[index]) {
                $(this).attr('data-col-key', keys[index]);
            }
        });
    }

    function reorderRow($row, fullOrder) {
        const $cells = $row.children('th, td');
        const map = {};
        $cells.each(function () {
            const key = $(this).attr('data-col-key');
            if (key) {
                map[key] = this;
            }
        });

        fullOrder.forEach(function (key) {
            if (map[key]) {
                $row.append(map[key]);
            }
        });
    }

    function applyOrder(ctx) {
        const theadOrder = buildFullOrder(
            ctx.defaultTheadOrder,
            ctx.visibleOrder,
            ctx.fixedColumnKey,
            ctx.adjustableVisibleKeys
        );
        const tbodyOrder = buildFullOrder(
            ctx.defaultTbodyOrder,
            ctx.visibleOrder,
            ctx.fixedColumnKey,
            ctx.adjustableVisibleKeys
        );

        reorderRow(ctx.$theadRow, theadOrder);
        ctx.$bodyRows.each(function () {
            reorderRow($(this), tbodyOrder);
        });
        if (ctx.$templateRow && ctx.$templateRow.length) {
            reorderRow(ctx.$templateRow, tbodyOrder);
        }
    }

    function applyWidths(ctx, widths) {
        ctx.$theadRow.children('th').each(function () {
            const $th = $(this);
            const key = $th.attr('data-col-key');
            if (!key || !ctx.adjustableVisibleKeys.includes(key)) {
                return;
            }
            const width = widths ? widths[key] : null;
            if (width) {
                const px = parseInt(width, 10) + 'px';
                $th.css({ width: px, minWidth: px });
            } else {
                $th.css({ width: '', minWidth: '' });
            }
        });
    }

    function captureDefaultWidths(ctx) {
        const widths = {};
        ctx.$theadRow.children('th').each(function () {
            const key = $(this).attr('data-col-key');
            if (key && ctx.adjustableVisibleKeys.includes(key)) {
                widths[key] = Math.round($(this).outerWidth());
            }
        });
        return widths;
    }

    function collectPreferences(ctx) {
        return {
            columnOrder: ctx.visibleOrder.slice(),
            columnWidths: captureDefaultWidths(ctx),
        };
    }

    function scheduleSave(ctx) {
        clearTimeout(ctx.saveTimer);
        ctx.saveTimer = setTimeout(function () {
            Storage.savePreferences(ctx.apiBaseUrl, ctx.pageKey, collectPreferences(ctx), ctx.storageMode).catch(function () {});
        }, SAVE_DELAY_MS);
    }

    function enableColumnResize(ctx) {
        const $header = ctx.$theadRow;

        $header.children('th').each(function () {
            const $th = $(this);
            const key = $th.attr('data-col-key');
            if (!key || !ctx.adjustableVisibleKeys.includes(key)) {
                return;
            }
            $th.addClass('dt-col-resizable');
            if (!$th.find('.dt-col-resize-handle').length) {
                $th.append('<span class="dt-col-resize-handle" aria-hidden="true"></span>');
            }
        });

        let resizing = null;

        $header.on('mousedown.formColResize', '.dt-col-resize-handle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $th = $(this).closest('th');
            resizing = {
                $th: $th,
                startX: e.pageX,
                startWidth: $th.outerWidth(),
            };
            $('body').addClass('dt-col-resizing');
        });

        $(document).on('mousemove.formColResize', function (e) {
            if (!resizing) {
                return;
            }
            const delta = e.pageX - resizing.startX;
            const newWidth = Math.max(48, resizing.startWidth + delta);
            const px = newWidth + 'px';
            resizing.$th.css({ width: px, minWidth: px });
        });

        $(document).on('mouseup.formColResize', function () {
            if (!resizing) {
                return;
            }
            resizing = null;
            $('body').removeClass('dt-col-resizing');
            scheduleSave(ctx);
        });
    }

    function enableColumnReorder(ctx) {
        let dragKey = null;

        ctx.$theadRow.children('th').each(function () {
            const $th = $(this);
            const key = $th.attr('data-col-key');
            if (!key || key === ctx.fixedColumnKey || !ctx.adjustableVisibleKeys.includes(key)) {
                return;
            }
            $th.attr('draggable', 'true').addClass('form-col-draggable');
        });

        ctx.$theadRow.on('dragstart.formColReorder', 'th[draggable="true"]', function (e) {
            dragKey = $(this).attr('data-col-key');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            $(this).addClass('form-col-dragging');
        });

        ctx.$theadRow.on('dragend.formColReorder', 'th[draggable="true"]', function () {
            dragKey = null;
            $(this).removeClass('form-col-dragging');
            ctx.$theadRow.children('th').removeClass('form-col-drop-target');
        });

        ctx.$theadRow.on('dragover.formColReorder', 'th[draggable="true"]', function (e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            ctx.$theadRow.children('th').removeClass('form-col-drop-target');
            $(this).addClass('form-col-drop-target');
        });

        ctx.$theadRow.on('drop.formColReorder', 'th[draggable="true"]', function (e) {
            e.preventDefault();
            const targetKey = $(this).attr('data-col-key');
            ctx.$theadRow.children('th').removeClass('form-col-drop-target');

            if (!dragKey || !targetKey || dragKey === targetKey) {
                return;
            }
            if (!ctx.adjustableVisibleKeys.includes(dragKey) || !ctx.adjustableVisibleKeys.includes(targetKey)) {
                return;
            }

            const order = ctx.visibleOrder.slice();
            const fromIdx = order.indexOf(dragKey);
            const toIdx = order.indexOf(targetKey);
            if (fromIdx === -1 || toIdx === -1) {
                return;
            }
            order.splice(fromIdx, 1);
            order.splice(toIdx, 0, dragKey);
            ctx.visibleOrder = order;
            applyOrder(ctx);
            scheduleSave(ctx);
        });
    }

    function bindResetButton(ctx) {
        if (!ctx.resetButtonSelector) {
            return;
        }

        $(ctx.resetButtonSelector)
            .off('click.formTablePrefsReset')
            .on('click.formTablePrefsReset', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (ctx.resetInProgress) {
                    return;
                }

                Swal.fire({
                    text: '確定恢復此明細表格的預設欄位配置？',
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
                    Storage.resetPreferences(ctx.apiBaseUrl, ctx.pageKey, ctx.storageMode)
                        .then(function () {
                            ctx.visibleOrder = ctx.defaultVisibleOrder.slice();
                            applyOrder(ctx);
                            applyWidths(ctx, ctx.defaultWidths);
                        })
                        .catch(function () {
                            Swal.fire({
                                text: '恢復失敗，請稍後再試',
                                icon: 'error',
                                scrollbarPadding: false,
                                heightAuto: false,
                            });
                        })
                        .finally(function () {
                            ctx.resetInProgress = false;
                        });
                });
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
     * @param {jQuery|string} options.tableElement
     * @param {string} options.tbodySelector - e.g. '#product_area'
     * @param {string} options.templateRowSelector - e.g. '#product_template'
     * @param {string[]} options.theadKeys
     * @param {string[]} options.tbodyKeys
     * @param {string[]} options.adjustableVisibleKeys - visible columns except fixed #
     * @param {string} [options.fixedColumnKey='row_index']
     * @param {string} options.pageKey
     * @param {string} [options.apiBaseUrl]
     * @param {string} [options.resetButtonSelector]
     * @param {string} [options.storageMode]
     * @param {string} [options.defaultStorageMode]
     * @param {boolean} [options.showStorageToggle]
     * @param {string} [options.storageToggleSelector]
     */
    async function init(options) {
        if (!Storage) {
            throw new Error('BackendListPreferencesStorage is required');
        }

        const storageMode = resolveStorageMode(options);
        const apiBaseUrl = options.apiBaseUrl || '';

        if (options.showStorageToggle) {
            Storage.bindStorageToggle({
                toggleSelector: options.storageToggleSelector,
                storageMode: storageMode,
                defaultStorageMode: options.defaultStorageMode,
            });
        }

        const $table = options.tableElement instanceof $ ? options.tableElement : $(options.tableElement);
        const $theadRow = $table.find('thead tr').first();
        const $bodyRows = $(options.tbodySelector).children('tr');
        const $templateRow = $(options.templateRowSelector);
        const fixedColumnKey = options.fixedColumnKey || 'row_index';
        const adjustableVisibleKeys = options.adjustableVisibleKeys.slice();

        tagRowCells($theadRow, options.theadKeys);
        $bodyRows.each(function () {
            tagRowCells($(this), options.tbodyKeys);
        });
        if ($templateRow.length) {
            tagRowCells($templateRow, options.tbodyKeys);
        }

        const defaultTheadOrder = options.theadKeys.slice();
        const defaultTbodyOrder = options.tbodyKeys.slice();
        const defaultVisibleOrder = adjustableVisibleKeys.slice();
        const defaultWidths = captureDefaultWidths({
            $theadRow: $theadRow,
            adjustableVisibleKeys: adjustableVisibleKeys,
        });

        const prefs = await Storage.loadPreferences(apiBaseUrl, options.pageKey, storageMode);
        const visibleOrder = mergeColumnOrder(defaultVisibleOrder, prefs.columnOrder);

        const ctx = {
            $table: $table,
            $theadRow: $theadRow,
            $bodyRows: $bodyRows,
            $templateRow: $templateRow,
            defaultTheadOrder: defaultTheadOrder,
            defaultTbodyOrder: defaultTbodyOrder,
            defaultVisibleOrder: defaultVisibleOrder,
            defaultWidths: defaultWidths,
            adjustableVisibleKeys: adjustableVisibleKeys,
            fixedColumnKey: fixedColumnKey,
            visibleOrder: visibleOrder,
            pageKey: options.pageKey,
            apiBaseUrl: apiBaseUrl,
            storageMode: storageMode,
            resetButtonSelector: options.resetButtonSelector,
            saveTimer: null,
            resetInProgress: false,
        };

        applyOrder(ctx);
        applyWidths(ctx, prefs.columnWidths || {});

        enableColumnResize(ctx);
        enableColumnReorder(ctx);
        bindResetButton(ctx);

        ctx.refreshBodyRows = function () {
            const tbodyOrder = buildFullOrder(
                ctx.defaultTbodyOrder,
                ctx.visibleOrder,
                ctx.fixedColumnKey,
                ctx.adjustableVisibleKeys
            );
            $(options.tbodySelector).children('tr').each(function () {
                const $row = $(this);
                if (!$row.children('td[data-col-key]').length) {
                    tagRowCells($row, options.tbodyKeys);
                }
                reorderRow($row, tbodyOrder);
            });
            ctx.$bodyRows = $(options.tbodySelector).children('tr');
        };

        return ctx;
    }

    window.BackendFormTablePreferences = {
        init: init,
        mergeColumnOrder: mergeColumnOrder,
    };
})(window, jQuery);
