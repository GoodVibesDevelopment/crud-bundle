<?php
/**
 * This file is part of the vardius/crud-bundle package.
 *
 * (c) Rafał Lorenz <vardius@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vardius\Bundle\CrudBundle\Data;

use Doctrine\ORM\EntityRepository;

/**
 * DataProviderInterface
 *
 * @author Rafał Lorenz <vardius@gmail.com>
 */
interface DataProviderInterface
{
    /**
     * Returns source of data
     *
     * @return EntityRepository
     */
    public function getSource();

    /**
     * Returns data item by id
     *
     * @param null $id
     * @return mixed
     */
    public function get($id = null);

    /**
     * Creates new element in data source
     * @return mixed
     */
    public function create();

    /**
     * Removes the element from data source
     * Accepts element as object or it's id
     *
     * @param $data
     * @throws \InvalidArgumentException
     */
    public function remove($data);

    /**
     * Adds element to data source
     * @param $data
     */
    public function add($data);

    /**
     * Updates element of data source
     *
     * @param $data
     */
    public function update($data);

    /**
     * @param array $criteria
     * @return mixed
     */
    public function findBy(array $criteria);

    /**
     * @return mixed
     */
    public function findAll();

    /**
     * @param $data
     * @return mixed
     */
    public function reload($data);
}
