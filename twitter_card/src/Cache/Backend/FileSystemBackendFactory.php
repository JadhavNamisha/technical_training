<?php

namespace Drupal\twitter_card\Cache\Backend;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;

/**
 * Factory for creating FileSystemBackend cache backends.
 */
class FileSystemBackendFactory implements CacheFactoryInterface {

  /**
   * The service for interacting with the file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The environment specific settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * Constructs a FileSystemBackendFactory object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The service for interacting with the file system.
   * @param \Drupal\Core\Site\Settings $settings
   *   The environment specific settings.
   */
  public function __construct(FileSystemInterface $fileSystem, Settings $settings) {
    $this->fileSystem = $fileSystem;
    $this->settings = $settings;
  }

  /**
   * Returns the FileSystemBackend for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\twitter_card\Cache\Backend\FileSystemBackend
   *   The cache backend object for the specified cache bin.
   *
   * @throws \Exception
   *   Thrown when no path has been configured to store the files for the given
   *   bin.
   */
  public function get($bin): FileSystemBackend {
    $path = $this->getPathForBin($bin);
    return new FileSystemBackend($this->fileSystem, $path);
  }

  /**
   * Returns the path for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which to return the path.
   *
   * @return string
   *   The path or URI to the folder where the cache files for the given bin
   *   will be stored.
   *
   * @throws \Exception
   *   Thrown when no path has been configured.
   */
  protected function getPathForBin(string $bin): string {
    $settings = $this->settings->get('filecache');
    // Look for a cache bin specific setting.
    if (isset($settings['directory']['bins'][$bin])) {
      $path = rtrim($settings['directory']['bins'][$bin], '/') . '/';
    }
    // Fall back to the default path.
    elseif (isset($settings['directory']['default'])) {
      $path = rtrim($settings['directory']['default'], '/') . '/' . $bin . '/';
    } else {
      throw new \Exception('No path has been configured for the file system cache backend.');
    }
    return $path;
  }
}
