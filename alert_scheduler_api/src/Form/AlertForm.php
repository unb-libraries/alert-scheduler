<?php

namespace Drupal\alert_scheduler_api\Form;

use Drupal\calendar_hours_server\Entity\HoursCalendar;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

class AlertForm extends ContentEntityForm {

  protected const SETTINGS_AMENDABLE_CALENDARS = 'calendar_overrides';

  protected const ACTION_CHANGE = 'change';
  protected const ACTION_CLOSE = 'close';

  /**
   * Retrieve app settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable config object.
   */
  protected function getAppConfig() {
    return $this->config('alert_scheduler.settings');
  }

  /**
   * Retrieve a datetime generator instance for this module.
   *
   * @return \Drupal\alert_scheduler_api\AlertDateTimeGenerator
   *   A datetime generator instance.
   */
  protected function getDateTimeGenerator() {
    /** @var \Drupal\alert_scheduler_api\AlertDateTimeGenerator $generator */
    $generator = \Drupal::service('datetime_generator.alert_scheduler');
    return $generator;
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($calendars = $this->loadCalendars()) {
      $form['hours_override'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Change hours'),
        '#weight' => 10,
      ];

      $form['hours'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Hours'),
        '#states' => [
          'visible' => [
            'input[name="hours_override"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        '#weight' => 15,
      ];

      foreach ($calendars as $calendar_id => $calendar) {
        /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar */
        $now = new DrupalDateTime('now', $calendar->getTimezone());
        $today = $now->format('Y-m-d');
        if (!$date = $this->getRequest()->query->get('date')) {
          $date = $today;
        }
        $hours = $calendar->getHours($date, $date);

        $form['hours'][$calendar->id()] = [
          '#type' => 'fieldset',
          '#title' => $this->t($calendar->title . ' (@day)', [
            '@day' => $date === $today ? 'today' : 'tomorrow',
          ]),
          '#attributes' => [
            'class' => [
              'form-row',
              'pl-0',
            ],
          ],
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
                  '#title' => $this->t('Opens'),
                  '#date_date_element' => 'none',
                  '#default_value' => $block->getStart(),
                  '#date_timezone' => $block->getStart()->getTimezone()->getName(),
                ],
                "closes:{$calendar_id}:{$index}" => [
                  '#type' => 'datetime',
                  '#title' => $this->t('Closes'),
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
                '#attributes' => [
                  'class' => [
                    'form-row',
                    'pl-0',
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
              'date' => $date,
            ]);
          $add_hours_url->mergeOptions([
            'query' => $this->getRedirectDestination()->getAsArray(),
          ]);
          $add_hours_link = Link::fromTextAndUrl('(re-)open it', $add_hours_url);
          $form['hours'][$calendar_id]['message'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@calendar is currently closed, but you can @create_link.', [
              '@calendar' => $calendar->label(),
              '@create_link' => $add_hours_link->toString(),
            ]),
          ];
        }

        if (!array_key_exists('edit-other-day', $form['hours'])) {
          if ($date === $today) {
            $other_day_label = 'tomorrow';
            $tomorrow = $now->add(\DateInterval::createFromDateString('1 day'))
              ->format('Y-m-d');
            $other_day_url = $this->getEntity()
              ->toUrl('edit-form')
              ->setOption('query', [
                  'date' => $tomorrow,
                ] + $this->getRedirectDestination()->getAsArray());
          }
          else {
            $other_day_label = 'today';
            $other_day_url = $this->getEntity()
              ->toUrl('edit-form')
              ->mergeOptions([
                'query' => $this->getRedirectDestination()->getAsArray(),
              ]);
          }

          $form['hours']['edit-other-day'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t("If you need to change @other_day's hours instead, click @link", [
              '@other_day' => $other_day_label,
              '@link' => Link::fromTextAndUrl('here', $other_day_url)->toString(),
            ]),
            '#weight' => 100,
          ];
        }
      }
    }

    $form['#attached']['library'][] = 'alert_scheduler_api/timepicker';
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['#attributes'] = [
      'class' => [
        'mt-3',
      ],
    ];
    return $actions;
  }

  /**
   * Load calendars.
   *
   * @return \Drupal\calendar_hours_server\Entity\HoursCalendar[]
   *   Array of calendar entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadCalendars() {
    /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar[] $calendars */
    $calendars = $this->entityTypeManager
      ->getStorage('hours_calendar')
      ->loadMultiple($this->getWritableCalendarIds());
    return $calendars;
  }

  /**
   * Retrieve the IDs of all calendars that can be amended.
   *
   * @return array
   *   Array of HoursCalendar IDs.
   */
  protected function getWritableCalendarIds() {
    $calendar_ids = $this->getAppConfig()
      ->get(self::SETTINGS_AMENDABLE_CALENDARS);
    if (isset($calendar_ids)) {
      return $calendar_ids;
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if ($form_state->getValue('hours_override')) {
      foreach ($this->loadCalendars(['libsys']) as $calendar_id => $calendar) {
        if ($date = $this->getRequest()->query->get('date')) {
          $date = DrupalDateTime::createFromFormat('Y-m-d', $date);
        }
        else {
          $date = new DrupalDateTime('now', $calendar->getTimezone());
        }
        $hours = $calendar->getHours($date->format('Y-m-d'), $date->format('Y-m-d'));

        $action = $form_state->getValue("{$calendar_id}_action");
        if ($action === self::ACTION_CHANGE) {
          foreach ($hours as $index => $block) {
            $event_id = $form_state->getValue("block_id:{$calendar_id}:{$index}");
            $from = $form_state->getValue("opens:{$calendar_id}:{$index}");
            $from->setDate(
              intval($date->format('Y')),
              intval($date->format('m')),
              intval($date->format('d'))
            );
            $to = $form_state->getValue("closes:{$calendar_id}:{$index}");
            $to->setDate(
              intval($date->format('Y')),
              intval($date->format('m')),
              intval($date->format('d'))
            );
            $this->updateHours($calendar, $event_id, $from, $to);
          }
        }
        elseif ($action === self::ACTION_CLOSE) {
          $this->close($calendar, $date);
        }
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
      $this->messenger()->addStatus($this->t('@calendar is now open from @hours_start - @hours_end on @date.', [
        '@calendar' => $calendar->label(),
        '@hours_start' => $from->format('h:i a'),
        '@hours_end' => $to->format('h:i a'),
        '@date' => $from->format('M jS, Y'),
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
      $this->messenger()->addStatus($this->t('@calendar is now closed.', [
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