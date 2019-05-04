<?php

/**
 * @file
 * Contains \Drupal\visitors\Plugin\Block\VisitorsBlock.
 */

namespace Drupal\modanalys\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Visitors' block.
 *
 * @Block(
 *   id = "visitors_block",
 *   admin_label = @Translation("Visitors"),
 *   category = @Translation("Visitors")
 * )
 */
class PopPagesBlock extends BlockBase {
    protected $config;
    protected $items;

    /**
     * {@inheritdoc}
     */
    public function build() {
        $this->config = \Drupal::config('poppages.config');
        $this->items = array();

        $this->_showPopPages();

        return array(
            'poppages_info' => array(
                '#theme' => 'item_list',
                '#items' => $this->items,
            ),
        );
    }

    protected function _showPopPages() {
        if ($this->config->get('show_pop_pages')) {
            $query = db_select('poppages');
            $query->addExpression('COUNT(*)');

            $count = $query->execute()->fetchField() +
                $this->config->get('start_count_total_visitors');

            $this->items[] = t('Total Visitors: %poppages',
                array('%poppages' => $count)
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

}

