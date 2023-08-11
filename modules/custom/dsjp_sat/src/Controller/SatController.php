<?php

namespace Drupal\dsjp_sat\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;
use Drupal\message_notify\MessageNotifier;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a controller for assessment result callback.
 */
class SatController extends ControllerBase {

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   *   Date formatter service.
   */
  private $dateFormatter;

  /**
   * HttpClient service.
   *
   * @var \GuzzleHttp\Client
   *   The http client property.
   */
  private $httpClient;

  /**
   * Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $time;

  /**
   * The message notifier service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  private $messageNotifier;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Date formatter.
   * @param \GuzzleHttp\Client $httpClient
   *   The http client.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The datetime interface.
   * @param \Drupal\message_notify\MessageNotifier $messageNotifier
   *   The message notifier.
   */
  public function __construct(DateFormatter $dateFormatter, Client $httpClient, TimeInterface $time, MessageNotifier $messageNotifier) {
    $this->dateFormatter = $dateFormatter;
    $this->httpClient = $httpClient;
    $this->time = $time;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('http_client'),
      $container->get('datetime.time'),
      $container->get('message_notify.sender')
    );
  }

  /**
   * Process the assessment result.
   */
  public function assessmentSave(Request $request) {
    $return = FALSE;
    if ($request->hasSession()) {
      $session = $request->getSession();
      $uid = $session->get('uid');
      if (!empty($uid)) {
        $user = $this->entityTypeManager()->getStorage('user')->load($uid);
        if ($user instanceof UserInterface && !$user->isAnonymous()) {
          $content = $request->getContent();
          if (!empty($content)) {
            $content = json_decode($content);
            $startDate = $content->startTime;
            $date = $this->dateFormatter->format(strtotime($startDate), 'custom', 'Y-m-d\TH:i:s', 'GMT');
            $data = [
              'type' => $content->type,
              'overallLevel' => $content->overallLevel,
              'resultPerSkill' => $content->resultPerSkill,
            ];
            $assessment = [
              'type' => 'dsj_assessment',
              'field_dsj_assessment_id' => $content->id ? $content->id : $content->key,
              'field_dsj_start_date' => $date,
              'field_dsj_data' => json_encode($data),
            ];
            $paragraph = Paragraph::create($assessment);
            $paragraph->save();
            $user->get('field_dsj_assessment')->appendItem([
              'target_id' => $paragraph->id(),
              'target_revision_id' => $paragraph->getRevisionId(),
            ]);
            $user->save();

            $message = $this->entityTypeManager()->getStorage('message')->create([
              'template' => 'dsj_user_assessment_saved',
              'uid' => $user->id(),
              'field_user' => $user,
            ]);
            $message->save();

            $this->messageNotifier->send($message);

            $return = TRUE;
          }
        }
      }
    }

    return new JsonResponse($return, 201);
  }

  /**
   * Generates a new results PDF for a given assessment.
   */
  public function downloadAssessment(Request $request) {
    $uid = $this->currentUser()->id();
    $user = $this->entityTypeManager()->getStorage('user')->load($uid);
    $assessmentId = $request->get('assessment_id');
    $assessment = $this->entityTypeManager()->getStorage('paragraph')->loadByProperties([
      'field_dsj_assessment_id' => $assessmentId,
    ]);
    if (!empty($assessment)) {
      // Prepare the body to generate a new PDF for the assessment.
      $assessment = reset($assessment);
      $startDate = $assessment->get('field_dsj_start_date')->getString();
      $startDate = $this->dateFormatter->format(strtotime($startDate), 'custom', 'Y-m-d\TH:i:s.v\Z', 'GMT');
      $data = $assessment->get('field_dsj_data')->getString();
      $data = json_decode($data, TRUE);
      $firstname = $user->get('field_dsj_firstname')->getString();
      $lastname = $user->get('field_dsj_lastname')->getString();
      $requestBody = [
        'name' => $firstname . ' ' . $lastname,
        'assessment' => [
          'questionnaireId' => $assessmentId,
          'downloadStatus' => 1,
          'overallLevel' => $data['overallLevel'],
          'questionnaireOptions' => [
            'accessibilityMode' => FALSE,
          ],
          'competenceLevels' => [
            [
              'competence' => 1,
              'level' => $data['resultPerSkill'][1],
            ],
            [
              'competence' => 2,
              'level' => $data['resultPerSkill'][2],
            ],
            [
              'competence' => 3,
              'level' => $data['resultPerSkill'][3],
            ],
            [
              'competence' => 4,
              'level' => $data['resultPerSkill'][4],
            ],
            [
              'competence' => 5,
              'level' => $data['resultPerSkill'][5],
            ],
          ],
          'startTime' => $startDate,
        ],
      ];

      // First request is to generate the PDF file.
      try {
        $generatePdfRequest = $this->httpClient->request('post', $request->getSchemeAndHttpHost() . '/digitalskills/api/summary/pdf', [
          'headers' => [
            'Content-Type' => 'application/json',
          ],
          'body' => json_encode($requestBody),
        ]);
        if ($generatePdfRequest->getStatusCode() == 202) {
          // If the previous request was a success, the pdf id will be returned
          // and used to get the file.
          $pdfId = $generatePdfRequest->getBody()->getContents();
          $getPdf = $this->httpClient->request('get', $request->getSchemeAndHttpHost() . '/digitalskills/api/summary/pdf/' . $pdfId, [
            'headers' => [
              'Content-Type' => 'application/pdf',
            ],
          ]);
          if (($getPdf->getStatusCode() == 200)) {
            // When we have the pdf contents, return a new response to the user.
            $file = $getPdf->getBody()->getContents();
            $response = new Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContent($file);

            return $response;
          }
          else {
            $this->getLogger('dsjp_sat')->error($this->t('Error @error when generating the PDF for @assessment', [
              '@error' => $getPdf->getStatusCode(),
              '@assessment' => $request->get('assessment_id'),
            ]));
          }
        }
        else {
          $this->getLogger('dsjp_sat')->error($this->t('Error @error when requesting the PDF for @assessment', [
            '@error' => $generatePdfRequest->getStatusCode(),
            '@assessment' => $request->get('assessment_id'),
          ]));
        }
      }
      catch (GuzzleException $e) {
        $this->getLogger('dsjp_sat')->error($e->getMessage());
      }
    }

    // If something is crashing, show an error message and return the user to
    // the listing page.
    $this->messenger()->addError($this->t('Error generating the PDF. Please contact the administrators if the problem persists.'));

    return new RedirectResponse(Url::fromRoute('view.dsj_assessments.page_1', [
      'user' => $request->get('user'),
    ])->toString());
  }

  /**
   * Custom callback for sat page redirects.
   */
  public function satPage() {
    $redirect = Url::fromUserInput('/digitalskills', [
      'query' => [
        'referrer' => 'dsjp',
      ],
    ]);
    if ($this->currentUser()->isAnonymous()) {
      $redirect = Url::fromRoute('user.login', [], [
        'query' => [
          'returnto' => $redirect->toString(),
        ],
      ]);
    }

    return new RedirectResponse($redirect->toString());
  }

  /**
   * Returns the user to the SAT landing pages based on the submission time.
   */
  public function returnPage(Request $request) {
    $satConfig = $this->config('dsjp_sat.configuration');
    if (!empty($satConfig->get('welcome'))) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $satConfig->get('welcome')]);
    }
    else {
      $url = Url::fromRoute('<front>');
    }
    if ($request->hasSession()) {
      $uid = $request->getSession()->get('uid');
      if (!empty($uid) && $user = $this->entityTypeManager()->getStorage('user')->load($uid)) {
        $assessments = $user->get('field_dsj_assessment')->referencedEntities();
        if (!empty($assessments)) {
          $latestAssessment = $assessments[count($assessments) - 1];
          $created = $latestAssessment->getCreatedTime();
          $timer = !empty($satConfig->get('timer')) ? $satConfig->get('timer') : 60;
          if ($this->time->getCurrentTime() - $created < $timer) {
            if (!empty($satConfig->get('success'))) {
              $url = Url::fromRoute('entity.node.canonical', ['node' => $satConfig->get('success')]);
            }
            else {
              $url = Url::fromRoute('view.dsj_assessments.page_1', [
                'user' => $uid,
              ]);
            }
          }
        }
      }
    }

    return new RedirectResponse($url->toString());
  }

  /**
   * Redirects user to personal assessments page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns the Redirect Response object.
   */
  public function userAssessmentsPage() {
    if ($this->currentUser()->isAnonymous()) {
      $url = Url::fromRoute('<front>');
    }
    else {
      $url = Url::fromRoute('view.dsj_assessments.page_1', [
        'user' => $this->currentUser()->id(),
      ]);
    }
    return new RedirectResponse($url->toString());
  }

}
