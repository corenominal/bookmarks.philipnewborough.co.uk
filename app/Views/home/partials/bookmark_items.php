<?php if ($bookmarks !== []): ?>
    <?php foreach ($bookmarks as $bookmark): ?>
        <article class="bookmarks__item" data-bookmark-id="<?= (int) $bookmark['id'] ?>">

            <?php if (! empty($bookmark['image'])): ?>
                <div class="bookmarks__image-wrap">
                    <?php if (! empty($bookmark['url'])): ?>
                        <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer">
                    <?php endif; ?>

                    <img
                        class="bookmarks__image"
                        src="/media/<?= esc($bookmark['image']) ?>"
                        alt="<?= esc($bookmark['title']) ?>"
                        loading="lazy"
                        decoding="async"
                    >

                    <?php if (! empty($bookmark['url'])): ?>
                        </a>
                    <?php endif; ?>
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
                    <h2 class="bookmarks__title">
                        <a class="bookmarks__title-link" href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer">
                            <?= esc($bookmark['title']) ?>
                        </a>
                    </h2>
                </header>

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
                    <a class="bookmarks__permalink" href="/bookmark/<?= esc($bookmark['uuid']) ?>"><?= esc(date('j M Y', strtotime((string) ($bookmark['created_at'] ?? 'now')))) ?></a>
                </time>
                <?php if (session()->get('is_admin') && ! empty($bookmark['private'])): ?>
                    <span class="bookmarks__private-badge badge text-bg-secondary">Private</span>
                <?php endif; ?>
            </footer>

        </article>
    <?php endforeach; ?>
<?php else: ?>
    <div class="bookmarks__empty-state">No bookmarks found.</div>
<?php endif; ?>
