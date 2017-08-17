<?php

class ID_Acs_Helper_Sms extends Mage_Core_Helper_Abstract
{

	//
	private function init() {
		$this->send_sms = Mage::getStoreConfig('acs/sms/send_sms');
		$this->sms_url = Mage::getStoreConfig('acs/sms/sms_url');
		$this->sms_user = Mage::getStoreConfig('acs/sms/sms_user');
		$this->sms_pass = Mage::getStoreConfig('acs/sms/sms_pass');
	}

}