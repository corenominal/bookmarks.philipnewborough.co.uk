<?php

namespace App\Controllers\Admin;

use App\Models\BookmarkModel;

class Bookmarks extends BaseController
{
    public function create()
    {
        $data['title']      = 'Add Bookmark';
        $data['js']         = ['admin/bookmark-form'];
        $data['css']        = ['home', 'admin/bookmark-form'];
        $data['action']     = 'create';
        $data['bookmark']   = null;

        return view('admin/bookmark_form', $data);
    }

    public function edit($uuid = null)
    {
        $bookmarkModel = new BookmarkModel();
        $bookmark      = $bookmarkModel->where('uuid', $uuid)->first();

        if (! $bookmark) {
            return redirect()->to('/admin')->with('error', 'Bookmark not found.');
        }

        $data['title']    = 'Edit Bookmark';
        $data['js']       = ['admin/bookmark-form'];
        $data['css']      = ['home', 'admin/bookmark-form'];
        $data['action']   = 'edit';
        $data['bookmark'] = $bookmark;

        return view('admin/bookmark_form', $data);
    }
}
