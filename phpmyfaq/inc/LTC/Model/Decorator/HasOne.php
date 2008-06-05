<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: HasOne.php,v 1.2 2008-04-28 21:14:20 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      29.09.2007
 */

/**
 * LTC_Model_Decorator_HasOne
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_Decorator_HasOne
    extends LTC_Model_Decorator_Association
{
    
    /**
     * Searches for data in the model matching the given where conditions. 
     * 
     * Returns an array containing the results.
     * If no results match the where condition(s), an empty array is returned. 
     * 
     * @param array
     * @param array
     * @param array 
     * @return array
     */
    public function find($where, $fields = array(), $options = array())
    {
        $db = LTC_Db::getInstance();
        // clean up vars
        $where  = $this->evaluateWhere($where);
        $fields = $this->evaluateFields($fields);
        // join primary key
        array_unshift($where, sprintf(
            '%s.%s=%s.%s',
            $this->model->getTableName(),
            $this->model->getPrimaryKey(),
            $this->associatedModel->getTableName(),
            $this->associatedModel->getPrimaryKey()
		));
        return $db->find(array($this->model, $this->associatedModel), $where, $fields, $options);
    }

    /**
     * Inserts a new data set into the current model and returns the ID of the 
     * new data set.
     * 
     * The ID is the value of the primary key field.   
     *
     * @param array 
     * @return int new ID
     */
    public function insert(array $data)
    {
        // insert data into current model
        $id = parent::insert($data);
        if (!is_int($id) or $id <= 0) {
            return false;
        }
        // insert data into associatedModel and return the ID
        $modelData = $this->extractModelData($data, $this->associatedModel);
        $modelData[$this->associatedModel->getPrimaryKey()] = $id;
        $associatedId = $this->associatedModel->insert($modelData);
        if (!is_int($associatedId) or $associatedId !== $id) {
            return false;
        }
        return $id;
    }
    
}