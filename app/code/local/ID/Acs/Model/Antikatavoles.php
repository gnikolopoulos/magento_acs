<?php

class ID_Acs_Model_Antikatavoles extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('id_acs/antikatavoles');
    }

    /*
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setDate($now);
        }
        return $this;
    }
    */
}