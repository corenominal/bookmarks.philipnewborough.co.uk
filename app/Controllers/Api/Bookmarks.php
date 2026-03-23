<?php

namespace App\Controllers\Api;

use App\Libraries\Markdown;
use App\Models\BookmarkModel;
use App\Models\TagModel;
use Ramsey\Uuid\Uuid;

class Bookmarks extends BaseController
{
    /**
     * POST /api/bookmarks
     * Create a new bookmark.
     */
    public function create()
    {
        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $title     = trim($json['title']);
        $url       = trim($json['url']);
        $tags      = trim($json['tags']);
        $notes     = trim($json['notes'] ?? '');
        $private   = (int) ($json['private'] ?? 0);
        $dashboard = (int) ($json['dashboard'] ?? 0);

        $favicon       = $this->getFavicon($url);
        $notesHtml     = $this->convertMarkdown($notes);
        $tagsNormalized = $this->normalizeTags($tags);
        $uuid          = Uuid::uuid4()->toString();

        $bookmarkModel = new BookmarkModel();
        $bookmarkId    = $bookmarkModel->insert([
            'uuid'       => $uuid,
            'title'      => $title,
            'title_html' => esc($title),
            'url'        => $url,
            'favicon'    => $favicon,
            'notes'      => $notes,
            'notes_html' => $notesHtml,
            'tags'       => $tagsNormalized,
            'private'    => $private,
            'dashboard'  => $dashboard,
            'hitcounter' => 0,
        ], true);

        if (! $bookmarkId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create bookmark.',
            ]);
        }

        $this->saveTags(new TagModel(), $bookmarkId, $tagsNormalized);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Bookmark created.',
            'uuid'    => $uuid,
        ]);
    }

    /**
     * PUT /api/bookmarks/{uuid}
     * Update an existing bookmark.
     */
    public function update($uuid = null)
    {
        if (empty($uuid)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'UUID is required.',
            ]);
        }

        $bookmarkModel = new BookmarkModel();
        $bookmark      = $bookmarkModel->where('uuid', $uuid)->first();

        if (! $bookmark) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Bookmark not found.',
            ]);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $title     = trim($json['title']);
        $url       = trim($json['url']);
        $tags      = trim($json['tags']);
        $notes     = trim($json['notes'] ?? '');
        $private   = (int) ($json['private'] ?? 0);
        $dashboard = (int) ($json['dashboard'] ?? 0);

        // Re-fetch the favicon if the URL has changed, otherwise keep the existing one.
        $favicon = ($url !== $bookmark['url'])
            ? $this->getFavicon($url)
            : ($bookmark['favicon'] ?: $this->getFavicon($url));

        $notesHtml      = $this->convertMarkdown($notes);
        $tagsNormalized = $this->normalizeTags($tags);

        $bookmarkModel->where('uuid', $uuid)->set([
            'title'      => $title,
            'title_html' => esc($title),
            'url'        => $url,
            'favicon'    => $favicon,
            'notes'      => $notes,
            'notes_html' => $notesHtml,
            'tags'       => $tagsNormalized,
            'private'    => $private,
            'dashboard'  => $dashboard,
        ])->update();

        $tagModel = new TagModel();
        $tagModel->where('bookmark_id', $bookmark['id'])->delete();
        $this->saveTags($tagModel, $bookmark['id'], $tagsNormalized);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Bookmark updated.',
            'uuid'    => $uuid,
        ]);
    }

    /**
     * Validate the incoming JSON payload.
     * Returns true on success, or an array of error messages.
     */
    private function validateInput(array $json): array|bool
    {
        $errors = [];
        $title  = trim($json['title'] ?? '');
        $url    = trim($json['url']   ?? '');
        $tags   = trim($json['tags']  ?? '');

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters.';
        }

        if ($url === '') {
            $errors['url'] = 'URL is required.';
        } elseif (! filter_var($url, FILTER_VALIDATE_URL)) {
            $errors['url'] = 'URL is not valid.';
        }

        if ($tags === '') {
            $errors['tags'] = 'At least one tag is required.';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Build the Google S2 favicon URL for the given bookmark URL.
     */
    private function getFavicon(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return '';
        }

        return 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=32';
    }

    /**
     * Convert Markdown text to HTML via the Markdown library.
     * Returns an empty string if notes are empty or conversion fails.
     */
    private function convertMarkdown(string $notes): string
    {
        if ($notes === '') {
            return '';
        }

        try {
            $markdown = new Markdown();
            $markdown->setMarkdown($notes);
            $result = $markdown->convert();

            return $result['html'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Normalise a comma-separated tag string.
     */
    private function normalizeTags(string $tags): string
    {
        $items = array_filter(array_map('trim', explode(',', $tags)));

        return implode(', ', $items);
    }

    /**
     * Insert tag rows for the given bookmark.
     */
    private function saveTags(TagModel $tagModel, int $bookmarkId, string $tagsString): void
    {
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));

        foreach ($tags as $tag) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $tag), '-'));
            $tagModel->skipValidation(true)->insert([
                'bookmark_id' => $bookmarkId,
                'tag'         => $tag,
                'slug'        => $slug,
            ]);
        }
    }
}
