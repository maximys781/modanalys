<?php

namespace Drupal\modanalys\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\DateHelper;
use Drupal\Core\Url;

class Settings extends ConfigFormBase {
    public function getFormID() {
        return 'poppages_admin_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = \Drupal::config('poppages.config');
        $form = array();

        $form['settings'] = array(
            '#type' => 'fieldset',
            '#weight' => -30,
            '#title' => t('Популярные страницы'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#description' => t('Популярные страницы позволяют бла бла бла')
        );

        $form['settings']['show_pop_pages'] = array(
            '#type' => 'table',
            '#title' => t('poppages'),
            '#default_value' => $config->get('show_pop_pages'),
            '#description' => t('Show Pop Pages.')
        );


        return parent::buildForm($form, $form_state);;
    }



    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return array();
    }
}

