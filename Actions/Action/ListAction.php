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

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vardius\Bundle\CrudBundle\Actions\Action;
use Vardius\Bundle\CrudBundle\Event\ActionEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvents;
use Vardius\Bundle\CrudBundle\Event\ResponseEvent;
use Vardius\Bundle\ListBundle\Column\ColumnInterface;
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
        $request = $event->getRequest();
        $format = $request->getRequestFormat();

        $this->checkRole($controller);

        $repository = $event->getDataProvider()->getSource();

        $listView = $event->getListView();
        $listDataEvent = new ListDataEvent($repository, $request);

        $dispatcher = $controller->get('event_dispatcher');
        $crudEvent = new CrudEvent($listView, $controller, $listDataEvent);
        $dispatcher->dispatch(CrudEvents::CRUD_LIST, $crudEvent);

        if ($format === 'html') {
            $params = [
                'list' => $listView->render($listDataEvent),
                'title' => $listView->getTitle(),
            ];
        } else {
            $columns = $listView->getColumns();
            $results = $listView->getData($listDataEvent, true);
            $results = $this->parseResults($results->toArray(), $columns, $format);

            $params = [
                'data' => $results,
            ];
        }

        $paramsEvent = new ResponseEvent($params);
        $crudEvent = new CrudEvent($repository, $controller, $paramsEvent);
        $dispatcher->dispatch(CrudEvents::CRUD_LIST_PRE_RESPONSE, $crudEvent);

        return $this->getResponseHandler($controller)->getResponse($format, $event->getView(), $this->getTemplate(), $paramsEvent->getParams(), 200, [], ['list']);
    }

    /**
     * @param array $results
     * @param ArrayCollection|ColumnInterface[] $columns
     * @param string $format
     * @return array
     */
    protected function parseResults(array $results, $columns, $format)
    {
        foreach ($results as $key => $result) {
            if (is_array($result)) {

                $results[$key] = $this->parseResults($result, $columns, $format);
            } elseif (method_exists($result, 'getId')) {
                $rowData = [];

                /** @var ColumnInterface $column */
                foreach ($columns as $column) {
                    $columnData = $column->getData($result, $format);
                    if ($columnData) {
                        $rowData[$column->getLabel()] = $columnData;
                    }
                }
                $results[$key] = $rowData;
            }
        }

        return $results;
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('defaults', [
            'page' => 1,
            'limit' => null,
            'column' => null,
            'sort' => null,
        ]);

        $resolver->setDefault('requirements', [
            'page' => '\d+',
            'limit' => '\d+',
        ]);

        $resolver->setDefault('pattern', function (Options $options) {
            if ($options['rest_route']) {
                return '.{_format}';
            }

            return '/list/{page}/{limit}/{column}/{sort}.{_format}';
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
