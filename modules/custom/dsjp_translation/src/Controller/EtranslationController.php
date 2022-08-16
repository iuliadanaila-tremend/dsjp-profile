<?php

namespace Drupal\dsjp_translation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dsjp_translation\Etranslation;
use Drupal\tmgmt\JobItemInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a controller for eTranslation service callback.
 */
class EtranslationController extends ControllerBase {

  /**
   * The etranslation service property.
   *
   * @var \Drupal\dsjp_translation\Etranslation
   */
  protected $eTranslation;

  /**
   * Etranslation constructor.
   *
   * @param \Drupal\dsjp_translation\Etranslation $eTranslation
   *   The eTranslation service.
   */
  public function __construct(Etranslation $eTranslation) {
    $this->eTranslation = $eTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dsjp_translation.etranslation')
    );
  }

  /**
   * Process the translation response.
   */
  public function callback(Request $request) {
    $translated = FALSE;
    // Gets the job item to translate.
    $externalReference = $request->get('external-reference');
    if (empty($externalReference)) {
      $this->getLogger('dsjp_translation')->error($this->t('The job item id was not send in the callback.'));
      return new JsonResponse($translated);
    }
    $jobItem = $this->entityTypeManager()->getStorage('tmgmt_job_item')->load($externalReference);
    if ($jobItem instanceof JobItemInterface) {
      $content = $request->getContent();
      if (!empty($content)) {
        $translatedContent = preg_split('/<!--delimiter-->/', base64_decode($content));
        $this->eTranslation->addTranslationData($jobItem, $translatedContent);
        // Skip review status for the job item.
        $translated = $jobItem->acceptTranslation();
        $jobItem->save();
      }
      else {
        $jobItem->addMessage($this->t('The request body is empty for job id: @job_id', [
          '@job_id' => $jobItem->id(),
        ]));
      }
    }
    else {
      $this->getLogger('dsjp_translation')->error($this->t('The job item id was not found in the database: @job_id.', [
        '@job_id' => $externalReference,
      ]));
    }

    return new JsonResponse($translated);
  }

}
