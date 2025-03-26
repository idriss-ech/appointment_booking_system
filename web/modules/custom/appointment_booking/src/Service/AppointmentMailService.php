<?php
namespace Drupal\appointment_booking\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;

class AppointmentMailService {
  protected $mailManager;
  protected $languageManager;
  protected $renderer;

  public function __construct(
    MailManagerInterface $mail_manager, 
    LanguageManagerInterface $language_manager,
    RendererInterface $renderer
  ) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  public function sendConfirmationEmail($to, array $appointment_data) {
    $params = [
      'subject' => t('Your appointment confirmation'),
      'message' => $this->buildEmailContent($appointment_data),
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'From' => \Drupal::config('system.site')->get('mail'),
      ],
    ];

    return $this->sendEmail(
      'appointment_booking',
      'appointment_confirmation',
      $to,
      $params
    );
  }

  public function sendVerificationEmail($to, $code) {
    $params = [
      'subject' => t('Your phone verification code'),
      'body' => [
        t('Your verification code is: @code', ['@code' => $code]),
        t('This code will expire in 15 minutes.'),
      ],
    ];

    return $this->sendEmail(
      'appointment_booking',
      'phone_verification',
      $to,
      $params
    );
  }

  protected function sendEmail($module, $key, $to, array $params) {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $result = $this->mailManager->mail(
      $module,
      $key,
      $to,
      $langcode,
      $params,
      NULL,
      TRUE
    );

    if ($result['result'] !== TRUE) {
      \Drupal::logger('appointment_booking')->error('Failed to send email to @email', ['@email' => $to]);
      return FALSE;
    }

    return TRUE;
  }

  protected function buildEmailContent(array $appointment_data) {
    $message = [
      '#theme' => 'appointment_confirmation_email',
      '#agency_name' => $appointment_data['agency']->label(),
      '#adviser_name' => $appointment_data['adviser']->label(),
      '#date_time' => $appointment_data['date'],
      '#customer_name' => $appointment_data['customer_name'],
    ];

    return $this->renderer->render($message);
  }
}