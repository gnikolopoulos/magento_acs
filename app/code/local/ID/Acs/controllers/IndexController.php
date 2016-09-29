<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ID_Acs_IndexController extends Mage_Core_Controller_Front_Action
{

	private $companyId;
	private $companyPass;
	private $username;
	private $password;

	private function init()
	{
		$this->companyId = Mage::getStoreConfig('acs/login/company_id');
		$this->companyPass = Mage::getStoreConfig('acs/login/company_pass');
		$this->username = Mage::getStoreConfig('acs/login/username');
		$this->password = Mage::getStoreConfig('acs/login/password');
	}

    public function indexAction()
    {
    	$this->init();

    	$order_collection = Mage::getModel('sales/order')
					->getCollection()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', array('in' => array('complete')));

		foreach ($order_collection as $order) {
			$client = @new SoapClient("https://services.acscourier.net/ACSTracking-portlet/api/axis/Plugin_acsTracking_TrackingSummaryWithStatusService?wsdl");
			$params = array(
						'companyId'			=> $this->companyId,
						'companyPass'		=> $this->companyPass,
						'username'			=> $this->username,
						'password'			=> $this->password,
						'pod_no'			=> $order->getTracksCollection()->getFirstItem()->getNumber(),
					  );
			$response = @$client->__soapCall("findByPod_no", $params);
			echo $response{0}->shipment_status.':';
			if( $response{0}->shipment_status == '4' ) {
				echo $order->getIncrementId() . ' Delivered<br />';
				$order->setStatus("delivered");
				$order->save();
			} elseif( $response{0}->shipment_status == '7' || $response{0}->shipment_status == '6' ) {
				echo $order->getIncrementId() . ' Denied<br />';
				$order->setStatus("denied");
				$order->save();
			} else {
				echo $order->getIncrementId() . ' Not Delivered<br />';
				// Other stuff...
			}
		}
    }

    /*
    public function indexAction()
    {
    	$this->init();

    	$order_collection = Mage::getModel('sales/order')
					->getCollection()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', array('in' => array('complete')));

			foreach ($order_collection as $order) {
				$shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')->setOrderFilter($order)->load();
				foreach ($shipmentCollection as $shipment) {
					foreach ($shipment->getAllTracks() as $tracking) {
						$client = @new SoapClient("https://services.acscourier.net/ACSTracking-portlet/api/axis/Plugin_acsTracking_TrackingSummaryWithStatusService?wsdl");
						$params = array(
									'companyId'			=> $this->companyId,
									'companyPass'		=> $this->companyPass,
									'username'			=> $this->username,
									'password'			=> $this->password,
									'pod_no'			=> $tracking->getNumber(),
								  );
						$response = @$client->__soapCall("findByPod_no", $params);
						echo $response{0}->shipment_status.':';
						if( $response{0}->shipment_status == '4' ) {
							echo $order->getIncrementId() . ' Delivered<br />';
							$order->setStatus("delivered");
							$order->save();
						} elseif( $response{0}->shipment_status == '7' || $response{0}->shipment_status == '6' ) {
							echo $order->getIncrementId() . ' Denied<br />';
							$order->setStatus("denied");
							$order->save();
						} else {
							echo $order->getIncrementId() . ' Not Delivered<br />';
							// Other stuff...
						}
					}
				}
			}
    }
    */

}