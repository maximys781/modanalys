<?php

/**
 * @file
 * Contains Drupal\modanalys\EventSubscriber\TerminateSubscriber.
 */

namespace Drupal\modanalys\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Store visitors data when a request terminates.
 */
class KernelTerminateSubscriber implements EventSubscriberInterface {
    /**
     * The currently active request object.
     *
     * Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * Store visitors data when a request terminates.
     *
     * @param Symfony\Component\HttpKernel\Event\PostResponseEvent $event
     *   The Event to process.
     */
    public function onTerminate(PostResponseEvent $event) {
        $this->request = $event->getRequest();//создаем событие, которое запрашивает получение данных

        $user = \Drupal::currentUser();//получаем информацию о текущем пользователе
        $not_admin = !in_array('administrator', $user->getRoles());//если он не администратор, то говорим о том что нужно получить права для доступа
        $log_admin = !\Drupal::config('modanalys.config')->get('exclude_administer_users');//если администратор, то подругжаем наш конфиг.

        if ($log_admin || $not_admin) {
            $ip_str = $this->_getIpStr();
            $fields = array(
                //создаем массив и описываем перменные к ним, как и указатели на функции
                'poppages_uid'        => $user->id(),
                'poppages_ip'         => $ip_str,
                'modanalys_date_time'     => time(),
                'poppages_url'        => $this->_getUrl(),
                'poppages_referer'    => $this->_getReferer(),
                'poppages_path'       => Url::fromRoute('<current>')->toString(),
                'poppages_title'      => $this->_getTitle(),
                'poppages_user_agent' => $this->_getUserAgent()
            );



            db_insert('poppages')
                ->fields($fields)
                ->execute();
        }
    }

    /**
     * Registers the methods in this class that should be listeners.
     *
     * @return array
     *   An array of event listener definitions.
     */
    public static function getSubscribedEvents() {
        $events["kernel.terminate"] = ['onTerminate'];

        return $events;
    }

    /**
     * Get the title of the current page.
     *
     * @return string
     *   Title of the current page.
     */
    protected function _getTitle() {
        $title = \Drupal::routeMatch()->getRouteObject()->getDefault("_title");//выбираем рабочую область объекта и выбираем стандартное значение заголовка
        return htmlspecialchars_decode($title, ENT_QUOTES);
    }

    /**
     * Get full path request uri.
     *
     * @return string
     *   Full path.
     */
    protected function _getUrl() {
        return
            urldecode(sprintf('http://%s%s', $_SERVER['HTTP_HOST'], $this->request->getRequestUri()));//создаем запрос на получение ссылки через стандартную функцию $_SERVER['HTTP_HOST']
    }

    /**
     * Get the address of the page (if any) which referred the user agent to the
     * current page.
     *
     * @return string
     *   Referer, or empty string if referer does not exist.
     */
    protected function _getReferer() {
        return
            isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : '';
    }

    /**
     * Converts a string containing an visitors (IPv4) Internet Protocol dotted
     * address into a proper address.
     *
     * @return string
     */
    protected function _getIpStr() {
        return sprintf("%u", ip2long($this->request->getClientIp()));//получаем IP адрес типа IPv4
    }

    /**
     * Get visitor user agent.
     *
     * @return string
     *   string user agent, or empty string if user agent does not exist
     */
    protected function _getUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
}

