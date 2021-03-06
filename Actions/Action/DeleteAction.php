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

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vardius\Bundle\CrudBundle\Actions\Action;
use Vardius\Bundle\CrudBundle\Event\ActionEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvent;
use Vardius\Bundle\CrudBundle\Event\CrudEvents;

/**
 * DeleteAction
 *
 * @author Rafał Lorenz <vardius@gmail.com>
 */
class DeleteAction extends Action
{
    /**
     * {@inheritdoc}
     */
    public function call(ActionEvent $event, string $format):Response
    {
        $controller = $event->getController();
        $dataProvider = $event->getDataProvider();
        $request = $event->getRequest();

        $id = $request->get('id');
        $data = $dataProvider->get($id);
        if ($data === null) {
            throw new EntityNotFoundException('Not found error');
        }

        $this->checkRole($controller, $data);

        $crudEvent = new CrudEvent($dataProvider->getSource(), $controller);
        $dispatcher = $controller->get('event_dispatcher');
        $dispatcher->dispatch(CrudEvents::CRUD_PRE_DELETE, $crudEvent);

        try {
            $dataProvider->remove($data->getId());
            $status = 200;
            $response = $data;
        } catch (\Exception $e) {
            $message = null;
            if (is_object($data) && method_exists($data, '__toString')) {
                $message = 'Error while deleting "' . $data . '"';
            } else {
                $message = 'Error while deleting element with id "' . $id . '"';
            }

            $response = [
                'success' => false,
                'error' => $message,
            ];
            $status = 400;

            if ($format === 'html') {
                /** @var Session $session */
                $session = $request->getSession();
                /** @var FlashBagInterface $flashBag */
                $flashBag = $session->getFlashBag();
                $flashBag->add('error', $message);
            }
        }

        $dispatcher->dispatch(CrudEvents::CRUD_POST_DELETE, $crudEvent);
        $responseHandler = $controller->get('vardius_crud.response.handler');

        if ($format === 'html') {
            return $controller->redirect($responseHandler->getRefererUrl($controller, $request));
        } else {

            return $responseHandler->getResponse($format, '', '', $response, $status);
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('template', 'delete');

        $resolver->setDefault('requirements', [
            'id' => '\d+'
        ]);

        $resolver->setDefault('pattern', function (Options $options) {
            return $options['rest_route'] ? '/{id}.{_format}' : '/delete/{id}.{_format}';
        });

        $resolver->setDefault('defaults', function (Options $options) {
            $format = $options['rest_route'] ? 'json' : 'html';

            return [
                '_format' => $format
            ];
        });

        $resolver->setDefault('methods', function (Options $options, array $previousValue) {
            return $options['rest_route'] ? ['DELETE'] : $previousValue;
        });
    }
}
