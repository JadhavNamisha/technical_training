<?php

namespace Drupal\twitter_card\Cache\Backend;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * A cache backend that stores cache items as files on the file system.
 */
class FileSystemBackend implements CacheBackendInterface {

  /**
   * The service for interacting with the file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The path or stream wrapper URI to the cache files folder.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructs a FileBackend cache backend.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The service for interacting with the file system.
   * @param string $path
   *   The path or stream wrapper URI to the folder where the cache files are
   *   stored.
   */
  public function __construct(FileSystemInterface $fileSystem, $path) {
    $this->fileSystem = $fileSystem;
    $this->path = rtrim($path, '/') . '/';
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $filename = $this->getFilename($cid);
    if ($item = $this->getFile($filename)) {
      $item = $this->prepareItem($item, $allow_invalid);
      if (!empty($item)) {
        return $item;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {

  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []): void {
    $this->ensureCacheFolderExists();

    $filename = $this->getFilename($cid);

    // Validate cache tags and remove duplicates.
    assert(Inspector::assertAllStrings($tags), 'Cache Tags must be strings.');
    $tags = array_unique($tags);
    sort($tags);

    $item = (object) [
      'cid' => $cid,
      'data' => $data,
      'expire' => $expire,
      'tags' => $tags,
    ];

    if (file_put_contents($filename, serialize($item)) === FALSE) {
      throw new \Exception('Cache entry could not be created.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {

  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {

  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {

  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {

  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {

  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {

  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {

  }

  /**
   * Normalizes a cache ID in order to comply with file naming limitations.
   *
   * There are many different file systems in use on web servers. In order to
   * maximize compatibility we will use filenames that only include alphanumeric
   * characters, hyphens and underscores with a max length of 255 characters.
   *
   * @param string $cid
   *   The passed in cache ID.
   *
   * @return string
   *   An cache ID consisting of alphanumeric characters, hyphens and
   *   underscores with a maximum length of 255 characters.
   */
  protected function normalizeCid(string $cid): string {
    // Nothing to do if the ID is already valid.
    $cid_uses_valid_characters = (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $cid);
    if (strlen($cid) <= 255 && $cid_uses_valid_characters) {
      return $cid;
    }
    // Return a string that uses as much as possible of the original cache ID
    // with the hash appended.
    $hash = Crypt::hashBase64($cid);
    if (!$cid_uses_valid_characters) {
      return $hash;
    }
    return substr($cid, 0, 255 - strlen($hash)) . $hash;
  }

  /**
   * Returns the filename for the given cache ID.
   *
   * @param string $cid
   *   The cache ID.
   *
   * @return string
   *   The filename.
   */
  protected function getFilename(string $cid): string {
    return $this->path . $this->normalizeCid($cid);
  }

  /**
   * Verifies that the cache folder exists and is writable.
   *
   * @throws \Exception
   *   Thrown when the folder could not be created or is not writable.
   */
  protected function ensureCacheFolderExists(): void {
    if (!is_dir($this->path)) {
      if (!$this->fileSystem->mkdir($this->path, 0775, TRUE)) {
        throw new \Exception('Could not create cache folder ' . $this->path);
      }
    }

    if (!is_writable($this->path)) {
      throw new \Exception('Cache folder ' . $this->path . ' is not writable.');
    }
  }

  /**
   * Deletes all cache items in the bin.
   */
  protected function doDeleteAll() {

  }

  /**
   * Prepares a cache item for returning to the cache handler.
   *
   * Checks that items are either permanent or did not expire, and returns data
   * as appropriate.
   *
   * @param object $item
   *   A cache item.
   * @param bool $allow_invalid
   *   (optional) If TRUE, cache items may be returned even if they have expired
   *   or been invalidated.
   *
   * @return object|null
   *   The item with data as appropriate or NULL if there is no valid item to
   *   load.
   */
  protected function prepareItem(\stdClass $item, bool $allow_invalid) {
    if (!isset($item->data)) {
      return NULL;
    }

    if (!$allow_invalid) {
      return NULL;
    }

    return $item;
  }

  /**
   * Returns the raw, unprepared cache item from the given file.
   *
   * @param string $filename
   *   The path or stream wrapper URI of the file to load.
   *
   * @return object|null
   *   The raw, unprepared cache item or NULL if the file does not exist or does
   *   not contain a serialized cache item.
   */
  protected function getFile(string $filename) {
    if (is_file($filename)) {
      $serialized_contents = file_get_contents($filename);
      if ($serialized_contents !== FALSE) {
        $item = unserialize($serialized_contents);
        // Only return the item if it could be successfully deserialized.
        if ($item !== FALSE) {
          return $item;
        }
      }
    }
    return NULL;
  }

}
