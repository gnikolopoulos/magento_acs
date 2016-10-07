<?php
 
class ID_Acs_Model_Resource_Antikatavoles extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('id_acs/antikatavoles', 'entity_id');
    }
}