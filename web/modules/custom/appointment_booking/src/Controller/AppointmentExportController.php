<?php
namespace Drupal\appointment_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class AppointmentExportController extends ControllerBase {

  use DependencySerializationTrait;

  protected $fileSystem;
  protected $tempStore;
  protected $fileUrlGenerator;
  protected $messenger;

  public function __construct(
    FileSystemInterface $file_system,
    PrivateTempStoreFactory $temp_store,
    FileUrlGeneratorInterface $file_url_generator,
    MessengerInterface $messenger,
  ) {
    $this->fileSystem = $file_system;
    $this->tempStore = $temp_store->get('appointment_booking');
    $this->fileUrlGenerator = $file_url_generator;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('file_url_generator'),
      $container->get('messenger')

    );
  }

  /**
   * Initialize filtered export.
   */
  public function startExport() {
    $filters = $this->tempStore->get('current_filters') ?: [];

    $storage = $this->entityTypeManager()->getStorage('appointment');
    $query = $storage->getQuery();
    $query->accessCheck(TRUE); 
    $this->applyFiltersToQuery($query, $filters);
    $count = $query->count()->execute();

    if ($count > 1000000) {
      $this->messenger->addError($this->t('Cannot export over 1 million appointments. Please apply more filters.'));
      return $this->redirect('entity.appointment.collection');
    }

    $batch = [
      'title' => $this->t('Exporting filtered appointments...'),
      'operations' => [
        [['\Drupal\appointment_booking\Controller\AppointmentExportController', 'processBatchStatic'], [$filters]],
        [['\Drupal\appointment_booking\Controller\AppointmentExportController', 'generateCsvStatic'], []],
      ],
      'finished' => [$this, 'batchFinished'],
      'init_message' => $this->t('Preparing filtered export...'),
      'progress_message' => $this->t('Processed @current of @total appointments.'),
      'error_message' => $this->t('Export failed.'),
    ];

    batch_set($batch);
    return batch_process();
  }

  public static function processBatchStatic($filters, &$context) {
    $controller = \Drupal::service('appointment_booking.export_controller');
    $controller->processBatch($filters, $context);
  }

  public static function generateCsvStatic(&$context) {
    $controller = \Drupal::service('appointment_booking.export_controller');
    $controller->generateCsv($context);
  }

  protected function applyFiltersToQuery($query, $filters) {
    if (!empty($filters['adviser'])) {
      $query->condition('adviser', $filters['adviser'], 'IN');
    }
    if (!empty($filters['date'])) {
      $query->condition('date', strtotime($filters['date']), '=');
    }
    if (!empty($filters['appointment_type'])) {
      $query->condition('appointment_type', $filters['appointment_type']);
    }
    if (!empty($filters['agency'])) {
      $query->condition('agency', $filters['agency']);
    }
  }

  public function processBatch($filters, &$context) {
    $storage = $this->entityTypeManager()->getStorage('appointment');
    dump($storage);
    die();
    if (!isset($context['sandbox']['progress'])) {
      $query = $storage->getQuery();
      $query->accessCheck(TRUE); 
      $this->applyFiltersToQuery($query, $filters);
      $context['sandbox']['ids'] = $query->execute();
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['ids']);
      $context['results']['headers'] = [
        'Title', 'Adviser', 'Date', 'Agency', 'Type', 'Status'
      ];
      $context['results']['data'] = [];
    }

    $ids = array_slice(
      $context['sandbox']['ids'],
      $context['sandbox']['progress'],
      50
    );

    $appointments = $storage->loadMultiple($ids);
    foreach ($appointments as $appointment) {
      $context['results']['data'][] = [
        $appointment->label(),
        $appointment->get('adviser')->entity ? $appointment->get('adviser')->entity->label() : '',
        $appointment->get('date')->value,
        $appointment->get('agency')->entity ? $appointment->get('agency')->entity->label() : '',
        $appointment->label(),
        $appointment->get('status')->value,
      ];
      $context['sandbox']['progress']++;
    }

    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  public function generateCsv(&$context) {
    $filename = 'appointments-filtered-' . date('Y-m-d') . '.csv';
    $directory = 'temporary://exports';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    $file_path = $directory . '/' . $filename;
    $file = fopen($file_path, 'w');

    fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($file, $context['results']['headers']);

    foreach ($context['results']['data'] as $row) {
      fputcsv($file, $row);
    }

    fclose($file);
    $context['results']['file'] = $file_path;
  }

  public function batchFinished($success, $results, $operations) {
    if ($success && !empty($results['file'])) {
      // Vérifier que le fichier existe
      if (file_exists($results['file'])) {
        // Créer une réponse de téléchargement sécurisée
        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($results['file']);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(
          \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
          basename($results['file'])
        );
        
        // Stocker le chemin temporairement pour le téléchargement
        $this->tempStore->set('last_export_file', $results['file']);
        
        // Créer un lien sécurisé via une route
        $url = Url::fromRoute('appointment_booking.export_download');
        $link = Link::fromTextAndUrl($this->t('Download CSV'), $url)->toString();
        $this->messenger->addStatus($this->t('Export complete. @link', ['@link' => $link]));
      } else {
        $this->messenger->addError($this->t('Generated file not found.'));
      }
    } else {
      $this->messenger->addError($this->t('Export failed. Please try again.'));
    }
  }
  
  /**
   * Nouvelle méthode pour gérer le téléchargement
   */
  public function downloadExport() {
    $file_path = $this->tempStore->get('last_export_file');
    
    if (!$file_path || !file_exists($file_path)) {
      $this->messenger->addError($this->t('File no longer available.'));
      return $this->redirect('entity.appointment.collection');
    }
  
    $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($file_path);
    $response->headers->set('Content-Type', 'text/csv');
    $response->setContentDisposition(
      \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      'appointments-' . date('Y-m-d') . '.csv'
    );
    
    // Nettoyer après téléchargement
    $this->tempStore->delete('last_export_file');
    
    return $response;
  }}