<?php

declare(strict_types=1);

namespace Drupal\oe_whitelabel_helper\Plugin\better_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort\DefaultWidget;

/**
 * OpenEuropa custom better exposed filters widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "oe_whitelabel_float_end_sort",
 *   label = @Translation("Float End Sort"),
 * )
 */
class FloatEndSortWidget extends DefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) : void {
    parent::exposedFormAlter($form, $form_state);

    // @todo This will be done via theming in OEL-1583.
    $form['#attributes']['class'][] = 'col-lg-4';
    $form['#attributes']['class'][] = 'col-md-6';
    $form['#attributes']['class'][] = 'mt-3';
    $form['#attributes']['class'][] = 'mt-md-0';

    if (empty($form['sort_bef_combine'])) {
      return;
    }

    $form['sort_bef_combine']['#attributes']['class'][] = 'ms-md-2';
    $form['sort_bef_combine']['#label_attributes']['class'][] = 'mb-0';
    $form['sort_bef_combine']['#label_attributes']['class'][] = 'text-nowrap';
    $form['sort_bef_combine']['#wrapper_attributes']['class'][] = 'align-items-center';
    $form['sort_bef_combine']['#wrapper_attributes']['class'][] = 'd-md-flex';
    $form['sort_bef_combine']['#wrapper_attributes']['class'][] = 'float-md-end';
    $form['sort_bef_combine']['#wrapper_attributes']['class'][] = 'mb-4';
    $form['sort_bef_combine']['#wrapper_attributes']['class'][] = 'mb-md-0';
  }

}
