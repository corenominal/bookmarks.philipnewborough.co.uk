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

        $favicon        = $this->getFavicon($url);
        $notesHtml      = $this->convertMarkdown($notes);
        $tagsNormalized = $this->normalizeTags($tags);
        $uuid           = Uuid::uuid4()->toString();

        $image = $this->hasInspirationTag($tagsNormalized)
            ? $this->captureScreenshot($url, $uuid)
            : '';

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
            'image'      => $image,
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

        // Capture a screenshot when the "inspiration" tag is present and no image exists yet,
        // or when the URL has changed (re-capture with the new URL).
        $existingImage = $bookmark['image'] ?? '';
        if ($this->hasInspirationTag($tagsNormalized) && (empty($existingImage) || $url !== $bookmark['url'])) {
            $newImage = $this->captureScreenshot($url, $uuid);
            if ($newImage !== '') {
                $existingImage = $newImage;
            }
        }

        $bookmarkModel->where('uuid', $uuid)->set([
            'title'      => $title,
            'title_html' => esc($title),
            'url'        => $url,
            'favicon'    => $favicon,
            'notes'      => $notes,
            'notes_html' => $notesHtml,
            'tags'       => $tagsNormalized,
            'image'      => $existingImage,
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

    /**
     * Return true when the normalised tag string contains the "inspiration" tag.
     */
    private function hasInspirationTag(string $tagsNormalized): bool
    {
        $tags = array_map('trim', explode(',', strtolower($tagsNormalized)));

        return in_array('inspiration', $tags, true);
    }

    /**
     * Capture a 1280×720 screenshot via the ScreenshotOne API, save it to
     * public/media/{uuid}.jpg, and return the filename.
     * Returns an empty string on any failure.
     */
    private function captureScreenshot(string $url, string $uuid): string
    {
        $config = config('ScreenshotOne');

        if (empty($config->apikey)) {
            return '';
        }

        $params = [
            'access_key'      => $config->apikey,
            'url'             => $url,
            'viewport_width'  => '1280',
            'viewport_height' => '720',
            'block_ads'       => 'true',
            'dark_mode'       => 'true',
            'format'          => 'jpg',
        ];

        if (! empty($config->secretkey)) {
            ksort($params);
            $queryString = http_build_query($params);
            $signature   = hash_hmac('sha256', $queryString, $config->secretkey);
            $apiUrl      = 'https://api.screenshotone.com/take?' . $queryString . '&signature=' . $signature;
        } else {
            $apiUrl = 'https://api.screenshotone.com/take?' . http_build_query($params);
        }

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Accept: image/jpeg, image/*'],
        ]);

        $imageData  = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '' || $statusCode !== 200 || strlen($imageData) < 100) {
            return '';
        }

        $filename = $uuid . '.jpg';
        $destPath = FCPATH . 'media' . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($destPath, $imageData) === false) {
            return '';
        }

        return $filename;
    }
}
