<?php

namespace App\Controllers\Api;

class ScreenshotPreview extends BaseController
{
    /**
     * GET /api/screenshot/preview
     * Return a ScreenshotOne API URL for the given bookmark URL (admin only).
     * The URL is constructed server-side (with optional HMAC signature) so
     * the API key is never exposed to the browser.
     */
    public function url()
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        $url = trim($this->request->getGet('url') ?? '');

        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'A valid URL is required.',
            ]);
        }

        $config = config('ScreenshotOne');

        if (empty($config->apikey)) {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => 'error',
                'message' => 'Screenshot service not configured.',
            ]);
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
            $queryString   = http_build_query($params);
            $signature     = hash_hmac('sha256', $queryString, $config->secretkey);
            $screenshotUrl = 'https://api.screenshotone.com/take?' . $queryString . '&signature=' . $signature;
        } else {
            $screenshotUrl = 'https://api.screenshotone.com/take?' . http_build_query($params);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'url'    => $screenshotUrl,
        ]);
    }
}
