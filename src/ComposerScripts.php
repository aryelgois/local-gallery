<?php
/**
 * This Software is part of aryelgois/local-gallery and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\LocalGallery;

use Composer\Script\Event;

/**
 * Composer scripts for command line use
 *
 * Use it with Composer's run-script
 *
 * Paths are relative to the package root
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/local-gallery
 */
class ComposerScripts
{
    /**
     * Path to storage directory
     *
     * @var string
     */
    const STORAGE = 'public/storage';

    /**
     * List of allowed file extensions
     *
     * @var string[]
     */
    const EXTENSIONS = [
        // Pictures
        'bmp',
        'jpg',
        'jpeg',
        'gif',
        'png',

        // Vectors
        // 'svg',

        // Videos
        'avi',
        'mkv',
        'mp4',
        'webm',
        'wmv',
    ];

    /**
     * Skeleton structure for each file in the index.json
     *
     * @var array
     */
    const SKELETON = [
        'dimension' => [0, 0],
        'favorite' => false,
        'location' => '',
        'mtime' => 0,
        // 'rotation' => 0,
        'size' => 0,
        'tags' => [],
        // 'thumbnail' => null,
        'time' => null,
        // 'type' => null, // picture / vector / video
    ];

    /**
     * Builds index.json for stored pictures and videos
     *
     * @param Event $event Composer run-script event
     */
    public static function index(Event $event)
    {
        $storage = self::STORAGE;
        if (!is_dir($storage) && !mkdir($storage, 0775, true)) {
            throw new \RuntimeException("Can not create '$storage' directory");
        }

        $index = [];
        $index_path = "$storage/index.json";
        if (is_file($index_path)) {
            $index = json_decode(file_get_contents($index_path), true);
        }

        $albums = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $storage,
            \FilesystemIterator::SKIP_DOTS
        ));
        $it->rewind();
        while ($it->valid()) {
            if (in_array($it->getExtension(), self::EXTENSIONS)) {
                $imageinfo = getimagesize($it->key());
                $albums[$it->getSubPath()][$it->getFileName()] = array_merge(
                    self::SKELETON,
                    [
                        'dimension' => [$imageinfo[0], $imageinfo[1]],
                        'mtime' => $it->getMTime(),
                        'size' => $it->getSize(),
                    ]
                );
                // $albums[$it->getSubPathName()] = array_merge(
                //     self::SKELETON,
                //     ['size' => $it->getSize()]
                // );
                // $it->getMTime()
            }
            $it->next();
        }

        foreach ($albums as $album => &$files) {
            $old = $index[$album] ?? [];
            foreach ($old as &$old_file) {
                unset(
                    $old_file['mtime'],
                    $old_file['dimension'],
                    $old_file['size']
                );
            }
            unset($old_file);

            $files = array_replace_recursive(
                $files,
                array_intersect_key($old, $files)
            );
            ksort($files);
        }
        unset($files);
        ksort($albums);

        // echo json_encode($albums, JSON_PRETTY_PRINT); die;
        file_put_contents($index_path, json_encode($albums, JSON_PRETTY_PRINT));
    }
}
