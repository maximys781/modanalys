<?php


/**
 * @file
 * Contains Drupal\modanalys\Controller\PopPages.
 */

namespace Drupal\modanalys\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

class PopPages extends ControllerBase
{
    /**
     * The date service.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */

    /**
     * The form builder service.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */

    /**
     * Returns a top pages page.
     *
     * @return array
     *   A render array representing the top pages page content.
     */
    public function display()
    {
        $header = $this->_getHeader();//создаем тему в которой указываем тип формы, заголовки в шапке и то откуда будем брать данные в таблице

        return array(
            'poppages_table' => array(
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $this->_getData($header),
            ),
            'poppages_pager' => array('#type' => 'pager')
        );
    }

    /**
     * Returns a table header configuration.
     *
     * @return array
     *   A render array representing the table header info.
     */
    protected function _getHeader()//создаем функцию в которой указываем заголовки таблицы
    {
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
    protected function _getData($header)
    {
        $items_per_page = \Drupal::config('poppages.config')->get('items_per_page');//создаем переменную, которая отвечает за  переход по страницам и подключаем свой конфиг

        $query = db_select('poppages', 'p')
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
            ->extend('Drupal\Core\Database\Query\TableSortExtender');//подключаемся к БД и подключаем функционал который нам понадобится

        $query->addExpression('COUNT(poppages_id)', 'count');//считаем число идентификаторов
        $query->addExpression('MIN(poppages_title)', 'poppages_title');//делаем так чтобы заголовок у нас выполнял функцию сортировки поиска минимума
        $query->addExpression('MIN(poppages_url)', 'poppages_url');// ссылки выпадают в порядке минимума
        $query->fields('p', array('poppages_path'));// определяем р как массив
        $query->groupBy('poppages_path');//группируем массив
        $query->orderByHeader($header);//вставляем сортировку в шапку
        $query->limit($items_per_page);//выбор по страницам

        $count_query = db_select('poppages', 'p');
        $count_query->addExpression('COUNT(DISTINCT poppages_path)');//отсутствие совпадения ссылок
        $query->setCountQuery($count_query);//счетчик подключений
        $results = $query->execute();//делаем выборку

        $rows = array();

        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $i = 0 + $page * $items_per_page;
        //@TODO add links
        foreach ($results as $data) {
            $rows[] = array(
                ++$i,
                //SafeMarkup::checkPlain($data->visitors_title) . '<br/>' .$data->visitors_url,
                $data->poppages_url,
                // l($data->visitors_path, $data->visitors_url),
                $data->count,
            );
        }

        return $rows;
    }
}

