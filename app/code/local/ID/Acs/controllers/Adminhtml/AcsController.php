<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mage::getModuleDir('', 'ID_Acs') . DS . 'lib' . DS .'EXTERNALLIB.php';

class ID_Acs_Adminhtml_AcsController extends Mage_Adminhtml_Controller_Action
{

	private $order;

	private $companyId;
	private $companyPass;
	private $username;
	private $password;
	private $sender;
	private $customerId;

	private $send_sms;
	private $sms_url;
	private $sms_user;
	private $sms_pass;

	private $storeId;
	private $error;

	private $massNumbers;

	/*
		Mage::getSingleton('core/session')->addSuccess('Success message');
		Mage::getSingleton('core/session')->addNotice('Notice message');
		Mage::getSingleton('core/session')->addError('Error message');
		// Admin only
		Mage::getSingleton('adminhtml/session')->addWarning('Warning message');

		try{
			/// ...
		} catch (Exception $e) {
			Mage::getSingleton('core/session')->addError('Error ' . $e->getMessage());
		}
	*/

	private function init()
	{
		$this->companyId = Mage::getStoreConfig('acs/login/company_id');
		$this->companyPass = Mage::getStoreConfig('acs/login/company_pass');
		$this->username = Mage::getStoreConfig('acs/login/username');
		$this->password = Mage::getStoreConfig('acs/login/password');
		$this->sender = Mage::getStoreConfig('acs/login/sender_name');
		$this->customerId = Mage::getStoreConfig('acs/login/customerId');

		$this->send_sms = Mage::getStoreConfig('acs/sms/send_sms');
		$this->sms_url = Mage::getStoreConfig('acs/sms/sms_url');
		$this->sms_user = Mage::getStoreConfig('acs/sms/sms_user');
		$this->sms_pass = Mage::getStoreConfig('acs/sms/sms_pass');

		$this->storeId = null;
		$this->error = false;
	}

	public function indexAction()
	{
    $this->_redirectReferer();
    Mage::getSingleton('adminhtml/session')->addNotice( $this->__('You cannot access this area directly') );
    return $this;
  }

