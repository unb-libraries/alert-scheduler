<?php

namespace Drupal\alert_scheduler_api\Form;

use Drupal\calendar_hours_server\Entity\HoursCalendar;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class AlertForm extends ContentEntityForm {

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
      $now = new DrupalDateTime('now', $calendar->getTimezone());
      $today = $now->format('Y-m-d');
      $hours = $calendar->getHours($today, $today);

      $form['hours'][$calendar->id()] = [
        '#type' => 'fieldset',
        '#title' => $calendar->title,
      ];

      foreach ($hours as $index => $block) {
        $form['hours'][$calendar_id][$index] = [
          '#type' => count($hours) > 1 ? 'fieldset' : 'container',
          '#title' => sprintf('%s - %s',
            $block->getStart()->format('h:i a'), $block->getEnd()->format('h:i a')),
          "block_id:{$calendar_id}:{$index}" => [
            '#type' => 'hidden',
            '#default_value' => $block->getId(),
          ],
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
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.scheduled_alert.collection');
    return parent::save($form, $form_state);
  }

}