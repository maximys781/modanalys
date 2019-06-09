<?php


namespace Drupal\modanalys\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\Link;

class UserDevices extends ControllerBase {
  protected $date;

  protected $formBuilder;


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

 
  public function __construct(DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder){
    $this->date        = $date_formatter;
    $this->formBuilder = $form_builder;
  }

  
  public function display() {
    $form = $this->formBuilder->getForm('Drupal\modanalys\Form\DateFilter');
    $header = $this->_getHeader();

    return array(
      '#title' => SafeMarkup::checkPlain(t('Устройства пользователей')),
      'modanalys_date_filter_form' => $form,
      'modanalys_table' => array(
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->_getData($header),
      ),
      'modanalys_pager' => array('#type' => 'pager')
    );
  }

  protected function _getHeader() {
    return array(
      '#' => array(
        'data'      => t('#'),
      ),
      'devperson_id' => array(
        'data'      => t('ID'),
        'field'     => 'devperson_id',
        'specifier' => 'devperson_id',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
        'sort'      => 'desc',
      ),
      'modanalys_date_time' => array(
        'data'      => t('Date'),
        'field'     => 'modanalys_date_time',
        'specifier' => 'modanalys_date_time',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
       'devperson_url' => array(
        'data'      => t('URL'),
        'field'     => 'devperson_url',
        'specifier' => 'devperson',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'u.name' => array(
        'data'      => t('User'),
        'field'     => 'u.name',
        'specifier' => 'u.name',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      '' => array(
        'data'      => t('Devices'),
      ),
    );
  }

  
  protected function _getData($header) {
    $items_per_page = \Drupal::config('visitors.config')->get('items_per_page');
    $query = db_select('devices', 'd')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->leftJoin('users_field_data', 'u', 'u.uid=d.devperson_id');
    $query->fields(
      'd',
      array(
        'devperson_id',
        'devperson_uid',
        'modanalys_date_time',
        'devperson_title',
        'devperson_path',
        'devperson_url'
      )
    );
    $query->fields('u', array('name', 'uid'));
    modanalys_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($items_per_page);

    $count_query = db_select('devices', 'd');
    $count_query->addExpression('COUNT(*)');
    modanalys_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();

    $page = isset($_GET['page']) ? (int) $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;
    $timezone =  drupal_get_user_timezone();
    foreach ($results as $data) {
      $user = user_load($data->devperson_uid);
      $username = array(
        '#type' => 'username',
        '#account' => $user
      );
      $rows[] = array(
        ++$i,
        $data->devperson_id,
        $this->date->format($data->modanalys_date_time, 'short'),
        $data->devperson_path,
        $user->getAccountName(),
        //\Drupal::l(t('details'), \Drupal\Core\Url::fromUri('visitors/hits/' . $data->visitors_id))
        \Drupal::l($this->t('details'),\Drupal\Core\Url::fromRoute('modanalys.hit_details',array("dev_id"=>$data->devperson_id)))
      );
    }

    return $rows;
  }
}
