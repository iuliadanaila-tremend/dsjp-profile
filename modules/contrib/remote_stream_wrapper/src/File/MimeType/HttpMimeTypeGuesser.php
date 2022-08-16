<?php

namespace Drupal\remote_stream_wrapper\File\MimeType;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\remote_stream_wrapper\HttpClientTrait;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface as LegacyMimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a remote file.
 */
class HttpMimeTypeGuesser implements MimeTypeGuesserInterface, LegacyMimeTypeGuesserInterface {

  use HttpClientTrait;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The extension guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $extensionGuesser;

  /**
   * Constructs a new HttpMimeTypeGuesser.
   *
   * In order to ensure BC, the extension guesser type hinting is removed
   * so that both the legacy
   * Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   * and new Symfony mime component interface can be injected.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param $extension_guesser
   *   The extension guesser.
   */
  public function __construct(FileSystemInterface $file_system, $extension_guesser) {
    $this->fileSystem = $file_system;
    $this->extensionGuesser = $extension_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public function guess($path) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use ::guessMimeType() instead. See https://www.drupal.org/node/3133341', E_USER_DEPRECATED);
    return $this->guessMimeType($path);
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType(string $path): ?string {
    // Ignore non-external URLs.
    if (!UrlHelper::isExternal($path)) {
      return NULL;
    }

    // Attempt to parse out the mime type if the URL contains a filename.
    if ($filename = $this->parseFileNameFromUrl($path)) {
      if (in_array('Symfony\Component\Mime\MimeTypeGuesserInterface', class_implements($this->extensionGuesser))) {
        $mimetype = $this->extensionGuesser->guessMimeType($filename);
      }
      else {
        $mimetype = $this->extensionGuesser->guess($filename);
      }

      if ($mimetype != 'application/octet-stream') {
        // Only return the guessed mime type if it found a valid match
        // instead of returning the default mime type.
        return $mimetype;
      }
    }

    try {
      $response = $this->requestTryHeadLookingForHeader($path, 'Content-Type');
      if ($response->hasHeader('Content-Type')) {
        return $response->getHeaderLine('Content-Type');
      }
    }
    catch (\Exception $exception) {
      watchdog_exception('remote_stream_wrapper', $exception);
    }

    return NULL;
  }

  /**
   * Parse a file name from a URI.
   *
   * This also requires the filename to have an extension.
   *
   * @param string $uri
   *   The URI.
   *
   * @return string|false
   *   The filename if it could be parsed from the URI, or FALSE otherwise.
   */
  public function parseFileNameFromUrl($uri) {
    // Extract the path part from the URL, ignoring query strings or fragments.
    if ($path = parse_url($uri, PHP_URL_PATH)) {
      $filename = $this->fileSystem->basename($path);
      // Filename must contain a period in order to find a valid extension.
      // If the filename does not contain an extension, then guess() will
      // always return the default 'application/octet-stream' value.
      if (strpos($filename, '.') > 0) {
        return $filename;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

}
