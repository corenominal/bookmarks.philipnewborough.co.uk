document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === '/admin') {
            link.classList.remove('text-white-50');
            link.classList.add('active');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {

    // ── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str || '')));
        return div.innerHTML;
    }

    // ── Selection state ────────────────────────────────────────────────────────
    const selectedIds = new Set();

    function updateDeleteButton() {
        document.getElementById('btn-delete').disabled = selectedIds.size === 0;
    }

    // ── Pending delete IDs (bulk or single-row) ────────────────────────────────
    let pendingDeleteIds = [];

    // ── Visibility filter state ────────────────────────────────────────────────
    let visibilityFilter = '';

    const bookmarksTable = new DataTable('#bookmarks-table', {

        // ── Layout & UI ────────────────────────────────────────────────────────
        autoWidth:      true,
        info:           true,
        lengthChange:   true,
        ordering:       true,
        paging:         true,
        searching:      true,
        orderMulti:     false,
        orderClasses:   true,
        pagingType:     'simple_numbers',
        pageLength:     25,
        lengthMenu:     [10, 25, 50, 100],

        // ── Default sort: newest first ─────────────────────────────────────────
        order: [[6, 'desc']],

        // ── Performance ────────────────────────────────────────────────────────
        deferRender:    false,
        processing:     true,
        serverSide:     true,
        stateSave:      false,

        // ── Data source ────────────────────────────────────────────────────────
        ajax: {
            url: '/admin/datatable',
            data: function(d) {
                d.visibility_filter = visibilityFilter;
            },
        },

        // ── Scroll ─────────────────────────────────────────────────────────────
        scrollX: false,

        // ── Column definitions ─────────────────────────────────────────────────
        columns: [
            {
                // Column 0 — Checkbox (row select)
                data:           null,
                title:          '<input type="checkbox" id="select-all-checkbox" class="form-check-input" aria-label="Select all rows on this page">',
                orderable:      false,
                searchable:     false,
                visible:        true,
                width:          '2rem',
                className:      'text-center',
                defaultContent: '<input type="checkbox" class="row-select form-check-input" aria-label="Select row">',
            },
            {
                // Column 1 — ID
                name:       'id',
                data:       'id',
                title:      '#',
                type:       'num',
                orderable:  true,
                searchable: false,
                visible:    false,
                width:      '3rem',
                className:  'text-end',
            },
            {
                // Column 2 — Title (with favicon)
                name:       'title',
                data:       'title',
                title:      'Title',
                type:       'string',
                orderable:  true,
                searchable: true,
                render: function(data, type, row) {
                    if (type !== 'display') return data || '';
                    const favicon = row.favicon
                        ? `<img src="${escHtml(row.favicon)}" width="16" height="16" class="bookmark-favicon me-2" loading="lazy" onerror="this.style.display='none'">`
                        : '<i class="bi bi-bookmark me-2 text-muted"></i>';
                    return favicon + escHtml(data);
                },
            },
            {
                // Column 3 — URL
                name:       'url',
                data:       'url',
                title:      'URL',
                type:       'string',
                orderable:  false,
                searchable: true,
                width:      '220px',
                render: function(data, type) {
                    if (type !== 'display' || !data) return data || '';
                    const display = data.length > 45 ? data.substring(0, 45) + '\u2026' : data;
                    return `<a href="${escHtml(data)}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">${escHtml(display)}</a>`;
                },
            },
            {
                // Column 4 — Tags
                name:       'tags',
                data:       'tags',
                title:      'Tags',
                type:       'string',
                orderable:  false,
                searchable: true,
                render: function(data, type) {
                    if (type !== 'display' || !data) return data || '';
                    return data.split(',')
                        .map(t => t.trim())
                        .filter(Boolean)
                        .map(t => `<span class="badge text-bg-secondary me-1 mb-1">${escHtml(t)}</span>`)
                        .join('');
                },
            },
            {
                // Column 5 — Visibility (server returns badge HTML)
                name:       'private',
                data:       'private',
                title:      'Visibility',
                type:       'string',
                orderable:  true,
                searchable: false,
                width:      '6.5rem',
                className:  'text-center',
            },
            {
                // Column 6 — Created
                name:       'created_at',
                data:       'created_at',
                title:      'Created',
                type:       'date',
                orderable:  true,
                searchable: false,
                width:      '7.5rem',
                render: function(data, type) {
                    if (type !== 'display' || !data) return data || '';
                    return new Date(data).toLocaleDateString('en-GB', {
                        year: 'numeric', month: 'short', day: 'numeric',
                    });
                },
            },
            {
                // Column 7 — Actions
                data:       null,
                title:      '',
                orderable:  false,
                searchable: false,
                width:      '80px',
                className:  'text-end',
                render: function(data, type, row) {
                    return `<div class="btn-group btn-group-sm" role="group">` +
                        `<a href="/admin/bookmark/${escHtml(row.uuid)}/edit" class="btn btn-outline-primary" title="Edit bookmark"><i class="bi bi-pencil-fill"></i></a>` +
                        `<button type="button" class="btn btn-outline-danger btn-row-delete" data-id="${row.id}" title="Delete bookmark"><i class="bi bi-trash3-fill"></i></button>` +
                        `</div>`;
                },
            },
        ],

        // ── Language / localisation ────────────────────────────────────────────
        language: {
            emptyTable:     'No bookmarks found',
            info:           'Showing _START_ to _END_ of _TOTAL_ bookmarks',
            infoEmpty:      'Showing 0 to 0 of 0 bookmarks',
            infoFiltered:   '(filtered from _MAX_ total bookmarks)',
            lengthMenu:     'Show _MENU_ entries',
            loadingRecords: 'Loading\u2026',
            processing:     'Processing\u2026',
            search:         'Search:',
            zeroRecords:    'No matching bookmarks found',
            paginate: {
                first:    'First',
                last:     'Last',
                next:     'Next',
                previous: 'Previous',
            },
        },

        // ── Callbacks ──────────────────────────────────────────────────────────
        drawCallback: function() {
            // Restore checkbox state and row highlight after each draw
            bookmarksTable.rows({ page: 'current' }).every(function() {
                const id       = this.data().id;
                const checkbox = this.node().querySelector('.row-select');
                const selected = selectedIds.has(id);
                if (checkbox) checkbox.checked = selected;
                this.node().classList.toggle('table-active', selected);
            });
            // Sync the select-all header checkbox
            const selectAll = document.getElementById('select-all-checkbox');
            if (selectAll) {
                const visibleIds = [];
                bookmarksTable.rows({ page: 'current' }).every(function() { visibleIds.push(this.data().id); });
                const n = visibleIds.filter(id => selectedIds.has(id)).length;
                selectAll.checked       = n > 0 && n === visibleIds.length;
                selectAll.indeterminate = n > 0 && n <  visibleIds.length;
            }
            updateDeleteButton();
        },

    });

    // ── Row checkbox clicks ────────────────────────────────────────────────────
    document.querySelector('#bookmarks-table tbody').addEventListener('change', function(e) {
        if (!e.target.classList.contains('row-select')) return;
        const row = bookmarksTable.row(e.target.closest('tr'));
        const id  = row.data().id;
        const tr  = e.target.closest('tr');
        if (e.target.checked) {
            selectedIds.add(id);
            tr.classList.add('table-active');
        } else {
            selectedIds.delete(id);
            tr.classList.remove('table-active');
        }
        const selectAll = document.getElementById('select-all-checkbox');
        if (selectAll) {
            const visibleIds = [];
            bookmarksTable.rows({ page: 'current' }).every(function() { visibleIds.push(this.data().id); });
            const n = visibleIds.filter(id => selectedIds.has(id)).length;
            selectAll.checked       = n > 0 && n === visibleIds.length;
            selectAll.indeterminate = n > 0 && n <  visibleIds.length;
        }
        updateDeleteButton();
    });

    // ── Select-all checkbox (current page) ────────────────────────────────────
    document.querySelector('#bookmarks-table thead').addEventListener('change', function(e) {
        if (e.target.id !== 'select-all-checkbox') return;
        bookmarksTable.rows({ page: 'current' }).every(function() {
            const id       = this.data().id;
            const checkbox = this.node().querySelector('.row-select');
            if (e.target.checked) {
                selectedIds.add(id);
                if (checkbox) checkbox.checked = true;
                this.node().classList.add('table-active');
            } else {
                selectedIds.delete(id);
                if (checkbox) checkbox.checked = false;
                this.node().classList.remove('table-active');
            }
        });
        updateDeleteButton();
    });

    // ── Visibility filter dropdown ─────────────────────────────────────────────
    document.querySelectorAll('.visibility-filter-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.dataset.value;
            document.querySelectorAll('.visibility-filter-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            const label = value ? (value.charAt(0).toUpperCase() + value.slice(1)) : 'All';
            document.getElementById('btn-visibility-filter').innerHTML =
                '<i class="bi bi-funnel-fill"></i><span class="d-none d-lg-inline"> Visibility: ' + label + '</span>';
            visibilityFilter = value;
            bookmarksTable.ajax.reload(null, false);
        });
    });

    // ── Refresh button ─────────────────────────────────────────────────────────
    document.getElementById('btn-datatable-refresh').addEventListener('click', function() {
        bookmarksTable.ajax.reload(null, false);
    });

    // ── Delete modal setup ─────────────────────────────────────────────────────
    const deleteModalEl = document.getElementById('modal-delete-confirm');
    const deleteModal   = new bootstrap.Modal(deleteModalEl, { focus: false });

    deleteModalEl.addEventListener('shown.bs.modal', function() {
        const closeBtn = deleteModalEl.querySelector('.btn-close');
        if (closeBtn) closeBtn.focus();
    });

    deleteModalEl.addEventListener('hide.bs.modal', function() {
        const focused = deleteModalEl.querySelector(':focus');
        if (focused) focused.blur();
        const btn = document.getElementById('btn-delete');
        if (btn && !btn.disabled) btn.focus();
    });

    // ── Bulk delete button ─────────────────────────────────────────────────────
    document.getElementById('btn-delete').addEventListener('click', function() {
        pendingDeleteIds = Array.from(selectedIds);
        document.getElementById('delete-modal-count').textContent = pendingDeleteIds.length;
        deleteModal.show();
    });

    // ── Single-row delete button ───────────────────────────────────────────────
    document.querySelector('#bookmarks-table tbody').addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-row-delete');
        if (!btn) return;
        pendingDeleteIds = [parseInt(btn.dataset.id, 10)];
        document.getElementById('delete-modal-count').textContent = '1';
        deleteModal.show();
    });

    // ── Confirm delete ─────────────────────────────────────────────────────────
    document.getElementById('btn-delete-confirm').addEventListener('click', function() {
        fetch('/admin/delete', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ ids: pendingDeleteIds }),
        })
        .then(res => res.json())
        .then(() => {
            deleteModal.hide();
            pendingDeleteIds.forEach(id => selectedIds.delete(id));
            pendingDeleteIds = [];
            updateDeleteButton();
            bookmarksTable.ajax.reload(null, false);
        })
        .catch(err => console.error('Delete failed:', err));
    });

});

