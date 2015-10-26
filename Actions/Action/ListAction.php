<?php
/**
 * This file is part of the vardius/crud-bundle package.
 *
 * (c) Rafał Lorenz <vardius@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vardius\Bundle\CrudBundle\Actions\Action;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vardius\Bundle\CrudBundle\Actions\Action;
use Vardius\Bundle\CrudBundle\Event\ActionEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvents;
use Vardius\Bundle\ListBundle\Event\ListDataEvent;

/**
 * ListAction
 *
 * @author Rafał Lorenz <vardius@gmail.com>
 */
class ListAction extends Action
{
    /**
     * {@inheritdoc}
     */
    public function call(ActionEvent $event)
    {
        $controller = $event->getController();
        $repository = $event->getDataProvider()->getSource();

        $dispatcher = $controller->get('event_dispatcher');
        $crudEvent = new CrudEvent($repository, $event->getController());
        $repository = $dispatcher->dispatch(CrudEvents::CRUD_LIST, $crudEvent)->getSource();

        $listView = $event->getListView();
        $listDataEvent = new ListDataEvent($repository, $event->getRequest());

        $params = [
            'list' => $listView->render($listDataEvent),
            'title' => $listView->getTitle(),
        ];

        $crudEvent = new CrudEvent($repository, $event->getController(), $params);
        $params = $dispatcher->dispatch(CrudEvents::CRUD_LIST_PRE_RESPONSE, $crudEvent)->getData();

        return $this->getResponseHandler($controller)->getResponse($this->options['response_type'], $event->getView(), $this->getTemplate(), $params);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('defaults', [
            'page' => 1,
            'limit' => 1,
            'column' => null,
            'sort' => null
        ]);

        $resolver->setDefault('requirements', [
            'page' => '\d+',
            'limit' => '\d+'
        ]);

        $resolver->setDefault('pattern', function (Options $options) {
            if ($options['rest_route']) {
                return '/{page}/{limit}/{column}/{sort}';
            }

            return '/list/{page}/{limit}/{column}/{sort}';
        });

        $resolver->setDefault('methods', function (Options $options, $previousValue) {
            if ($options['rest_route']) {
                return ['GET'];
            }

            return $previousValue;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'list';
    }

}
