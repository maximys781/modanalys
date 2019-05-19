<?php

/**
 * @file
 * Contains \Drupal\visitors\Plugin\Block\VisitorsBlock.
 */

namespace Drupal\modanalys\Plugin\Block;

use Drupal\Core\Block\BlockBase;


class ModanalysBlock extends BlockBase {
    protected $config;
    protected $items;

    /**
     * {@inheritdoc}
     */
    public function build() {
        $this->config = \Drupal::config('modanalys.config');
        $this->items = array();

        $this->_showPopPages();
        $this->_showPublishedNodes();
        $this->_showSinceDate();
        $this->-showUserIp();

        return array(
            'modanalys_info' => array(
                '#theme' => 'item_list',
                '#items' => $this->items,
            ),
        );
    }

    protected function _showPopPages() {
        if ($this->config->get('show_total_modanalys')) {
            $query = db_select('poppages');
            $query->addExpression('COUNT(*)');

            $count = $query->execute()->fetchField() +
                $this->config->get('start_count_total_modanalys');

            $this->items[] = t('Total Poppages: %modanalys',
                array('%modanalys' => $count)
            );
        }
    }
    protected function _showPublishedNodes() {
        if ($this->config->get('show_published_nodes')) {
            $query = db_select('node', 'n');
            $query->innerJoin('node_field_data', 'nfd', 'n.nid = nfd.nid');
            $query->addExpression('COUNT(*)');
            $query->condition('nfd.status', '1', '=');

            $nodes = $query->execute()->fetchField();

            $this->items[] = t('Published Nodes: %nodes',
                array('%nodes' => $nodes)
            );
        }
    }

    protected function _showSinceDate() {
        if ($this->config->get('show_since_date')) {
            $query = db_select('poppages');
            $query->addExpression('MIN(modanalys_date_time)');

            $since_date = $query->execute()->fetchField();

            $this->items[] = t('Since: %since_date',
                array('%since_date' => format_date($since_date, 'short'))
            );
        }
    }
    protected function _showUserIp(){
        if ($this->config->get('show_user_ip')){
            $this->items[]= t('Твой IP: %user_ip',
                array('%user_ip' => \Drupal::request()->getClientIp())//мы вызываем эту функцию из Symfony HttpFoundation, которая  позволяет определить IP адрес клиента.
            );
    }
}

}

