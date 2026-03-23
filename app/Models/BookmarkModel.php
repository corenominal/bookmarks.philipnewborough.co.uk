<?php

namespace App\Models;

use CodeIgniter\Model;

class BookmarkModel extends Model
{
    protected $table            = 'bookmarks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'id',
        'uuid',
        'title',
        'title_html',
        'url',
        'favicon',
        'notes',
        'notes_html',
        'tags',
        'image',
        'private',
        'dashboard',
        'hitcounter',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'url'   => 'required|valid_url',
        'title' => 'required|min_length[1]|max_length[255]',
    ];
}