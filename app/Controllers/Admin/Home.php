<?php

namespace App\Controllers\Admin;

use Hermawan\DataTables\DataTable;
use App\Models\BookmarkModel;

class Home extends BaseController
{
    /**
     * Display the Admin Dashboard page.
     *
     * @return string Rendered admin dashboard view output.
     */
    public function index()
    {
        $viewRow = (new BookmarkModel())->selectSum('hitcounter', 'total')->first();

        $data['stats'] = [
            'total'   => (new BookmarkModel())->countAllResults(),
            'public'  => (new BookmarkModel())->where('private', 0)->countAllResults(),
            'private' => (new BookmarkModel())->where('private', 1)->countAllResults(),
            'views'   => isset($viewRow['total']) ? (int) $viewRow['total'] : 0,
        ];

        $data['datatables'] = true;
        $data['js']         = ['admin/home'];
        $data['css']        = ['admin/home'];
        $data['title']      = 'Admin Dashboard';

        return view('admin/home', $data);
    }

    /**
     * Server-side DataTables endpoint for the bookmarks table.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response for DataTables.
     */
    public function datatable()
    {
        $model   = new BookmarkModel();
        $builder = $model->builder()
            ->select('id, uuid, title, favicon, url, tags, private, created_at')
            ->where('deleted_at IS NULL');

        $visibilityFilter = $this->request->getGet('visibility_filter');
        if ($visibilityFilter === 'public') {
            $builder->where('private', 0);
        } elseif ($visibilityFilter === 'private') {
            $builder->where('private', 1);
        }

        return DataTable::of($builder)
            ->edit('private', function ($row) {
                return $row->private
                    ? '<span class="badge text-bg-warning">Private</span>'
                    : '<span class="badge text-bg-success">Public</span>';
            })
            ->escape('private', false)
            ->toJson(true);
    }

    /**
     * Delete selected bookmarks (soft delete).
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function delete()
    {
        $json = $this->request->getJSON(true);
        $ids  = $json['ids'] ?? [];

        // Sanitise: keep only positive integers
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        $model = new BookmarkModel();
        $model->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
