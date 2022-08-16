<?php

namespace Drupal\dsjp_content;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Database\Connection;

/**
 * Defines a helper class for changing field types.
 *
 * @package Drupal\dsjp_content
 */
class InstallHelper implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The databse connection service.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Helper function that changes the field type from SKOS to list.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   */
  public function changeFieldType($entity_type, $field_name) {
    $database = $this->database;
    $table = $entity_type . '__' . $field_name;
    $revisionTable = $entity_type . '_revision__' . $field_name;
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

    if (is_null($field_storage)) {
      return;
    }

    $rows = NULL;
    $revisionRows = NULL;

    if ($database->schema()->tableExists($table)) {
      // The table data to restore after the update is completed.
      $rows = $this->select($table);
      $revisionRows = $this->select($revisionTable);
    }

    $new_fields = [];

    // Use existing field config for new field.
    foreach ($field_storage->getBundles() as $bundle => $label) {
      $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $new_field = $field->toArray();
      $new_field['field_type'] = 'list_string';
      $new_field['settings'] = [];

      $new_fields[] = $new_field;
    }

    // Deleting field storage which will also delete bundles(fields).
    $new_field_storage = $field_storage->toArray();
    $new_field_storage['type'] = 'list_string';
    $new_field_storage['settings'] = [
      'allowed_values_function' => '',
      'allowed_values' => $this->buildAllowedValuesForField($field_name),
    ];
    $field_storage->delete();

    // Purge field data now to allow new field and field_storage with same name
    // to be created. You may need to increase batch size.
    field_purge_batch(100);

    // Create new field storage.
    $new_field_storage = FieldStorageConfig::create($new_field_storage);
    $new_field_storage->save();

    // Create new fields.
    foreach ($new_fields as $new_field) {
      $new_field = FieldConfig::create($new_field);
      $new_field->save();
    }

    // Restore existing data in the same table.
    if (!is_null($rows)) {
      foreach ($rows as $row) {
        $oldColumnName = $field_name . '_target_id';
        $columnName = $field_name . '_value';
        $row->{$columnName} = $row->{$oldColumnName};
        unset($row->{$oldColumnName});
        $this->insert($table, $row);
      }
    }
    if (!is_null($revisionRows)) {
      foreach ($revisionRows as $row) {
        $oldColumnName = $field_name . '_target_id';
        $columnName = $field_name . '_value';
        $row->{$columnName} = $row->{$oldColumnName};
        unset($row->{$oldColumnName});
        $this->insert($revisionTable, $row);
      }
    }
  }

  /**
   * Helper function that returns the content of a table.
   *
   * @param string $table
   *   The table name.
   *
   * @return mixed
   *   The table content.
   */
  protected function select($table) {
    $database = $this->database;
    return $database->select($table, 'n')
      ->fields('n')
      ->execute()
      ->fetchAll();
  }

  /**
   * Helper function that inserts a row in a table.
   *
   * @param string $table
   *   The table name.
   * @param object $row
   *   The row content.
   */
  protected function insert($table, $row) {
    $database = $this->database;
    $database->insert($table)
      ->fields((array) $row)
      ->execute();
  }

  /**
   * Helper function that returns the allowed values for a field.
   *
   * @param string $fieldName
   *   The field name.
   *
   * @return array|string[]
   *   A list of allowed values.
   */
  protected function buildAllowedValuesForField($fieldName) {
    $values = [];

    switch ($fieldName) {
      case 'field_dsj_target_group':
        $values = $this->buildAllowedValuesTargetGroup();
        break;

      case 'field_dsj_learning_activity':
        $values = $this->buildAllowedValuesLearningActivity();
        break;

      case 'field_dsj_effort':
        $values = $this->buildAllowedValuesForEffort();
        break;

      case 'field_dsj_typology_of_training':
        $values = $this->buildAllowedValuesForTypology();
        break;

      case 'field_dsj_credential_offered':
        $values = $this->buildAllowedValuesForCredential();
        break;

      case 'field_dsj_assessment_type':
        $values = $this->buildAllowedValuesForAssessment();
        break;

      case 'field_dsj_target_language':
        $values = $this->buildAllowedValuesForTargetLanguage();
        break;
    }

    return $values;
  }

  /**
   * Helper function that builds the allowed values for target_group.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesTargetGroup() {
    return [
      'http://data.europa.eu/snb/target-group/022beab85a' => 'High Achievers',
      'http://data.europa.eu/snb/target-group/0247b300b0' => 'Non-Native Speakers',
      'http://data.europa.eu/snb/target-group/05f11c859b' => 'Persons requiring employment retraining',
      'http://data.europa.eu/snb/target-group/1599531db0' => 'Persons in tertiary education (EQF 6)',
      'http://data.europa.eu/snb/target-group/1cfca7f767' => 'Persons who have completed primary education',
      'http://data.europa.eu/snb/target-group/46806e0244' => 'Persons in secondary/compulsory education',
      'http://data.europa.eu/snb/target-group/527a6f97eb' => 'Persons who have completed tertiary education (EQF 7)',
      'http://data.europa.eu/snb/target-group/5b1417fd15' => 'Persons in primary education',
      'http://data.europa.eu/snb/target-group/5e56931138' => 'Persons with 0-3 years work experience',
      'http://data.europa.eu/snb/target-group/808b99d38f' => 'Persons who have completed tertiary education (EQF 8)',
      'http://data.europa.eu/snb/target-group/919082b88b' => 'Persons who have completed secondary/compulsory education',
      'http://data.europa.eu/snb/target-group/acb7f20f25' => 'Persons in tertiary education (EQF 7)',
      'http://data.europa.eu/snb/target-group/b0bf4f9fe9' => 'Persons in tertiary education (EQF 8)',
      'http://data.europa.eu/snb/target-group/c900d8ecb1' => 'Migrants',
      'http://data.europa.eu/snb/target-group/cbedf28444' => 'Persons with 3-10 years work experience',
      'http://data.europa.eu/snb/target-group/d45b47b458' => 'Persons with 10+ years work experience',
      'http://data.europa.eu/snb/target-group/de9cd193ce' => 'Persons with a learning disability',
      'http://data.europa.eu/snb/target-group/e3782a33ab' => 'Native Speakers',
      'http://data.europa.eu/snb/target-group/e7627913ad' => 'Persons who have completed tertiary education (EQF 6)',
      'http://data.europa.eu/snb/target-group/fd1279fd9f' => 'Low Achievers',
    ];
  }

  /**
   * Helper function that builds the allowed values for learning_activity.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesLearningActivity() {
    return [
      'http://data.europa.eu/snb/learning-activity/3c8bd58d62' => 'lab / simulation / practice coursework',
      'http://data.europa.eu/snb/learning-activity/4357e0e681' => 'job experience',
      'http://data.europa.eu/snb/learning-activity/59eaf34fab' => 'volunteering',
      'http://data.europa.eu/snb/learning-activity/a7e556215a' => 'research',
      'http://data.europa.eu/snb/learning-activity/b660f5dcea' => 'self-motivated study',
      'http://data.europa.eu/snb/learning-activity/bf2e3a7bae' => 'e-learning coursework',
      'http://data.europa.eu/snb/learning-activity/bf5588ff84' => 'internship',
      'http://data.europa.eu/snb/learning-activity/d46a826a39' => 'apprenticeship',
      'http://data.europa.eu/snb/learning-activity/efff75e10a' => 'workshop, seminar or conference',
      'http://data.europa.eu/snb/learning-activity/fd33e234ae' => 'educational programme',
      'http://data.europa.eu/snb/learning-activity/ff436ea7c9' => 'classroom coursework',
    ];
  }

  /**
   * Helper function that builds the allowed values for effort.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesForEffort() {
    return [
      'http://data.europa.eu/snb/learning-schedule/67395e6b5a' => 'Part time light',
      'http://data.europa.eu/snb/learning-schedule/72a0ab92fa' => 'Full time',
      'http://data.europa.eu/snb/learning-schedule/f230bae523' => 'Part time intensive',
    ];
  }

  /**
   * Helper function that builds the allowed values for typology.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesForTypology() {
    return [
      'http://data.europa.eu/snb/learning-opportunity/05053c1cbe' => 'Course',
      'http://data.europa.eu/snb/learning-opportunity/0f7dac46ca' => 'Programme module',
      'http://data.europa.eu/snb/learning-opportunity/11252a5207' => 'Mentoring',
      'http://data.europa.eu/snb/learning-opportunity/17744a2647' => 'MOOC',
      'http://data.europa.eu/snb/learning-opportunity/63f9f6180c' => 'Apprenticeship',
      'http://data.europa.eu/snb/learning-opportunity/65a4cf5de2' => 'Study visit',
      'http://data.europa.eu/snb/learning-opportunity/74a4a268e8' => 'Short learning programme',
      'http://data.europa.eu/snb/learning-opportunity/77b99de990' => 'Internship',
      'http://data.europa.eu/snb/learning-opportunity/79343569f3' => 'Educational programme',
      'http://data.europa.eu/snb/learning-opportunity/7e1ac538db' => 'Class',
      'http://data.europa.eu/snb/learning-opportunity/8b965da2d4' => 'Service learning',
      'http://data.europa.eu/snb/learning-opportunity/b2434ca358' => 'Thesis',
    ];
  }

  /**
   * Helper function that builds the allowed values for credential.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesForCredential() {
    return [
      'http://data.europa.eu/snb/credential/48b514e72a' => 'Learning Activity',
      'http://data.europa.eu/snb/credential/6cf8e68c43' => 'Qualification Award',
      'http://data.europa.eu/snb/credential/6dff8a0f87' => 'Diploma Supplement',
      'http://data.europa.eu/snb/credential/bdc47cb449' => 'Learning Entitlement',
      'http://data.europa.eu/snb/credential/e34929035b' => 'Generic',
    ];
  }

  /**
   * Helper function that builds the allowed values for assessment.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesForAssessment() {
    return [
      'http://data.europa.eu/snb/learning-assessment/729f3bed4b' => 'Workbased',
      'http://data.europa.eu/snb/learning-assessment/7813801c77' => 'Project based',
      'http://data.europa.eu/snb/learning-assessment/9191af2ed9' => 'Presential',
      'http://data.europa.eu/snb/learning-assessment/920fbb3cbe' => 'Online',
      'http://data.europa.eu/snb/learning-assessment/e92d221e4d' => 'Blended',
      'http://data.europa.eu/snb/learning-assessment/ed4c557045' => 'Research-Lab based',
    ];
  }

  /**
   * Helper function that builds the allowed values for target language.
   *
   * @return string[]
   *   The array with allowed values.
   */
  protected function buildAllowedValuesForTargetLanguage() {
    return [
      'http://publications.europa.eu/resource/authority/language/BUL' => 'Bulgarian',
      'http://publications.europa.eu/resource/authority/language/HRV' => 'Croatian',
      'http://publications.europa.eu/resource/authority/language/CES' => 'Czech',
      'http://publications.europa.eu/resource/authority/language/DAN' => 'Danish',
      'http://publications.europa.eu/resource/authority/language/NLD' => 'Dutch',
      'http://publications.europa.eu/resource/authority/language/ENG' => 'English',
      'http://publications.europa.eu/resource/authority/language/EST' => 'Estonian',
      'http://publications.europa.eu/resource/authority/language/FIN' => 'Finnish',
      'http://publications.europa.eu/resource/authority/language/FRA' => 'French',
      'http://publications.europa.eu/resource/authority/language/DEU' => 'German',
      'http://publications.europa.eu/resource/authority/language/ELL' => 'Greek',
      'http://publications.europa.eu/resource/authority/language/HUN' => 'Hungarian',
      'http://publications.europa.eu/resource/authority/language/GLE' => 'Irish',
      'http://publications.europa.eu/resource/authority/language/ITA' => 'Italian',
      'http://publications.europa.eu/resource/authority/language/LAV' => 'Latvian',
      'http://publications.europa.eu/resource/authority/language/LIT' => 'Lithuanian',
      'http://publications.europa.eu/resource/authority/language/MLT' => 'Maltese',
      'http://publications.europa.eu/resource/authority/language/POL' => 'Polish',
      'http://publications.europa.eu/resource/authority/language/POR' => 'Portuguese',
      'http://publications.europa.eu/resource/authority/language/RON' => 'Romanian',
      'http://publications.europa.eu/resource/authority/language/SLK' => 'Slovak',
      'http://publications.europa.eu/resource/authority/language/SLV' => 'Slovenian',
      'http://publications.europa.eu/resource/authority/language/SPA' => 'Spanish',
      'http://publications.europa.eu/resource/authority/language/SWE' => 'Swedish',
    ];
  }

}
