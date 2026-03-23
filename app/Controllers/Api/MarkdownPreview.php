<?php

namespace App\Controllers\Api;

use App\Libraries\Markdown;

class MarkdownPreview extends BaseController
{
    /**
     * POST /api/markdown/preview
     * Convert a Markdown string to HTML.
     */
    public function convert()
    {
        $json     = $this->request->getJSON(true);
        $markdown = trim($json['markdown'] ?? '');

        if ($markdown === '') {
            return $this->response->setJSON(['html' => '']);
        }

        try {
            $lib = new Markdown();
            $lib->setMarkdown($markdown);
            $result = $lib->convert();
            $html   = $result['html'] ?? '';
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Markdown conversion failed.',
            ]);
        }

        return $this->response->setJSON(['html' => $html]);
    }
}
