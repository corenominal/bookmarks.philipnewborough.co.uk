<?php

namespace App\Commands;

use App\Models\BookmarkModel;
use App\Models\TagModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Imports bookmarks and their tags from a JSON export file.
 *
 * Usage:
 * php spark bookmarks:import [<path-to-json-file>] [--truncate]
 *
 * If no file is provided, the first JSON file found in the imports/ directory
 * is used. GUIDs in the export are mapped to UUIDs for the bookmarks table.
 * If a bookmark has an image URL, the image is downloaded to public/media and
 * renamed to {uuid}.{ext}.
 *
 * Options:
 * --truncate  Truncate the bookmarks and tags tables and remove all downloaded
 *             images from public/media before importing.
 */
class ImportBookmarks extends BaseCommand
{
    protected $group       = 'Bookmarks';
    protected $name        = 'bookmarks:import';
    protected $description = 'Imports bookmarks and their tags from a JSON export file.';
    protected $usage       = 'bookmarks:import [<path-to-json-file>] [--truncate]';
    protected $arguments   = [
        'path-to-json-file' => 'Absolute or relative path to the JSON export file (optional).',
    ];
    protected $options = [
        '--truncate' => 'Truncate the bookmarks and tags tables and remove downloaded images before importing.',
    ];

    public function run(array $params): void
    {
        $truncate = CLI::getOption('truncate') !== null;

        if ($truncate) {
            $this->truncate();
        }

        $filePath = $params[0] ?? '';

        if (empty($filePath)) {
            $files = glob(ROOTPATH . 'imports' . DIRECTORY_SEPARATOR . '*.json');

            if (empty($files)) {
                CLI::error('No JSON file specified and none found in the imports/ directory.');
                CLI::write('Usage: php spark ' . $this->usage, 'yellow');
                return;
            }

            $filePath = $files[0];
            CLI::write('No file specified, using: ' . $filePath, 'yellow');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            CLI::error("File not found or not readable: {$filePath}");
            return;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        // Support both a bare array and a wrapped export object {"bookmarks": [...]}
        $bookmarks = isset($data['bookmarks']) ? $data['bookmarks'] : $data;

        if (!is_array($bookmarks) || empty($bookmarks)) {
            CLI::error('Invalid JSON: expected a non-empty array of bookmarks.');
            return;
        }

        $bookmarkModel = new BookmarkModel();
        $tagModel      = new TagModel();
        $mediaPath     = FCPATH . 'media' . DIRECTORY_SEPARATOR;

        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        CLI::write('Importing ' . count($bookmarks) . ' bookmarks...', 'yellow');
        CLI::newLine();

        foreach ($bookmarks as $bookmark) {
            $guid = $bookmark['guid'] ?? '';

            if (empty($guid)) {
                CLI::write('  [SKIP] Bookmark has no GUID, skipping.', 'red');
                $errors++;
                continue;
            }

            // Map GUID to UUID (lowercase)
            $uuid = strtolower($guid);

            // Skip if already imported
            if ($bookmarkModel->where('uuid', $uuid)->countAllResults() > 0) {
                CLI::write("  [SKIP] Already exists: {$uuid}", 'yellow');
                $skipped++;
                continue;
            }

            // Download image if present
            $imageFilename = '';

            if (!empty($bookmark['image'])) {
                $imageUrl = $bookmark['image'];
                $urlPath  = parse_url($imageUrl, PHP_URL_PATH);
                $ext      = $urlPath ? strtolower(pathinfo($urlPath, PATHINFO_EXTENSION)) : '';

                if (!empty($ext)) {
                    $imageFilename = $uuid . '.' . $ext;
                    $destPath      = $mediaPath . $imageFilename;
                    $imageData     = @file_get_contents($imageUrl);

                    if ($imageData !== false) {
                        file_put_contents($destPath, $imageData);
                        CLI::write("  [IMG]  Downloaded: {$imageFilename}", 'cyan');
                    } else {
                        CLI::write("  [WARN] Could not download image: {$imageUrl}", 'yellow');
                        $imageFilename = '';
                    }
                }
            }

            // Insert bookmark
            $bookmarkData = [
                'uuid'       => $uuid,
                'title'      => $bookmark['title'] ?? '',
                'title_html' => $bookmark['title_html'] ?? '',
                'url'        => $bookmark['url'] ?? '',
                'favicon'    => $bookmark['favicon'] ?? '',
                'notes'      => $bookmark['notes'] ?? '',
                'notes_html' => $bookmark['notes_html'] ?? '',
                'tags'       => $bookmark['tags'] ?? '',
                'image'      => $imageFilename,
                'private'    => (int) ($bookmark['private'] ?? 0),
                'dashboard'  => (int) ($bookmark['dashboard'] ?? 0),
                'hitcounter' => (int) ($bookmark['hitcounter'] ?? 0),
                'created_at' => $bookmark['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $bookmark['updated_at'] ?? date('Y-m-d H:i:s'),
                'deleted_at' => $bookmark['deleted_at'] ?? null,
            ];

            $bookmarkModel->skipValidation(true);
            $bookmarkId = $bookmarkModel->insert($bookmarkData, true);
            $bookmarkModel->skipValidation(false);

            if (!$bookmarkId) {
                CLI::write("  [ERR]  Failed to insert bookmark: {$uuid}", 'red');
                $errors++;
                continue;
            }

            // Insert tags
            $tagString = $bookmark['tags'] ?? '';

            if (!empty($tagString)) {
                $tags = array_filter(array_map('trim', explode(',', $tagString)));

                foreach ($tags as $tag) {
                    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $tag), '-'));

                    $tagModel->skipValidation(true)->insert([
                        'bookmark_id' => $bookmarkId,
                        'tag'         => $tag,
                        'slug'        => $slug,
                        'created_at'  => $bookmark['created_at'] ?? date('Y-m-d H:i:s'),
                        'updated_at'  => $bookmark['updated_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                    $tagModel->skipValidation(false);
                }
            }

            CLI::write("  [OK]   {$uuid} — {$bookmark['title']}", 'green');
            $imported++;
        }

        CLI::newLine();
        CLI::write('Import complete.', 'yellow');
        CLI::write('  Imported : ' . $imported, 'green');
        CLI::write('  Skipped  : ' . $skipped, 'yellow');
        CLI::write('  Errors   : ' . $errors, 'red');
    }

    private function truncate(): void
    {
        CLI::write('Truncating bookmarks and tags tables...', 'yellow');

        $db = \Config\Database::connect();
        $db->table('tags')->truncate();
        $db->table('bookmarks')->truncate();

        CLI::write('  Tables truncated.', 'green');

        $mediaPath = FCPATH . 'media' . DIRECTORY_SEPARATOR;
        $files     = glob($mediaPath . '*');

        $removed = 0;

        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                unlink($file);
                $removed++;
            }
        }

        CLI::write("  Removed {$removed} image(s) from media/.", 'green');
        CLI::newLine();
    }
}
