<?= $this->extend('templates/dashboard') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- Page header -->
    <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-start justify-content-between gap-3">
        <div>
            <h2 class="mb-1"><?= esc($title) ?></h2>
            <p class="text-muted mb-0 small">
                <?= $action === 'create' ? 'Fill in the details below to save a new bookmark.' : 'Update the bookmark details below.' ?>
            </p>
        </div>
        <a href="/admin" class="btn btn-sm btn-outline-secondary flex-shrink-0">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Alert area -->
    <div id="form-alert" class="alert d-none mb-4" role="alert" aria-live="polite"></div>

    <!-- Form + Preview -->
    <form
        id="bookmark-form"
        novalidate
        data-action="<?= esc($action) ?>"
        data-uuid="<?= esc($bookmark['uuid'] ?? '') ?>"
        data-image="<?= esc($bookmark['image'] ?? '') ?>"
        data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>"
    >
        <div class="row g-4 align-items-start">

            <!-- Left column: form -->
            <div class="col-12 col-xl-6">

                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-bookmark-fill me-2 text-muted"></i>Bookmark Details</h5>
                    </div>
                    <div class="card-body">

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="field-title" class="form-label fw-medium">
                                Title <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="field-title"
                                name="title"
                                class="form-control"
                                value="<?= esc($bookmark['title'] ?? '') ?>"
                                maxlength="255"
                                autocomplete="off"
                                required
                            >
                            <div class="invalid-feedback" id="error-title"></div>
                        </div>

                        <!-- URL -->
                        <div class="mb-3">
                            <label for="field-url" class="form-label fw-medium">
                                URL <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="url"
                                id="field-url"
                                name="url"
                                class="form-control"
                                value="<?= esc($bookmark['url'] ?? '') ?>"
                                autocomplete="off"
                                required
                            >
                            <div class="invalid-feedback" id="error-url"></div>
                        </div>

                        <!-- Tags -->
                        <div class="mb-3">
                            <label for="field-tag-input" class="form-label fw-medium">
                                Tags <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="hidden" id="field-tags" name="tags" value="<?= esc($bookmark['tags'] ?? '') ?>">
                            <datalist id="tags-datalist"></datalist>
                            <input
                                type="text"
                                id="field-tag-input"
                                class="form-control"
                                list="tags-datalist"
                                placeholder="Type a tag and press Enter or comma"
                                autocomplete="off"
                            >
                            <div class="invalid-feedback" id="error-tags"></div>
                            <div id="tags-badge-list" class="mt-2 d-flex flex-wrap gap-1" role="list" aria-label="Selected tags"></div>
                            <div class="form-text">Press Enter or comma to add a tag. Click a badge to remove it.</div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-0">
                            <label for="field-notes" class="form-label fw-medium">Notes</label>
                            <textarea
                                id="field-notes"
                                name="notes"
                                class="form-control bookmark-form__notes"
                                rows="8"
                                placeholder="Optional notes in Markdown format"
                            ><?= esc($bookmark['notes'] ?? '') ?></textarea>
                            <div class="form-text">Supports Markdown. Converted to HTML on save.</div>
                        </div>

                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0 fw-semibold"><i class="bi bi-toggles me-2 text-muted"></i>Options</h5>
                    </div>
                    <div class="card-body">

                        <!-- Visibility -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="field-private"
                                    name="private"
                                    value="1"
                                    <?= ! empty($bookmark['private']) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="field-private">
                                    Private <span class="text-muted small">(hidden from the public listing)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Dashboard -->
                        <div class="mb-0">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="field-dashboard"
                                    name="dashboard"
                                    value="1"
                                    <?= ! empty($bookmark['dashboard']) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="field-dashboard">
                                    Show on Dashboard <span class="text-muted small">(pin to startpage)</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="/admin" class="btn btn-secondary">Cancel</a>
                    <button type="submit" id="btn-submit" class="btn btn-primary">
                        <span id="btn-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <?php if ($action === 'create'): ?>
                            <i class="bi bi-plus-circle-fill me-1"></i> Save Bookmark
                        <?php else: ?>
                            <i class="bi bi-check-circle-fill me-1"></i> Update Bookmark
                        <?php endif; ?>
                    </button>
                </div>

            </div>
            <!-- /Left column -->

            <!-- Right column: live preview -->
            <div class="col-12 col-xl-6">
                <div class="bookmark-preview-panel">
                    <p class="bookmark-preview-panel__label">
                        <i class="bi bi-eye me-1"></i> Live Preview
                    </p>

                    <!-- Placeholder shown when no title/URL entered yet -->
                    <div id="preview-placeholder" class="bookmark-preview-panel__placeholder">
                        <i class="bi bi-bookmark" aria-hidden="true"></i>
                        <p class="mt-2 mb-0">Enter a title and URL to see a preview.</p>
                    </div>

                    <!-- Preview card mirrors home/partials/bookmark_items.php -->
                    <article
                        id="bookmark-preview"
                        class="bookmarks__item bookmark-preview-panel__card d-none"
                        aria-label="Bookmark preview"
                    >
                        <div id="preview-image-wrap" class="bookmarks__image-wrap d-none">
                            <img
                                id="preview-image"
                                class="bookmarks__image"
                                src=""
                                alt=""
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <div class="bookmarks__body">
                            <header class="bookmarks__header">
                                <img
                                    id="preview-favicon"
                                    class="bookmarks__favicon d-none"
                                    src=""
                                    alt=""
                                    width="16"
                                    height="16"
                                    aria-hidden="true"
                                >
                                <h2 class="bookmarks__title">
                                    <a id="preview-title" class="bookmarks__title-link" href="#" target="_blank" rel="noopener noreferrer">
                                        Untitled
                                    </a>
                                </h2>
                            </header>
                            <div id="preview-notes" class="bookmarks__notes prose d-none"></div>
                            <div id="preview-tags" class="bookmarks__tags d-none" aria-label="Tags"></div>
                        </div>
                        <footer class="bookmarks__meta">
                            <time id="preview-date" class="bookmarks__time"></time>
                            <span id="preview-private" class="bookmarks__private-badge badge text-bg-secondary d-none">Private</span>
                        </footer>
                    </article>
                </div>
            </div>
            <!-- /Right column -->

        </div>
    </form>

</div>
<?= $this->endSection() ?>
