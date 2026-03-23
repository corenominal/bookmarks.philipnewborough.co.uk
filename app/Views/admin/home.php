<?= $this->extend('templates/dashboard') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Page header -->
    <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-start justify-content-between gap-3">
        <div>
            <h2 class="mb-1">Dashboard</h2>
            <p class="text-muted mb-0 small">Overview and bookmark management</p>
        </div>
        <a href="/admin/bookmark/create" class="btn btn-sm btn-primary flex-shrink-0">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Bookmark
        </a>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xxl-3">
            <div class="stat-card card h-100 stat-card--bookmarks">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="stat-card__icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-bookmarks-fill"></i>
                    </div>
                    <div>
                        <div class="stat-card__value"><?= number_format($stats['total']) ?></div>
                        <div class="stat-card__label">Total Bookmarks</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <div class="stat-card card h-100 stat-card--public">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="stat-card__icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <div>
                        <div class="stat-card__value"><?= number_format($stats['public']) ?></div>
                        <div class="stat-card__label">Public</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <div class="stat-card card h-100 stat-card--private">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="stat-card__icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-card__value"><?= number_format($stats['private']) ?></div>
                        <div class="stat-card__label">Private</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <div class="stat-card card h-100 stat-card--views">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="stat-card__icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <div>
                        <?php
                        $v = $stats['views'];
                        if ($v >= 1_000_000) {
                            $viewsDisplay = round($v / 1_000_000, 1) . 'm';
                        } elseif ($v >= 1_000) {
                            $viewsDisplay = round($v / 1_000, 1) . 'k';
                        } else {
                            $viewsDisplay = (string) $v;
                        }
                        // Strip trailing .0 (e.g. 2.0k → 2k)
                        $viewsDisplay = preg_replace('/\.0([km])$/', '$1', $viewsDisplay);
                        ?>
                        <div class="stat-card__value" title="<?= number_format($stats['views']) ?>"><?= $viewsDisplay ?></div>
                        <div class="stat-card__label">Total Views</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookmarks table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-3 py-3">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-bookmarks me-2 text-muted"></i>Bookmarks</h5>
                    <div class="d-flex gap-2" role="group" aria-label="Table actions">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="btn-visibility-filter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel-fill"></i><span class="d-none d-lg-inline"> Visibility: All</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btn-visibility-filter">
                                <li><a class="dropdown-item visibility-filter-item active" href="#" data-value="">All</a></li>
                                <li><a class="dropdown-item visibility-filter-item" href="#" data-value="public">Public</a></li>
                                <li><a class="dropdown-item visibility-filter-item" href="#" data-value="private">Private</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-datatable-refresh" title="Refresh table">
                            <i class="bi bi-arrow-clockwise"></i><span class="d-none d-lg-inline"> Refresh</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete" disabled title="Delete selected">
                            <i class="bi bi-trash3-fill"></i><span class="d-none d-lg-inline"> Delete</span>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 pb-2">
                    <div class="table-responsive">
                        <table id="bookmarks-table" class="table table-hover table-striped table-bordered align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>URL</th>
                                    <th>Tags</th>
                                    <th>Visibility</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="modal-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-confirm-label">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="delete-modal-count">0</strong> bookmark(s)? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>