	public function createAction($order = null)
	{
		$this->init();

		if( $this->getRequest()->getParam('order') ) {
			$this->order = Mage::getModel("sales/order")->load( $this->getRequest()->getParam('order') );
		} elseif( $this->getRequest()->getParam('order_ids') ) {
			$this->order = Mage::getModel("sales/order")->load( $order );
		} else {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addError( $this->__('Invalid action. You must select at least 1 order to create vouchers for') );
			return false;
		}

		// Get Order parameters
		if($this->order->canShip()) {
			$order_data = $this->order->getShippingAddress()->getData();
			$extras = array();
			if( $this->order->getPayment()->getMethodInstance()->getCode() == 'phoenix_cashondelivery' ) {
				// Αντικαταβολή
				if( $this->order->getFieldCustomPrice() !== NULL ) {
					$amount = $this->order->getFieldCustomPrice();
				} else {
					$amount = $this->order->getGrandTotal();
				}
				$method = 'Μ'; // Ελληνικά
				$extras[] = 'ΑΝ'; // Ελληνικά
			} else {
				// Άλλο
				$amount = 0;
				$method = '';
			}

			// Έλεγχος για παράδοση Reception
			if( $this->order->getShippingDescription() == 'Αποστολή στη διεύθυνσή μου - Παραλαβή από τοπικό κατάστημα ACS' || $this->order->getShippingMethod() == 'id_acs_reception' ) {
				$extras[] = 'ΡΣ'; // Ελληνικά
			}

			// Έλεγχος για παράδοση Σαββατο
			if( $this->order->getShippingMethod() == 'id_acs_saturday' ) {
				$extras[] = '5Σ'; // Ελληνικά
			}

			// Έλεγχος για χέρι με χέρι
			if( $this->order->getShippingMethod() == 'id_acs_exchange' ) {
				$extras[] = 'ΔΔ'; // Ελληνικά
			}

			// Έλεγχος ΔΠ
			if( $this->checkAddr() == 'ΔΠ' ) {
				$extras[] = 'ΔΠ'; // Ελληνικά
			}

			// If there is not any errors
			if( !$this->error ) {
				// Prepare SOAP Client
				try {
					$client = @new SoapClient("https://services.acscourier.net/ACSCreateVoucher-portlet/api/axis/Plugin_ACSCreateVoucher_ACSVoucherService?wsdl");
					$params = array(
								'companyId'					=> $this->companyId,
								'companyPass'				=> $this->companyPass,
								'username'					=> $this->username,
								'password'					=> $this->password,
								'diakDateParal' 			=> date('Y-m-d'),
								'diakApostoleas'			=> $this->sender,
								'diakParalhpthsOnoma'		=> $this->order->getShippingAddress()->getName(),
								'diakParalhpthsDieth'		=> $order_data['street'],
								'acDiakParalhpthsDiethAr'	=> ( filter_var($order_data['street'], FILTER_SANITIZE_NUMBER_INT) ? filter_var($order_data['street'], FILTER_SANITIZE_NUMBER_INT) : '0'),
								'acDiakParalhpthsDiethPer'	=> $order_data['city'], // Περιοχή
								'diakParalhpthsThlef'		=> ($order_data['fax'] ? $order_data['fax'] : $order_data['telephone']), // Τηλέφωνο
								'diakParalhpthsTk'			=> $order_data['postcode'], // ΤΚ
								'stationIdDest'				=> ($this->storeId ? $this->storeId : null),
								'branchIdDest'				=> 1,
								'diakTemaxia'				=> 1,
								'diakVaros'					=> 0.5,
								'diakXrewsh'				=> 2,
								'diakWraMexri'				=> null,
								'diakAntikatPoso'			=> floatval($amount), // Ποσό
								'diakTroposPlAntikat'		=> $method, // Τρόπος
								'hostName'					=> 'eShop',
								'diakNotes'					=> ($this->order->getCustomerNote() ? $this->order->getCustomerNote() : ''),
								'diakCountry'				=> $order_data['country_id'],
								'diakcFiller'				=> $this->order->getIncrementId(), // Αρ. Παραγγελίας
								'acDiakStoixs'				=> implode(',', $extras), // ΑΝ = Αντικαταβολή, ΡΣ = Παράδοση Reception
								'customerId'				=> $this->customerId,
								'diakParalhpthsCell'		=> $order_data['telephone'], // Κινητό
								'diakParalhpthsOrofos'		=> null,
								'diakParalhpthsCompany'		=> ($order_data['company'] ? $order_data['company'] : null),
								'withReturn'				=> 0,
								'diakcCompCus'				=> '',
								'specialDir'				=> '',
							  );
					$response = @$client->__soapCall("createVoucher", $params);
					//$response->no_pod

					// Proceed if no errors
					if( !$response->errorMsg ) {
						$this->createInvoice();
						$this->createShipment($response->no_pod);
						if( $this->send_sms ) {
							//Mage::log('SMS sending triggered');
							if( $this->sendSMS($response->no_pod) ) {
								$extra = $this->__('SMS Notification Sent');
							} else {
								$extra = $this->__('SMS Notification not sent');
							}
						}

						// Add voucher to Vouchers table
						$voucher = array(
							'created_at'	=> date('d-m-Y H:i:s'),
							'pod'			=> $response->no_pod,
							'status'		=> 'Active',
						);
						Mage::getModel('id_acs/voucher')->setData($voucher)->save();

						$this->_redirectReferer();
						Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Created voucher for order #%s.Voucher: %s'.' '.$extra, $this->order->getIncrementId(), $response->no_pod) );
					} else {
						$this->_redirectReferer();
						Mage::getSingleton('adminhtml/session')->addError( $this->__('Could not create voucher for order #%s. Error: %s', $this->order->getIncrementId(), $response->errorMsg) );
					}

				} catch(SoapFault $fault) {
					trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);

					$this->_redirectReferer();
					Mage::getSingleton('adminhtml/session')->addError( $this->__('Could not create voucher for order #%s', $this->order->getIncrementId()) );
				}
			} else {
				$this->_redirectReferer();
				Mage::getSingleton('adminhtml/session')->addError( $this->__('Possible wrong address for order #%s', $this->order->getIncrementId()) );
			}
		} else {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addError( $this->__('Order #%s cannot be shipped or has already been shipped', $this->order->getIncrementId()) );
		}
		return $this;
	}

	public function massVouchersAction()
	{
		$this->order_arr = $this->getRequest()->getParam('order_ids');
		foreach($this->order_arr as $_order) {
			$this->createAction($_order);
		}
		//$this->_redirectReferer();
		//Mage::getSingleton('adminhtml/session')->addWarning('IDs:'.implode(',', (array)$this->getRequest()->getParam('order_ids')));
		return $this;
	}

	public function massValidateAction()
	{
		$this->init();

		$this->order_arr = $this->getRequest()->getParam('order_ids');
		$problems = array();
		foreach($this->order_arr as $_order) {
			$result = $this->checkAddr($_order);
			if( !$this->checkAddr($_order) ) {
				$problems[] = $this->order->getIncrementId();
			}
		}

		if( !empty($problems)) {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addError('Possible address problems with order(s):'.implode(',', $problems ));
		} else {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addSuccess('No problems detected');
		}
		return $this;
	}

	private function checkAddr($orderId = null)
	{
		if($orderId == null) {
			$order_data = $this->order->getShippingAddress()->getData();
			$this->storeId = null;
			try {
				$client = @new SoapClient("https://services.acscourier.net/ACS-AddressValidationNew-portlet/api/axis/Plugin_ACSAddressValidation_ACSAreaService?wsdl");
				$params = array(
					'companyId'			=> $this->companyId,
					'companyPass'		=> $this->companyPass,
					'username'			=> $this->username,
					'password'			=> $this->password,
					'zip_code'			=> $order_data['postcode'],
					'only_dp'			=> false
				  );
				$response = @$client->__soapCall("findByZipCode", $params);
				if(!empty($response)) {
					$this->storeId = $response{0}->station_id;
					return $response{0}->dp_dx;
				} else {
					$this->error = true;
					return false;
				}
			} catch(SoapFault $fault) {
				trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
				return false;
			}
		} else {
			$this->order = Mage::getModel("sales/order")->load( $orderId );
			$order_data = $this->order->getShippingAddress()->getData();
			try {
				$client = @new SoapClient("https://services.acscourier.net/ACS-AddressValidationNew-portlet/api/axis/Plugin_ACSAddressValidation_ACSAreaService?wsdl");
				$params = array(
					'companyId'			=> $this->companyId,
					'companyPass'		=> $this->companyPass,
					'username'			=> $this->username,
					'password'			=> $this->password,
					'zip_code'			=> $order_data['postcode'],
					'only_dp'			=> false
				  );
				$response = @$client->__soapCall("findByZipCode", $params);
				if(!empty($response)) {
					return true;
				} else {
					return false;
				}
			} catch(SoapFault $fault) {
				trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
				return false;
			}
		}
	}

	private function createShipment($voucher)
	{
		if($this->order->canShip())
		{
			$customerEmailComments = '';
			// Create shipment and add tracking number
		    $shipment = Mage::getModel('sales/service_order', $this->order)->prepareShipment(Mage::helper('acs/orders')->_getItemQtys($this->order));

		    if( $shipment )
		    {
			    $arrTracking = array(
	                'carrier_code' => 'custom',
	                'title' => 'ACS Courier',
	                'number' => $voucher,
	            );
			    $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
	            $shipment->addTrack($track);
	            $shipment->register();
	            Mage::helper('acs/orders')->_saveShipment($shipment, $this->order, $customerEmailComments);
	            Mage::helper('acs/orders')->_saveOrder($this->order);

                if( !$shipment->getEmailSent() )
                {
                	// Send Tracking data
                    $shipment->sendEmail(true);
                    $shipment->setEmailSent(true);
                    $shipment->save();
                }
                return true;
            }
		} else {
			return false;
		}
	}

	private function createInvoice()
	{
		if( !$this->order->hasInvoices() && $this->order->canInvoice() ) {
            // Prepare
            $invoice = Mage::getModel('sales/service_order', $this->order)->prepareInvoice();
            // Check that are products to be invoiced
            if( $invoice->getTotalQty() ) {
                // CAPTURE_OFFLINE since CC and PayPal already have invoices
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')
                                    ->addObject($invoice)
                                    ->addObject($invoice->getOrder());
                $transactionSave->save();

            }
        }
	}

	private function sendSMS($tracking_id) {
		$phone = $this->order->getBillingAddress()->getTelephone();
		$fax = $this->order->getBillingAddress()->getFax();

		//Mage::log('Phone: '.$phone);
		//Mage::log('Fax: '.$fax);

		if ( preg_match('#^69#', $phone) === 1 && strlen($phone) == 10 ) {
		    // Is valid mobile
		    if( $this->sms($phone, $tracking_id) ) {
		    	return true;
		    } else {
		    	return false;
		    }
		} elseif( preg_match('#^69#', $fax) === 1 && strlen($fax) == 10 ) {
			// Is valid mobile
		    if( $this->sms($fax, $tracking_id) ) {
		    	return true;
		    } else {
		    	return false;
		    }
		} else {
			return false;
		}
		return $this;
	}

	private function sms($number,$voucher)
	{
		//Mage::log('SMS final call triggered');

		$message = 'Η ΠΑΡΑΓΓΕΛΙΑ ΣΑΣ ΜΕ ΑΡΙΘΜΟ #'.$this->order->getIncrementId().' ΑΠΕΣΤΑΛΗ ΜΕ ACS COURIER.Ο ΑΡΙΘΜΟΣ ΑΠΟΣΤΟΛΗΣ ΕΙΝΑΙ '.$voucher.'. ΕΥΧΑΡΙΣΤΟΥΜΕ.';
		$data = array(
					'username'		=> $this->sms_user,
					'password'		=> $this->sms_pass,
					'destination'	=> '30'.$number,
					'sender'		=> '5th Element',
					'message'		=> $message,
					'batchuserinfo' => 'TrackingInfoSMS',
					'pricecat'		=> 0
				);
		$response = file_get_contents( $this->sms_url.'?'.http_build_query($data) );
		if( preg_match('#^OK ID:[0-9]{1,}#', $response) === 1 ) {
			$this->order->addStatusHistoryComment('Tracking Number SMS Sent, '.date('d-m-Y H:i:s'));
			$this->order->save();
			return true;
		} else {
			return false;
		}
		return $this;
	}

	public function unprintedAction()
	{
		$this->init();

		try {
			$client = @new SoapClient("https://services.acscourier.net/ACSReceiptsList-portlet/api/axis/Plugin_ACSReceiptsList_ACSUnprintedPodsService?wsdl");
			$params = array(
						'companyId'			=> $this->companyId,
						'companyPass'		=> $this->companyPass,
						'username'			=> $this->username,
						'password'			=> $this->password,
						'dateParal'			=> date('Y-m-d'),
						'myData'			=> '0',
					  );
			$response = @$client->__soapCall("getUnprintedPods", $params);

			$pods = array();
			foreach ($response as $pod) {
				$pods[] = $pod->no_pod;
			}
			if( count($pods) > 0 ) {
				$sorted_pods = sort($pods);
				$this->getResponse()->setRedirect('http://acs-eud.acscourier.gr/Eshops/GetVoucher.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&voucherno='.implode("|", $pods).'&PrintType=2');
			} else {
				$this->_redirectReferer();
				Mage::getSingleton('adminhtml/session')->addWarning( $this->__('No Vouchers to print') );
			}
			return true;
		} catch(SoapFault $fault) {
			trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
			return false;
		}
		return $this;
	}

	public function getlistAction()
	{
		$this->init();

		try {
			$client = @new SoapClient("https://services.acscourier.net/ACSReceiptsList-portlet/axis/Plugin_ACSReceiptsList_ACSReceiptsListService?wsdl");
			$params = array(
						'companyId'			=> $this->companyId,
						'companyPass'		=> $this->companyPass,
						'username'			=> $this->username,
						'password'			=> $this->password,
						'dateParal'			=> date('Y-m-d'),
						'myData'				=> '0',
					  );
			$response = @$client->__soapCall("createACSReceiptsList", $params);

			if( !$response->error ) {
				if( $response->massNumber ) {
					// Add massNumber to id_acs_list table
					$list = array(
						'created_at'	=> date('d-m-Y H:i:s'),
						'massnumber'	=> $response->massNumber,
					);
					Mage::getModel('id_acs/list')->setData($list)->save();

					$this->getResponse()->setRedirect( 'http://acs-eud.acscourier.gr/Eshops/getlist.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&MassNumber='.$response->massNumber.'&DateParal='.date("Y-m-d") );
				} else {
					if( $this->getNumbers() ) {
						$list_final = new Zend_Pdf();
						if( $this->massNumbers ) {
							foreach ($this->massNumbers as $n) {
								//$this->_redirectReferer();
								$url = 'http://acs-eud.acscourier.gr/Eshops/getlist.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&MassNumber='.$n.'&DateParal='.date("Y-m-d");
								$pdf = Zend_Pdf::parse(file_get_contents($url));
								foreach($pdf->pages as $page){
								  $clonedPage = clone $page;
								  $list_final->pages[] = $clonedPage;
								}
								unset($clonedPage);
							}
							header("Content-Type: application/pdf");
	    					header("Content-Disposition: inline; filename=list.pdf");
	    					echo $list_final->render();
		    			} else {
		    				$this->_redirectReferer();
								Mage::getSingleton('adminhtml/session')->addWarning( $this->__('No Receipt lists to print') );
		    			}
					}
				}
				return true;
			} else {
				$this->_redirectReferer();
				Mage::getSingleton('adminhtml/session')->addError( $response->error );
				return false;
			}
		} catch(SoapFault $fault) {
			trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
			return false;
		}
		return $this;
	}

	private function getNumbers( $date = null )
	{
		if($date) {
			$dateParal = $date;
		} else {
			$dateParal = date('Y-m-d');
		}
		
		try {
			$client = @new SoapClient("https://services.acscourier.net/ACSReceiptsList-portlet/api/axis/Plugin_ACSReceiptsList_MassNumberEntryService?wsdl");
			$params = array(
						'companyId'			=> $this->companyId,
						'companyPass'		=> $this->companyPass,
						'username'			=> $this->username,
						'password'			=> $this->password,
						'dateParal'			=> $dateParal,
						'lang'				=> 'GR',
					  );
			$response = @$client->__soapCall("getMassNumbers", $params);
			$numbers = array();

			foreach( $response as $item ) {
				$numbers[] = $item->massNumber;
			}
			$this->massNumbers = $numbers;
			return true;
		} catch(SoapFault $fault) {
			trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
			return false;
		}
		return $this;
	}

	public function reprintVoucherAction($order = null)
	{
		$this->init();

		if( $this->getRequest()->getParam('order') ) {
			$this->order = Mage::getModel("sales/order")->load( $this->getRequest()->getParam('order') );
			
			$this->getResponse()->setRedirect('http://acs-eud.acscourier.gr/Eshops/GetVoucher.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&voucherno='.$this->order->getTracksCollection()->getFirstItem()->getNumber().'&PrintType=2');
		} elseif( $this->getRequest()->getParam('order_ids') ) {
			$this->order = Mage::getModel("sales/order")->load( $order );
			return $this->order->getTracksCollection()->getFirstItem()->getNumber();
		} elseif( $this->getRequest()->getParam('pod') ) {
			$this->getResponse()->setRedirect('http://acs-eud.acscourier.gr/Eshops/GetVoucher.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&voucherno='.$this->getRequest()->getParam('pod').'&PrintType=2');
		} else {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addError( $this->__('Invalid action. You must select at least 1 order to print vouchers from') );
			return false;
		}
	}

	public function massReprintAction()
	{
		$this->order_arr = $this->getRequest()->getParam('order_ids');
		$pods = array();
		foreach($this->order_arr as $_order) {
			$pods[] = $this->reprintVoucherAction($_order);
		}
		$this->getResponse()->setRedirect('http://acs-eud.acscourier.gr/Eshops/GetVoucher.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&voucherno='.implode("|", $pods).'&PrintType=2');
	}

	public function deleteVoucherAction($order = null)
	{
		if( $this->getRequest()->getParam('order') ) {
			$this->order = Mage::getModel("sales/order")->load( $this->getRequest()->getParam('order') );

			try {
				$client = @new SoapClient("https://services.acscourier.net/ACSDeleteVoucher-portlet/axis/Plugin_DeleteVoucher_ACSDeleteVoucherService?wsdl");
				$params = array(
							'companyId'			=> $this->companyId,
							'companyPass'		=> $this->companyPass,
							'username'			=> $this->username,
							'password'			=> $this->password,
							'noPod'				=> $this->order->getTracksCollection()->getFirstItem()->getNumber(),
						  );
				$response = @$client->__soapCall("deleteACSDeleteVoucher", $params);

				if( !$response->error ) {

					// Delete Shipment
					if( $this->order->hasShipments() ) {
						//delete shipment
						$shipments = $this->order->getShipmentsCollection();
						foreach ($shipments as $shipment){
						    $shipment->delete();
						}

						// Reset item shipment qty
						// see Mage_Sales_Model_Order_Item::getSimpleQtyToShip()
						$items = $this->order->getAllVisibleItems();
						foreach($items as $i){
						   $i->setQtyShipped(0);
						   $i->save();
						}

						//Reset order state
						$this->order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Undo Shipment');
						$this->order->save();
					}

					$this->_redirectReferer();
					Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Voucher %s deleted', $this->order->getTracksCollection()->getFirstItem()->getNumber()) );
					return true;
				} else {
					$this->_redirectReferer();
					Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Could not delete voucher %s', $this->order->getTracksCollection()->getFirstItem()->getNumber()) );
					return false;
				}
			} catch(SoapFault $fault) {
				trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
				return false;
			}

			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Test: Deleted voucher %s', $this->order->getTracksCollection()->getFirstItem()->getNumber()) );
		} elseif( $this->getRequest()->getParam('pod') ) {
			try {
				$client = @new SoapClient("https://services.acscourier.net/ACSDeleteVoucher-portlet/axis/Plugin_DeleteVoucher_ACSDeleteVoucherService?wsdl");
				$params = array(
							'companyId'			=> $this->companyId,
							'companyPass'		=> $this->companyPass,
							'username'			=> $this->username,
							'password'			=> $this->password,
							'noPod'				=> $this->getRequest()->getParam('pod'),
						  );
				$response = @$client->__soapCall("deleteACSDeleteVoucher", $params);

				if( !$response->error ) {

					$data = array(
						'status' => 'Cancelled',
					);
					$voucher = Mage::getModel('id_acs/voucher')
								->load($this->getRequest()->getParam('pod'), 'pod')
								->addData($data)
								->save();

					$this->_redirectReferer();
					Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Voucher %s deleted', $this->getRequest()->getParam('pod')) );
					return true;
				} else {
					$this->_redirectReferer();
					Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Could not delete voucher %s. %s', $this->getRequest()->getParam('pod'), $response->error) );
					return false;
				}
			} catch(SoapFault $fault) {
				trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
				return false;
			}
		} else {
			$this->_redirectReferer();
			Mage::getSingleton('adminhtml/session')->addError( $this->__('Invalid action') );
			return false;
		}
	}

	public function listsAction()
	{
		$this->_title($this->__('Previous Receipt Lists'));
        $this->loadLayout();
        $this->_setActiveMenu('acs/list');
        $this->_addContent($this->getLayout()->createBlock('id_acs/adminhtml_list'));
        $this->renderLayout();
	}

	public function vouchersAction()
	{
		$this->_title($this->__('Vouchers'));
        $this->loadLayout();
        $this->_setActiveMenu('acs/voucher');
        $this->_addContent($this->getLayout()->createBlock('id_acs/adminhtml_voucher'));
        $this->renderLayout();
	}

	public function antikatavolesAction()
	{
		$this->_title($this->__('Antikatavoles'));
        $this->loadLayout();
        $this->_setActiveMenu('acs/antikatavoles');
        $this->_addContent($this->getLayout()->createBlock('id_acs/adminhtml_antikatavoles'));
        $this->renderLayout();
	}

	public function grid_voucherAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('id_acs/adminhtml_voucher_grid')->toHtml()
        );
    }

	public function grid_listAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('id_acs/adminhtml_list_grid')->toHtml()
        );
    }

    public function grid_antikatavolesAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('id_acs/adminhtml_antikatavoles_grid')->toHtml()
        );
    }

    public function printListAction()
    {
    	$this->init();

    	$list = Mage::getModel('id_acs/list')->load($this->getRequest()->getParam('massnumber'), 'massnumber');

    	$this->getResponse()->setRedirect( 'http://acs-eud.acscourier.gr/Eshops/getlist.aspx?MainID='.$this->companyId.'&MainPass='.$this->companyPass.'&UserID='.$this->username.'&UserPass='.$this->password.'&MassNumber='.$this->getRequest()->getParam('massnumber').'&DateParal='.date('Y-m-d', strtotime($list->created_at)) );
    }

    public function checkordersAction()
    {
		Mage::helper('acs/antikatavoles')->_check();
		$this->_redirect('*/acs/antikatavoles');
    }

    public function uploadxlsAction()
    {
    	if ($data = $this->getRequest()->getParams()) {
            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
                try {
                    $uploader = new Varien_File_Uploader('filename');
                    $uploader->setAllowedExtensions(array('xml', 'XML', 'xls', 'XLS'));
                    $uploader->setAllowRenameFiles(false);
                    $uploader->setFilesDispersion(false);
                    $path = Mage::getBaseDir('tmp') . DS . 'antikatavoles';
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $uploader->save($path, $_FILES['filename']['name']);
                    $filename = $uploader->getUploadedFileName();
                    Mage::helper('acs/antikatavoles')->_processFile($filename);
					Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('File %s uploaded', $filename) );
                } catch (Exception $e) {
                    Mage::log( $e->getMessage() );
                }
            }
        }
        $this->_redirect('*/acs/antikatavoles');
    }

    public function resetAntikatavoliAction()
    {
    	Mage::helper('acs/antikatavoles')->_reset($this->getRequest()->getParam('pod'));
		$this->_redirect('*/acs/antikatavoles');
    }

}