<?php


namespace Drupal\modanalys\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

class PopPages extends ControllerBase {
  protected $date;
  protected $formBuilder;

  public static function create(ContainerInterface $container){
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder')//создаем функцию, которая будет строить нам форму при запросе к БД
    );
  }

  public function __construct(DateFormatterInterface $date_formatter,FormBuilderInterface $form_builder)
  {
    $this->date = $date_formatter;
    $this->formBuilder = $form_builder;//создаем указатель на переменную
  }

  public function display()
  {
    $form = $this->formBuilder->getForm('Drupal\modanalys\Form\DateFilter');
    $header = $this->_getHeader();//создаем тему в которой указываем тип формы, заголовки в шапке и то откуда будем брать данные в таблице

    return array(
      'modanalys_date_time_form' => $form,
      'modanalys_table' => array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $this->_getData($header),
      ),
      'modanalys_pager' => array('#type' => 'pager')
    );
  }
  protected function _getHeader(){//создаем функцию в которой указываем заголовки таблицы
    return array(
      '#' => array(
        'data' => t('#'),
      ),
      'poppages_url' => array(
        'data' => t('URL'),
        'field' => 'poppages_url',
        'specifier' => 'poppages_url',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'count' => array(
        'data' => t('Count'),
        'field' => 'count',
        'specifier' => 'count',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
        'sort' => 'desc',
      ),
    );
  }

  /**
   * Returns a table content.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   Array representing the table content.
   */
  protected function _getData($header){
    $items_per_page = \Drupal::config('modanalys.config')->get('items_per_page');//создаем переменную, которая отвечает за  переход по страницам и подключаем свой конфиг

    $query = db_select('poppages', 'p')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');//подключаемся к БД и подключаем функционал который нам понадобится

    $query->addExpression('COUNT(poppages_id)', 'count');//считаем число идентификаторов
    $query->addExpression('MIN(poppages_title)', 'poppages_title');//делаем так чтобы заголовок у нас выполнял функцию сортировки поиска минимума
    $query->addExpression('MIN(poppages_url)', 'poppages_url');// ссылки выпадают в порядке минимума
    $query->fields('p', array('poppages_path'));// определяем р как массив
    modanalys_date_filter_sql_condition($query);
    $query->groupBy('poppages_path');//группируем массив
    $query->orderByHeader($header);//вставляем сортировку в шапку
    $query->limit($items_per_page);//выбор по страницам

    $count_query = db_select('poppages', 'p');
    $count_query->addExpression('COUNT(DISTINCT poppages_path)');//отсутствие совпадения ссылок
    modanalys_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);//счетчик подключений
    $results = $query->execute();//делаем выборку

    $rows = array();

    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;

    foreach ($results as $data ){
      $rows[] = array(
        ++$i,
        $data->poppages_url,
        $data->count,
      );
    }

    return $rows;
  }
}

