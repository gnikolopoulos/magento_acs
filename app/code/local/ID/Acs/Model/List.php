<?php

class ID_Acs_Model_List extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('id_acs/list');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()) {
            $this->setCreatedAt($now);
        }
        return $this;
    }
}