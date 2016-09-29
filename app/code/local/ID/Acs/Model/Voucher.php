<?php

class ID_Acs_Model_Voucher extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('id_acs/voucher');
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