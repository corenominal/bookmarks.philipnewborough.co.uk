<?php

namespace App\Controllers\Api;

use App\Models\TagModel;

class Tags extends BaseController
{
    /**
     * GET /api/tags
     * Return a list of all distinct tag names, sorted alphabetically.
     */
    public function index()
    {
        $tagModel = new TagModel();

        $rows = $tagModel
            ->select('tag')
            ->distinct()
            ->orderBy('tag', 'ASC')
            ->findAll();

        $tags = array_column($rows, 'tag');

        return $this->response->setJSON([
            'status' => 'ok',
            'tags'   => $tags,
        ]);
    }
}
