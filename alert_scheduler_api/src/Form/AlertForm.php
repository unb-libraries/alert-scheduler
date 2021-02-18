<?php

namespace Drupal\alert_scheduler_api\Form;

use Drupal\calendar_hours_server\Entity\HoursCalendar;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Form controller for "scheduled_alert" entities.
 *
 * @package Drupal\alert_scheduler_api\Form
 */
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
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($calendars = $this->loadCalendars()) {
      $form['hours_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'mt-5',
            'mb-4',
          ],
        ],
        '#weight' => 10,
        'hours_override_wrapper' => [
          '#type' => 'container',
          'hours_override' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Change hours'),
            '#weight' => 10,
          ],
        ],
        'hours' => [
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
        ],
      ];

      foreach ($calendars as $calendar_id => $calendar) {
        /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar */
        $now = new DrupalDateTime('now', $calendar->getTimezone());
        $today = $now->format('Y-m-d');
        if (!$date = $this->getRequest()->query->get('date')) {
          $date = $today;
        }
        $hours = $calendar->getHours($date, $date);

        $form['hours_container']['hours'][$calendar->id()] = [
          '#type' => 'fieldset',
          '#title' => $this->t('@calendar (@day@sep@date)', [
            '@calendar' => $calendar->title,
            '@day' => $date === $today ? 'today' : 'tomorrow',
            '@sep' => $date !== $today ? ', ' : '',
            '@date' => $date !== $today ? DrupalDateTime::createFromFormat('Y-m-d', $date)->format('M jS, Y') : '',
          ]),
          '#attributes' => [
            'class' => [
              'd-flex',
              'flex-column',
              'flex-sm-row',
            ],
          ],
        ];

        if (!empty($hours)) {
          foreach ($hours as $index => $block) {
            $form['hours_container']['hours'][$calendar_id]["{$calendar_id}_action"] = [
              '#type' => 'select',
              '#options' => [
                self::ACTION_CHANGE => $this->t('Change hours'),
                self::ACTION_CLOSE => $this->t('Mark as CLOSED (all day)'),
              ],
              '#default_value' => self::ACTION_CHANGE,
            ];

            $form['hours_container']['hours'][$calendar_id][$index] = [
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
          $form['hours_container']['hours'][$calendar_id]['message'] = [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => $this->t('@calendar is currently closed, but you can @create_link.', [
              '@calendar' => $calendar->label(),
              '@create_link' => $add_hours_link->toString(),
            ]),
          ];
        }

        if (!array_key_exists('edit-other-day', $form['hours_container']['hours'])) {
          if ($date === $today) {
            $other_day_label = 'tomorrow';
            $tomorrow = $now->add(\DateInterval::createFromDateString('1 day'))
              ->format('Y-m-d');
            if ($this->getEntity()->isNew()) {
              $other_day_url = Url::fromRoute('entity.scheduled_alert.add_form');
            }
            else {
              $other_day_url = $this->getEntity()->toUrl('edit-form');
            }
            $other_day_url->setOption('query', [
              'date' => $tomorrow,
            ] + $this->getRedirectDestination()->getAsArray());
          }
          else {
            $other_day_label = 'today';
            if ($this->getEntity()->isNew()) {
              $other_day_url = Url::fromRoute('entity.scheduled_alert.add_form');
            }
            else {
              $other_day_url = $this->getEntity()->toUrl('edit-form');
            }
            $other_day_url->mergeOptions([
              'query' => $this->getRedirectDestination()->getAsArray(),
            ]);
          }

          $form['hours_container']['hours']['edit-other-day'] = [
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

    $form['info'] = [
      '#type' => 'container',
      '#markup' => '<i class="fas fa-stopwatch mr-2"></i><span class="font-weight-bold">' . $this->t('Note') . ': </span>' . $this->t('It can take up to @interval minutes for new alerts or edits to display in the browser, depending on the cache cycle.', [
        '@interval' => $this->getHoursCacheInterval() / 60,
      ]),
      '#attributes' => [
        'class' => [
          'alert',
          'alert-warning',
        ],
      ],
      '#weight' => 90,
    ];

    $form['#attached']['library'][] = 'alert_scheduler_api/timepicker';
    return $form;
  }

  /**
   * Retrieve the number of seconds after which the hours cache is refreshed.

   * @return int
   *   An integer >= 0 indicating
   */
  protected function getHoursCacheInterval() {
    return $this->getHoursConfig()
      ->get('max_age');
  }

  /**
   * Retrieve "calendar_hours" settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable config object.
   */
  protected function getHoursConfig() {
    return $this->config(CALENDAR_HOURS__SETTINGS_CLIENT);
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
      $any_update = FALSE;
      foreach ($this->loadCalendars() as $calendar_id => $calendar) {
        /** @var \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar */
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
            /** @var \Drupal\Core\Datetime\DrupalDateTime $from */
            $from = $form_state->getValue("opens:{$calendar_id}:{$index}");
            $from->setDate(
              intval($date->format('Y')),
              intval($date->format('m')),
              intval($date->format('d'))
            );
            /** @var \Drupal\Core\Datetime\DrupalDateTime $to */
            $to = $form_state->getValue("closes:{$calendar_id}:{$index}");
            $to->setDate(
              intval($date->format('Y')),
              intval($date->format('m')),
              intval($date->format('d'))
            );

            $do_update = TRUE;
            if (($diff = $to->getTimestamp() - $from->getTimestamp()) < 0) {
              $to->add(\DateInterval::createFromDateString('1 day'));
            }
            elseif ($diff === 0) {
              $do_update = FALSE;
              $this->messenger()->addError($this->t('Opening and closing time must not be the same, @calendar was not updated.', [
                '@calendar' => $calendar->label(),
              ]));
            }
            elseif ($from->getTimestamp() === $block->getStart()->getTimestamp()
              && $to->getTimestamp() === $block->getEnd()->getTimestamp()) {
              $do_update = FALSE;
            }

            if ($do_update) {
              $this->updateHours($calendar, $event_id, $from, $to);
              $any_update = TRUE;
            }
          }
        }
        elseif ($action === self::ACTION_CLOSE) {
          $this->close($calendar, $date);
          $any_update = TRUE;
        }
      }

      if ($any_update) {
        $this->messenger()->addWarning($this->t('Hours will not update on the website until @release_time.', [
          '@release_time' => $this->nextQuarterHour()->format('h:i a'),
        ]));
      }
    }
  }

  /**
   * Retrieve a date time object which represents the next quarter of the hour.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A datetime object.
   */
  protected function nextQuarterHour() {
    $release_time = new DrupalDateTime('now');
    $hour = intval($release_time->format('H'));
    $minute = intval($release_time->format('i'));
    $past_quarter_hour = floor($minute / 15) * 15;
    $next_quarter_hour = $past_quarter_hour + 15;

    $release_time->setTime($hour, 0, 0)
      ->add(\DateInterval::createFromDateString("{$next_quarter_hour} minutes"));

    return $release_time;
  }

  /**
   * Update hours for the given calendar.
   *
   * @param \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar
   *   The calendar.
   * @param string $event_id
   *   ID of the event to update.
   * @param \Drupal\Core\Datetime\DrupalDateTime $from
   *   New start of the event.
   * @param \Drupal\Core\Datetime\DrupalDateTime $to
   *   New end of the event.
   */
  protected function updateHours(HoursCalendar $calendar, string $event_id, DrupalDateTime $from, DrupalDateTime $to) {
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
   * Set the library associated with the given calendar to 'closed'.
   *
   * @param \Drupal\calendar_hours_server\Entity\HoursCalendar $calendar
   *   The calendar.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   */
  protected function close(HoursCalendar $calendar, DrupalDateTime $date) {
    try {
      $calendar->close($date);
      $this->messenger()->addStatus($this->t('@calendar is now closed on @date.', [
        '@calendar' => $calendar->label(),
        '@date' => $date->format('D jS, Y'),
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
