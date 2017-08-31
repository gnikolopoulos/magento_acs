<?php

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
        
        $this->send_sms = Mage::getStoreConfig('acs/sms/send_sms');
		$this->sms_url = Mage::getStoreConfig('acs/sms/sms_url');
		$this->sms_user = Mage::getStoreConfig('acs/sms/sms_user');
		$this->sms_pass = Mage::getStoreConfig('acs/sms/sms_pass');
	}

    public function indexAction()
    {
      $this->init();

      $order_collection = Mage::getModel('sales/order')
					->getCollection()
					->addAttributeToSelect('*')
					->addAttributeToFilter('status', array('in' => array('complete')));

			foreach ($order_collection as $order) {
				$client = @new SoapClient("http://services.acscourier.net/ACSTracking-portlet/api/axis/Plugin_acsTracking_TrackingSummaryWithStatusService?wsdl");
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
					$this->sendDeniedEmail($order);
                    
                    if( $this->send_sms ) {
				        $this->sendSMS($order);
                    }
				} else {
					echo $order->getIncrementId() . ' Not Delivered<br />';
					// Other stuff...
				}
			}
    }

    private function sendDeniedEmail($order)
    {
        // Get order
        $storeId = Mage::app()->getStore()->getStoreId();
        Mage::log('Order for denied:'.$order->getIncrementId());
        if( $order->getStatus() == 'denied' ) {
            // Order has been denied, prepare email
            $previousStore = Mage::app()->getStore();
            Mage::app()->setCurrentStore($order->getStore()->getCode());
            Mage::getDesign()->setArea('frontend');

            $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('id_denied_order_email');
            $emailTemplateVariables['order'] = $order;
            $emailTemplateVariables['store'] = $order->getStore();
            $emailTemplate->getProcessedTemplate($emailTemplateVariables);
            $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email', $storeId));
            $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name', $storeId));
            $emailTemplate->send( $order->getCustomerEmail() ,'Fifth Element', $emailTemplateVariables);
            $emailTemplate->send( 'info@fifthelement.gr' ,'Fifth Element', $emailTemplateVariables);

            Mage::app()->setCurrentStore($previousStore->getCode());
        }

        return $this;
    }

    private function sendSMS($order)
    {
        $message = 'ΕΝΗΜΕΡΩΘΗΚΑΜΕ ΓΙΑ ΤΗΝ ΑΡΝΗΣΗ ΠΑΡΑΛΑΒΗΣ ΤΗΣ ΠΑΡΑΓΓΕΛΙΑΣ ΣΑΣ #'.$order->getIncrementId().'.ΣΑΣ ΕΧΕΙ ΣΤΑΛΕΙ EMAIL ΣΧΕΤΙΚΑ ΜΕ ΤΗΝ ΟΦΕΙΛΗ ΣΑΣ ΒΑΣΕΙ ΤΩΝ ΟΡΩΝ ΠΟΥ ΕΧΕΤΕ ΑΠΟΔΕΧΘΕΙ.';

        $phone = $order->getShippingAddress()->getTelephone();
        $fax = $order->getShippingAddress()->getFax();

        // Start procedure
        if( $order->getStatus() == 'denied' ) {
            if ( preg_match('#^69#', $phone) === 1 && strlen($phone) == 10 ) {
                // Is valid mobile
                $data = array(
                    'username'      => $this->sms_user,
                    'password'      => $this->sms_pass,
                    'destination'   => '30'.$phone,
                    'sender'        => '5th Element',
                    'message'       => $message,
                    'batchuserinfo' => 'OrderDenied',
                    'pricecat'      => 0
                );
                $response = file_get_contents( $this->sms_url.'?'.http_build_query($data) );

                if( preg_match('#^OK ID:[0-9]{1,}#', $response) === 1 ) {
                    return true;
                } else {
                    return false;
                }

            } elseif( preg_match('#^69#', $fax) === 1 && strlen($fax) == 10 ) {
                // Is valid mobile
                $data = array(
                            'username'      => $this->sms_user,
                            'password'      => $this->sms_pass,
                            'destination'   => '30'.$fax,
                            'sender'        => '5th Element',
                            'message'       => $message,
                            'batchuserinfo' => 'OrderDenied',
                            'pricecat'      => 0
                        );
                $response = file_get_contents( $this->sms_url.'?'.http_build_query($data) );

                if( preg_match('#^OK ID:[0-9]{1,}#', $response) === 1 ) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
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
						$client = @new SoapClient("http://services.acscourier.net/ACSTracking-portlet/api/axis/Plugin_acsTracking_TrackingSummaryWithStatusService?wsdl");
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
