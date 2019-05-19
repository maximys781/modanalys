<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\HitDetails.
 */

namespace Drupal\modanalys\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

class DevicesDetails extends ControllerBase {
  
  protected $date;

  public static function create(ContainerInterface $container) {
    return new static($container->get('date.formatter'));
  }

  public function __construct(DateFormatterInterface $date_formatter) {
    $this->date = $date_formatter;
  }

  /**
   * Returns a hit details page.
   *
   * @return array
   *   A render array representing the hit details page content.
   */
  public function display($devperson_id) {
    return array(
      'modanalys_table' => array(
        '#type' => 'table',
        '#rows'  => $this->_getData($devperson_id),
      ),
    );
  }

  /**
   * Returns a table content.
   *
   * @param int $hit_id
   *   Unique id of the visitors log.
   *
   * @return array
   *   Array representing the table content.
   */
  protected function _getData($hit_id) {
    $query = db_select('devperson', 'd');
    $query->leftJoin('users_field_data', 'u', 'u.uid=d.devperson_uid');
    $query->fields('d');
    $query->fields('u', array('name', 'uid'));
    $query->condition('d.devperson_id', (int) $devperson_id);
    $hit_details = $query->execute()->fetch();

    $rows = array();

    if ($hit_details) {
      $url          = urldecode($hit_details->deperson_url);
      $referer      = $hit_details->devperson_referer;
      $date         = $this->date->format($hit_details->modanalys_date_time, 'large');
      $whois_enable = \Drupal::service('module_handler')->moduleExists('whois');

      $attr         = array(
        'attributes' => array(
          'target' => '_blank',
          'title'  => t('Whois lookup')
        )
      );
      $ip = long2ip($hit_details->devperson_ip);
      $user = user_load($hit_details->devperson_uid);
      //@TODO make url, referer and username as link
      $array = array(
        'User'       => $user->getAccountName(),
        'IP'         => $whois_enable ? \Drupal::l($ip, 'whois/' . $ip, $attr) : $ip,
        'User Agent' => SafeMarkup::checkPlain($hit_details->visitors_user_agent)
      );

      /*if (\Drupal::service('module_handler')->moduleExists('visitors_geoip')) {
        $geoip_data_array = array(
          'Страна'        => SafeMarkup::checkPlain($hit_details->visitors_country_name),
          'Регион'         => SafeMarkup::checkPlain($hit_details->visitors_region),
          'Город'           => SafeMarkup::checkPlain($hit_details->visitors_city),
        );
        $array = array_merge($array, $geoip_data_array);
      }*/

      foreach ($array as $key => $value) {
        $rows[] = array(array('data' => t($key), 'header' => TRUE), $value);
      }
    }

    return $rows;
  }
}

