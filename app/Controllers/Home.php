<?php

namespace App\Controllers;

use App\Models\BookmarkModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $limit = 20;
        $query = trim((string) $this->request->getGet('q'));
        $bookmarksData = $this->getBookmarksBatch(0, $limit, $query);

        $data['js']               = ['home', 'shared/network-animation'];
        $data['css']              = ['home'];
        $data['title']            = 'Bookmarks';
        $data['bookmarks']        = $bookmarksData['bookmarks'];
        $data['hasMoreBookmarks'] = $bookmarksData['hasMore'];
        $data['bookmarkBatchSize'] = $limit;
        $data['searchQuery']      = $query;

        return view('home', $data);
    }

    public function show(string $uuid): string
    {
        $bookmarkModel = new BookmarkModel();

        $bookmark = $bookmarkModel->where('uuid', $uuid)->first();

        if ($bookmark === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Bookmark not found.');
        }

        if (! session()->get('is_admin') && ! empty($bookmark['private'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Bookmark not found.');
        }
        
        // Increment hit counter for non-admin views
        if (! session()->get('is_admin')) {
            $bookmarkModel->set('hitcounter', 'hitcounter + 1', false)
                ->where('id', $bookmark['id'])
                ->update();

            $bookmark['hitcounter'] = (int) ($bookmark['hitcounter'] ?? 0) + 1;
        }
        $referer  = (string) $this->request->getHeaderLine('Referer');
        $homeUrl  = site_url('/');

        if (str_starts_with($referer, $homeUrl)) {
            $data['backUrl']   = $homeUrl;
            $data['backLabel'] = 'Back to bookmarks';
        } else {
            $data['backUrl']   = $homeUrl;
            $data['backLabel'] = 'Back to bookmarks';
        }

        $data['js']       = ['home', 'shared/network-animation'];
        $data['css']      = ['home'];
        $data['title']    = esc($bookmark['title']);
        $data['bookmark'] = $bookmark;

        return view('bookmark', $data);
    }

    public function loadMoreBookmarks(): ResponseInterface
    {
        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit  = (int) $this->request->getGet('limit');
        $query  = trim((string) $this->request->getGet('q'));

        if ($limit <= 0) {
            $limit = 20;
        }

        if ($limit > 50) {
            $limit = 50;
        }

        $bookmarksData = $this->getBookmarksBatch($offset, $limit, $query);

        $html = view('home/partials/bookmark_items', [
            'bookmarks' => $bookmarksData['bookmarks'],
        ]);

        return $this->response->setJSON([
            'html'       => $html,
            'nextOffset' => $offset + count($bookmarksData['bookmarks']),
            'hasMore'    => $bookmarksData['hasMore'],
        ]);
    }

    /**
     * @return array{bookmarks: array<int, array<string, mixed>>, hasMore: bool}
     */
    private function getBookmarksBatch(int $offset, int $limit, string $query = ''): array
    {
        $bookmarkModel = new BookmarkModel();

        if (! session()->get('is_admin')) {
            $bookmarkModel->where('private', 0);
        }

        if ($query !== '') {
            $bookmarkModel
                ->groupStart()
                ->like('title', $query)
                ->orLike('notes', $query)
                ->orLike('tags', $query)
                ->groupEnd();
        }

        $rows = $bookmarkModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit + 1, $offset);

        $hasMore   = count($rows) > $limit;
        $bookmarks = array_slice($rows, 0, $limit);

        return [
            'bookmarks' => $bookmarks,
            'hasMore'   => $hasMore,
        ];
    }
}
