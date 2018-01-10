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
        'jpg',
        'jpeg',
        'gif',
        'png',
    ];

    /**
     * Skeleton structure for each file in the index.json
     *
     * @var array
     */
    const SKELETON = [
        'tags' => [],
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

        $files = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storage)
        );
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isDot()) {
                $extension = pathinfo($it->getBasename(), PATHINFO_EXTENSION);
                if (in_array($extension, self::EXTENSIONS)) {
                    $files[$it->getSubPathName()] = self::SKELETON;
                }
            }
            $it->next();
        }

        $index = array_merge(
            $files,
            array_intersect_key($index, $files)
        );
        ksort($index);

        file_put_contents($index_path, json_encode($index, JSON_PRETTY_PRINT));
    }
}
