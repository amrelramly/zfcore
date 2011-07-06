<?php
/**
 * Core_Categories_Rowset
 *
 * @version $Id$
 */
class Core_Categories_Rowset extends Zend_Db_Table_Rowset_Abstract
{
    /**
     * Add row
     *
     * @param Core_Categories_Row $row
     * @return self
     */
    public function addRow(Core_Categories_Row $row)
    {
        $this->_data[] = $row->toArray();
        if ($this->_count == count($this->_rows)) {
            $this->_rows[] = $row;
        }
        // set the count of rows
        $this->_count = count($this->_data);

        return $this;
    }
}