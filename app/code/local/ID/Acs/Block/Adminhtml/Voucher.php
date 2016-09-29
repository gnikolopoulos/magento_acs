<?php

class ID_Acs_Block_Adminhtml_Voucher extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller         = 'adminhtml_voucher';
        $this->_blockGroup         = 'id_acs';
        parent::__construct();
        $this->_headerText         = Mage::helper('acs')->__('Vouchers');

        $this->_removeButton('add');
    }
}