<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<section class="bookmarks-page container py-3">
    <div class="row g-4 justify-content-center">
        <div class="col-12 col-xl-9">

            <div class="bookmarks-page__header">
                <div class="bookmarks-page__title-row">
                    <h1 class="bookmarks-page__title mb-0">Bookmarks</h1>
                    <form class="bookmarks-page__search" method="get" action="/" role="search" aria-label="Search bookmarks">
                        <label for="bookmarks-search" class="visually-hidden">Search bookmarks</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                            <input
                                type="search"
                                id="bookmarks-search"
                                name="q"
                                class="form-control"
                                placeholder="Search bookmarks"
                                value="<?= esc($searchQuery) ?>"
                                autocomplete="off"
                            >
                        </div>
                    </form>
                </div>
            </div>

            <section class="bookmarks" aria-label="Bookmarks list">
                <div
                    class="bookmarks__items"
                    id="bookmarks-items"
                    data-load-url="/bookmarks/load"
                    data-offset="<?= count($bookmarks) ?>"
                    data-limit="<?= (int) $bookmarkBatchSize ?>"
                    data-has-more="<?= $hasMoreBookmarks ? '1' : '0' ?>"
                    data-search="<?= esc($searchQuery) ?>"
                >
                    <?= view('home/partials/bookmark_items', [
                        'bookmarks' => $bookmarks,
                    ]) ?>
                </div>

                <div id="bookmarks-loader" class="bookmarks__loader" aria-live="polite">
                    <span class="bookmarks__loader-text">Scroll to load more bookmarks</span>
                </div>

                <div id="bookmarks-observer" class="bookmarks__observer" aria-hidden="true"></div>
            </section>

        </div> <!-- /.col-12 -->
    </div> <!-- /.row -->
</section>
<?= $this->endSection() ?>
