<?php

namespace Drupal\dsjp_coalition\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom process for string image extraction.
 *
 * @code
 * process:
 *   foo:
 *     plugin: text_image_extractor
 *     source: baz
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "text_image_extractor"
 * )
 */
class TextImageExtractor extends Get implements ContainerFactoryPluginInterface {

  /**
   * The file system property.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $instance = new static(
      $configuration,
      $pluginId,
      $pluginDefinition
    );
    $instance->fileSystem = $container->get('file_system');
    $instance->fileRepository = $container->get('file.repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (!empty($value)) {
      $constants = $row->getSourceProperty('constants');
      // Get every image src from the img tags.
      preg_match_all('/<img[^>]+src="([^">]+)"/', $value, $matches);
      if (isset($matches[1])) {
        foreach ($matches[1] as $src) {
          // Create a new file object, set is as permanent, and replace the old
          // src with the file's one.
          $file = $this->fileRepository->writeData(file_get_contents($src), $constants['file_destination'] . $this->fileSystem->basename($src));
          if ($file instanceof FileInterface) {
            $file->status = 1;
            $file->save();

            $newSrc = $file->createFileUrl(FALSE);
            $value = str_replace($src, $newSrc, $value);
          }
        }
      }
    }

    return $value;
  }

}
