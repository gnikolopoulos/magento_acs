<?php

class ID_Acs_Model_Resource_Antikatavoles_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('id_acs/antikatavoles', 'entity_id');
    }
}