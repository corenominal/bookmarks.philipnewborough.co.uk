<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<section class="container py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">

            <div class="mb-3">
                <a href="<?= esc($backUrl) ?>" class="text-decoration-none text-secondary small">&larr; <?= esc($backLabel) ?></a>
            </div>

            <article class="bookmarks__item bookmarks__item--single">

                <?php if (! empty($bookmark['image'])): ?>
                    <div class="bookmarks__image-wrap">
                        <img
                            class="bookmarks__image"
                            src="/media/<?= esc($bookmark['image']) ?>"
                            alt="<?= esc($bookmark['title']) ?>"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>
                <?php endif; ?>

                <div class="bookmarks__body">
                    <header class="bookmarks__header">
                        <?php if (! empty($bookmark['favicon'])): ?>
                            <img
                                class="bookmarks__favicon"
                                src="<?= esc($bookmark['favicon']) ?>"
                                alt=""
                                width="16"
                                height="16"
                                loading="lazy"
                                decoding="async"
                                aria-hidden="true"
                            >
                        <?php endif; ?>
                        <h1 class="bookmarks__title">
                            <a class="bookmarks__title-link" href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= esc($bookmark['title']) ?>
                            </a>
                        </h1>
                    </header>

                    <?php if (! empty($bookmark['url'])): ?>
                        <p class="bookmarks__url">
                            <a class="bookmarks__url-link" href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= esc($bookmark['url']) ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <?php if (! empty($bookmark['notes_html'])): ?>
                        <div class="bookmarks__notes prose">
                            <?= $bookmark['notes_html'] ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($bookmark['tags'])): ?>
                        <div class="bookmarks__tags" aria-label="Tags">
                            <?php foreach (array_filter(array_map('trim', explode(',', $bookmark['tags']))) as $tag): ?>
                                <a class="bookmarks__tag" href="/?q=<?= urlencode($tag) ?>"><?= esc($tag) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <footer class="bookmarks__meta">
                    <time class="bookmarks__time" datetime="<?= esc((string) ($bookmark['created_at'] ?? '')) ?>">
                        <?= esc(date('j M Y, H:i', strtotime((string) ($bookmark['created_at'] ?? 'now')))) ?>
                    </time>
                    <?php if (session()->get('is_admin') && ! empty($bookmark['private'])): ?>
                        <span class="bookmarks__private-badge badge text-bg-secondary">Private</span>
                    <?php endif; ?>
                </footer>

            </article>

        </div>
    </div>
</section>
<?= $this->endSection() ?>
