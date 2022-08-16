<?php

namespace Drupal\dsjp_sat\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'assessment_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "assessment_formatter",
 *   label = @Translation("Assessment"),
 *   field_types = {
 *     "json",
 *     "json_native"
 *   }
 * )
 */
class AssessmentFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $assessment = $items->getEntity()->get('field_dsj_assessment_id')->getString();
    if (!empty($assessment)) {
      $uid = $items->getEntity()->getParentEntity()->id();
      foreach ($items as $delta => $item) {
        $results = json_decode($item->value, TRUE);
        $data = _dsjp_sat_map_skill_data($results['resultPerSkill']);
        $url = Url::fromUserInput('/digitalskills/screen/questionnaire/generic', [
          'query' => [
            'referrer' => 'dsjp',
          ],
        ]);
        $elements[$delta] = [
          '#theme' => 'dsjp_assessment',
          '#skills' => $data,
          '#overall' => $results['overallLevel'] ?? 1,
          '#assessment_links' => [
            'download' => Link::createFromRoute($this->t('Download this report'), 'dsjp_sat.assessment_download', [
              'user' => $uid,
              'assessment_id' => $assessment,
            ])->toRenderable(),
            'retake' => Link::fromTextAndUrl($this->t('Retake this test'), $url)->toRenderable(),
          ],
        ];
      }
    }
    return $elements;
  }

}
