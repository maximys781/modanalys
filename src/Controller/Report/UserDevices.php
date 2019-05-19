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
      '#title' => SafeMarkup::checkPlain(t('User with IP:') . ' ' . $host),
      'modanalys_date_filter_form' => $form,
      'modanalys_table' => array(
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->_getData($header, $host),
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
        'specifier' => 'devperson_url',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'u.name' => array(
        'data'      => t('User'),
        'field'     => 'u.name',
        'specifier' => 'u.name',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      '' => array(
        'data'      => t('Operations'),
      ),
    );
  }

  
  protected function _getData($header, $host) {
    if (@inet_pton($host) === FALSE) //inet_pton занимается проверкой IPv6 адресов на соответствие
    {
      return;
    }

    $items_per_page = \Drupal::config('modanalys.config')->get('items_per_page');

    $query = db_select('devices', 'd')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('users_field_data', 'u', 'u.uid=d.devperson_uid');// делаем левое соединение, где один кортеж из одной таблицы связываем с другой
    $query->fields(
      'd',
      array(
        'devperson_id',
        'devperson_ip',
        'devperson_uid',
        'modanalys_date_time',
        'devperson_title',
        'devperson_path',
        'devperson_url'
      )
    );
    $query->fields('u', array('name', 'uid'));
    $query->condition('d.devperson_ip', sprintf('%u', ip2long($host)), '=');
    modanalys_date_filter_sql_condition($query);
    $query->orderByHeader($header);
    $query->limit($items_per_page);

    /*$count_query = db_select('devperson', 'd');
    $count_query->addExpression('COUNT(*)');
    $count_query->condition('visitors_ip', sprintf('%u', ip2long($host)));
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);*/
    $results = $query->execute();

    /*$count = $count_query->execute()->fetchField();
    if ($count == 0) {
      return;
    }*/
    $rows = array();

    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;

    foreach ($results as $data) {
      $user = user_load($data->devperson_uid);
      $username = array('#type' => 'username', '#account' => $user);

      $modanalys_host_url = Url::fromRoute('modanalys.hit_details',array("devperson_id"=>$data->devperson_id));
      $modanalys_host_link = \Drupal::l($this->t('details'),\Drupal\Core\Url::fromRoute('modanalys.hit_details',array("devperson_id"=>$data->devperson_id)));
      $modanalys_host_link = $modanalys_host_link->toRenderable();


      $user_profile_url = Url::fromRoute('entity.user.canonical',array("user"=>$user->id()));
      $user_profile_link = Link::fromTextAndUrl($user->getAccountName(),$user_profile_url);
      $user_profile_link = $user_profile_link->toRenderable();

      $rows[] = array(
        ++$i,
        $data->devperson_id,
        $this->date->format($data->modanalys_date_time, 'short'),
        SafeMarkup::checkPlain($data->devperson_title) . '<br/>',
        render($user_profile_link),
        render($modanalys_host_link)
        
      );
    }

    return $rows;
  }
}

