<?php

namespace Drupal\alert_scheduler_api\Form;

use Drupal\calendar_hours_server\Entity\HoursCalendar;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

class AlertForm extends ContentEntityForm {

  protected const ACTION_CHANGE = 'change';
  protected const ACTION_CLOSE = 'close';

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['hours'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hours'),
    ];

    foreach ($this->loadCalendars(['libsys']) as $calendar_id => $calendar) {
    /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar */
      $now = new DrupalDateTime('now', $calendar->getTimezone());
      $today = $now->format('Y-m-d');
      $hours = $calendar->getHours($today, $today);

      $form['hours'][$calendar->id()] = [
        '#type' => 'fieldset',
        '#title' => $calendar->title,
      ];

      if (!empty($hours)) {
        foreach ($hours as $index => $block) {
          $form['hours'][$calendar_id]["{$calendar_id}_action"] = [
            '#type' => 'select',
            '#options' => [
              self::ACTION_CHANGE => $this->t('Change hours'),
              self::ACTION_CLOSE => $this->t('Close'),
            ],
            '#default_value' => self::ACTION_CHANGE,
          ];

          $form['hours'][$calendar_id][$index] = [
            '#type' => count($hours) > 1 ? 'fieldset' : 'container',
            '#title' => sprintf('%s - %s',
              $block->getStart()->format('h:i a'), $block->getEnd()->format('h:i a')),
            "block_id:{$calendar_id}:{$index}" => [
              '#type' => 'hidden',
              '#default_value' => $block->getId(),
            ],
            'time_fields' => [
              '#type' => 'container',
              "opens:{$calendar_id}:{$index}" => [
                '#type' => 'datetime',
                '#date_date_element' => 'none',
                '#default_value' => $block->getStart(),
                '#date_timezone' => $block->getStart()->getTimezone()->getName(),
              ],
              "closes:{$calendar_id}:{$index}" => [
                '#type' => 'datetime',
                '#date_date_element' => 'none',
                '#default_value' => $block->getEnd(),
                '#date_timezone' => $block->getEnd()->getTimezone()->getName(),
              ],
              '#states' => [
                'visible' => [
                  'select[name="' . $calendar_id . '_action"]' => [
                    'value' => self::ACTION_CHANGE,
                  ],
                ],
              ],
            ],
          ];
        }
      }
      else {
        $add_hours_url = $calendar
          ->toUrl('add-hours-form')
          ->setOption('query', [
            'date' => $today
          ]);
        $add_hours_link = Link::fromTextAndUrl('(re-)open it', $add_hours_url);
        $form['hours'][$calendar_id]['message'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('@calendar is closed on the selected date, but you can @create_link.', [
            '@calendar' => $calendar->label(),
            '@create_link' => $add_hours_link->toString(),
          ]),
        ];
      }
    }

    $form['#attached']['library'][] = 'alert_scheduler_api/timepicker';
    return $form;
  }

  /**
   * Load calendars.
   *
   * @param array $ids
   *   IDs of the calendars to load.
   *
   * @return \Drupal\calendar_hours_server\Entity\HoursCalendar[]
   *   Array of calendar entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadCalendars($ids = []) {
    /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar[] $calendars */
    $calendars = $this->entityTypeManager
      ->getStorage('hours_calendar')
      ->loadMultiple($ids);
    return $calendars;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    foreach ($this->loadCalendars(['libsys']) as $calendar_id => $calendar) {
      $now = new DrupalDateTime('now', $calendar->getTimezone());
      $today = $now->format('Y-m-d');
      $hours = $calendar->getHours($today, $today);

      $action = $form_state->getValue("{$calendar_id}_action");
      if ($action === self::ACTION_CHANGE) {
        foreach ($hours as $index => $block) {
          $event_id = $form_state->getValue("block_id:{$calendar_id}:{$index}");
          $from = $form_state->getValue("opens:{$calendar_id}:{$index}");
          $from->setDate(
            intval($now->format('Y')),
            intval($now->format('m')),
            intval($now->format('d'))
          );
          $to = $form_state->getValue("closes:{$calendar_id}:{$index}");
          $to->setDate(
            intval($now->format('Y')),
            intval($now->format('m')),
            intval($now->format('d'))
          );
          $this->updateHours($calendar, $event_id, $from, $to);
        }
      }
      elseif ($action === self::ACTION_CLOSE) {
        $this->close($calendar, $now);
      }
    }
  }

  /**
   * Update hours for the given calendar.
   *
   * @param \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar
   *   The calendar.
   * @param $event_id
   *   ID of the event to update.
   * @param DrupalDateTime $from
   *   New start of the event.
   * @param DrupalDateTime $to
   *   New end of the event.
   */
  protected function updateHours(HoursCalendar $calendar, $event_id, DrupalDateTime $from, DrupalDateTime $to) {
    try {
      $calendar->setHours($event_id, $from, $to);
      $this->messenger()->addStatus($this->t('Hours update for @calendar', [
        '@calendar' => $calendar->label(),
      ]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      $this->messenger()->addError($this->t('Hours not or only partially updated for @calendar.', [
        '@calendar' => $calendar->label(),
      ]));
    }
  }

  /**
   * 'Close' the given calendar.
   *
   * @param \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar
   *   The calendar.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   */
  protected function close(HoursCalendar $calendar, DrupalDateTime $date) {
    try {
      $calendar->close($date);
      $this->messenger()->addStatus($this->t('@calendar successfully close.', [
        '@calendar' => $calendar->label(),
      ]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      $this->messenger()->addError($this->t('@calendar could not be closed.', [
        '@calendar' => $calendar->label(),
      ]));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.scheduled_alert.collection');
    return parent::save($form, $form_state);
  }

}