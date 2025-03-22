<?php

namespace Drupal\appointment_booking\Service;

class OperatingHoursFormatter {

  /**
   * Formats operating hours into a human-readable string.
   *
   * @param array $operating_hours
   *   The operating hours data.
   *
   * @return string
   *   The formatted operating hours.
   */
  public function formatOperatingHours(array $operating_hours) {
    $formatted_hours = [];
    $days_of_week = [
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
      7 => 'Sunday',
    ];

    foreach ($operating_hours as $day) {
      if (!empty($day['starthours']) && !empty($day['endhours'])) {
        // Convert starthours and endhours to H:i format.
        $start_time = str_pad(floor($day['starthours'] / 100), 2, '0', STR_PAD_LEFT) . ':' . str_pad($day['starthours'] % 100, 2, '0', STR_PAD_LEFT);
        $end_time = str_pad(floor($day['endhours'] / 100), 2, '0', STR_PAD_LEFT) . ':' . str_pad($day['endhours'] % 100, 2, '0', STR_PAD_LEFT);

        // Get the corresponding day of the week (e.g., Monday, Tuesday, etc.).
        $day_name = isset($days_of_week[$day['day']]) ? $days_of_week[$day['day']] : '';

        // Add to the formatted hours array in the desired format.
        if ($day_name) {
          $formatted_hours[] = $day_name . ': ' . $start_time . ' - ' . $end_time;
        }
      }
    }

    // Join all the formatted hours into a string.
    return implode(', ', $formatted_hours);
  }
}
