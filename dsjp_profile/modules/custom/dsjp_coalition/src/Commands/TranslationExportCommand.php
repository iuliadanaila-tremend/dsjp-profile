<?php

namespace Drupal\dsjp_coalition\Commands;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Config\LanguageConfigOverride;
use Drush\Commands\DrushCommands;

/**
 * Provides a drush command for exporting select lists translations.
 */
class TranslationExportCommand extends DrushCommands {

  /**
   * The language manager service property.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * TranslationExportCommand constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(LanguageManagerInterface $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * Drush command that displays the given text.
   *
   * @param string $language
   *   Argument with language to be exported.
   *
   * @command dsjp_coalition:translation-export
   * @aliases translation-export
   * @usage dsjp_coalition:translation-export 'es'
   */
  public function exportTranslation($language) {
    $fields = [
      'field_dsj_assessment_type',
      'field_dsj_credential_offered',
      'field_dsj_digital_skill_level',
      'field_dsj_digital_technology',
      'field_dsj_effort',
      'field_dsj_event_setting',
      'field_dsj_learning_activity',
      'field_dsj_organization_category',
      'field_dsj_publication_type',
      'field_dsj_skills_resource_type',
      'field_dsj_target_audience',
      'field_dsj_target_group',
      'field_dsj_target_language',
      'field_dsj_training_duration',
      'field_dsj_type_of_funding',
      'field_dsj_type_of_initiative',
      'field_dsj_type_of_training',
      'field_dsj_typology_of_training',
    ];
    $file = fopen('translation_' . $language . '.csv', 'w');
    // Add the header.
    fputcsv($file, ['Original', 'Translation']);
    foreach ($fields as $field_name) {
      // Get the translation config.
      $translation_config = $this->languageManager->getLanguageConfigOverride($language, 'field.storage.node.' . $field_name);
      if ($translation_config instanceof LanguageConfigOverride) {
        $data = $translation_config->get();
        if (isset($data['settings']['allowed_values'])) {
          $translated_values = $data['settings']['allowed_values'];
          // Get the original config.
          $base_config = FieldStorageConfig::loadByName('node', $field_name);
          if ($base_config instanceof FieldStorageConfig) {
            // Add the field name row.
            fputcsv($file, [$base_config->get('field_name')]);
            $original_values = $base_config->getSetting('allowed_values');
            $index = 0;
            foreach ($original_values as $label) {
              // In some cases, no translation is provided(5G, WiFi), so we use
              // the original label.
              $translated_label = $translated_values[$index]['label'] ?? $label;
              fputcsv($file, [$label, $translated_label]);
              $index++;
            }
          }
        }
      }
    }
    fclose($file);
  }

}
