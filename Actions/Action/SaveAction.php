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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Vardius\Bundle\CrudBundle\Actions\Action;
use Vardius\Bundle\CrudBundle\Event\ActionEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvents;
use Vardius\Bundle\CrudBundle\Event\ResponseEvent;

/**
 * SaveAction
 *
 * @author Rafał Lorenz <vardius@gmail.com>
 */
abstract class SaveAction extends Action
{
    /**
     * Rest response success action code
     */
    CONST ACTION_CODE = 200;

    /**
     * {@inheritdoc}
     */
    public function call(ActionEvent $event, $format)
    {
        $controller = $event->getController();
        $dataProvider = $event->getDataProvider();
        $request = $event->getRequest();

        if ($id = $request->get('id')) {
            $data = $dataProvider->get($id);

            $this->checkRole($controller, $data);
        } else {
            $data = $dataProvider->create();

            $this->checkRole($controller);
        }

        $formProvider = $controller->get('vardius_crud.form.provider');
        $form = $formProvider->createForm($event->getFormType(), $data, [
            'method' => $request->getMethod()
        ]);

        $source = $dataProvider->getSource();
        $crudEvent = new CrudEvent($source, $controller, $form);

        $dispatcher = $controller->get('event_dispatcher');
        $dispatcher->dispatch(CrudEvents::CRUD_PRE_SAVE, $crudEvent);
        $responseHandler = $controller->get('vardius_crud.response.handler');

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {

            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($data->getId()) {
                    $dispatcher->dispatch(CrudEvents::CRUD_PRE_UPDATE, $crudEvent);
                    $dataProvider->update($data);
                    $dispatcher->dispatch(CrudEvents::CRUD_POST_UPDATE, $crudEvent);
                } else {
                    $dispatcher->dispatch(CrudEvents::CRUD_PRE_CREATE, $crudEvent);
                    $dataProvider->add($data);
                    $dispatcher->dispatch(CrudEvents::CRUD_POST_CREATE, $crudEvent);
                }

                $dispatcher->dispatch(CrudEvents::CRUD_POST_SAVE, $crudEvent);

                $routeName = rtrim(rtrim($request->get('_route'), 'edit'), 'add') . 'show';
                if (!$controller->get('router')->getRouteCollection()->get($routeName)) {
                    $routeName = rtrim($routeName, 'show') . 'list';
                }

                if (!$controller->get('router')->getRouteCollection()->get($routeName)) {
                    /** @var Session $session */
                    $session = $request->getSession();
                    /** @var FlashBagInterface $flashBag */
                    $flashBag = $session->getFlashBag();
                    $flashBag->add('success', 'save.success');

                    return $controller->redirect($responseHandler->getRefererUrl($controller, $request, [
                        'id' => $data->getId()
                    ]));
                }

                if ($format === 'html') {

                    return $controller->redirect($controller->generateUrl($routeName, [
                        'id' => $data->getId()
                    ]));
                } else {

                    return $responseHandler->getResponse($format, '', '', [
                        'data' => $data,
                    ], self::ACTION_CODE, [], ['Default', 'update']);
                }
            } elseif ($format === 'json') {
                $formErrorHandler = $controller->get('vardius_crud.form.error_handler');

                return new JsonResponse([
                    'message' => 'Invalid form data',
                    'errors' => $formErrorHandler->getErrorMessages($form),
                ], 400);
            }
        }

        $params = [
            'data' => $data,
        ];

        if ($format === 'html') {
            $params = array_merge($params, [
                'form' => $form->createView(),
            ]);
        }

        $paramsEvent = new ResponseEvent($params);
        $crudEvent = new CrudEvent($source, $controller, $paramsEvent);
        $dispatcher->dispatch(CrudEvents::CRUD_SAVE, $crudEvent);

        return $responseHandler->getResponse($format, $event->getView(), $this->getTemplate(), $paramsEvent->getParams(), 200, [], ['update']);
    }

}
