<?php

require Mage::getModuleDir('', 'ID_Acs') . '/lib/PHPExcel.php';

class ID_Acs_Helper_Antikatavoles extends Mage_Core_Helper_Abstract
{
	private $XLS;
	private $rows;

	public function _processFile($filename)
    {
    	$this->XLS = new PHPExcel();
		$this->readXLS($filename);

		$this->rows = $this->XLS->getSheet(0)->getHighestDataRow();

		for( $i = 3; $i < $this->rows - 1; $i++ ) {
			/*
			Columns:
				B: Voucher
				D: Customer Name
				E: Date sent
				F: Date delivered
				G: Amount
				H: Order #
			*/
			$order = Mage::getModel('sales/order')->loadByIncrementId( $this->XLS->getSheet(0)->getCell('H'.$i)->getValue() );
			if( $order->getId() ) {

				$date = date_create_from_format('d/m/Y', $this->XLS->getSheet(0)->getCell('F'.$i)->getValue());
				// Check total
				if( $order->getGrandTotal() == (float)str_replace(',', '.', $this->XLS->getSheet(0)->getCell('G'.$i)->getValue()) ) {
					$status = 'OK';
				} elseif( $order->getFieldCustomPrice() == (float)str_replace(',', '.', $this->XLS->getSheet(0)->getCell('G'.$i)->getValue()) ) {
					$status = 'OK';
				} else {
					$status = 'Διαφορά ποσού';
				}

				// Check if pod exists before adding
				if( !Mage::getModel('id_acs/antikatavoles')->load( str_replace('#', '', $this->XLS->getSheet(0)->getCell('B'.$i)->getValue()), 'pod' )->getEntityId() ) {
					$data = array(
						'pod'			=> $this->XLS->getSheet(0)->getCell('B'.$i)->getValue(),
						'customer_name' => $this->XLS->getSheet(0)->getCell('D'.$i)->getValue(),
						'order' 		=> str_replace('#', '', $this->XLS->getSheet(0)->getCell('H'.$i)->getValue()),
						'value' 		=> (float)str_replace(',', '.', $this->XLS->getSheet(0)->getCell('G'.$i)->getValue()),
						'date'			=> $date->getTimestamp(),
						'status'		=> $status,
					);
					Mage::getModel('id_acs/antikatavoles')->setData($data)->save();
				} else {
					Mage::getSingleton('adminhtml/session')->addError( $this->__('Skipping Voucher %s since it already exists', $this->XLS->getSheet(0)->getCell('B'.$i)->getValue()) );
				}

			}
		}

    	return true;
    }

    private function readXLS($file)
	{
		if (!file_exists( Mage::getBaseDir('tmp'). DS . 'antikatavoles' . DS . $file )) {
			exit("File does not exist." . EOL);
		} else {
			try {
			    $this->XLS = PHPExcel_IOFactory::load( Mage::getBaseDir('tmp'). DS . 'antikatavoles' . DS . $file );
			} catch(Exception $e) {
			    die('Error loading file "'.$e->getMessage());
			}
		}
	}

	public function _check()
	{
		// Get orders that have been completed at least 1 day before and have no antikatavoles yet
		$orders = $this->getOrders();

		Mage::getSingleton('adminhtml/session')->addSuccess( 'Orders: '.count($orders) );
	}

	private function getExisting()
	{
		$data = Mage::getModel('id_acs/antikatavoles')->getCollection()->addFieldToSelect('order');
		return $data;
	}

	private function getOrders()
	{
		$existing = $this->getExisting();

		$data = Mage::getModel('sales/order')->getCollection()
				->join( array('payment' => 'sales/order_payment'), 'main_table.entity_id=payment.parent_id', array('payment_method' => 'payment.method') )
			    ->addAttributeToFilter('main_table.created_at', array( 'to'=>date("Y-m-d", strtotime("yesterday")) ))
				->addAttributeToFilter('main_table.status', array( 'in' => array('delivered') ))
				->addAttributeToFilter('main_table.increment_id', array( 'nin' => $existing->getColumnValues('order') ))
				->addAttributeToFilter('main_table.shipping_method', array( 'in' => array('id_acs_standand','id_acs_return','id_acs_reception') ))
				->addAttributeToFilter('payment.method', array( 'in' => array('phoenix_cashondelivery')) );
		return $data;
	}

	public function _reset($pod)
	{
		$antikatavoli = Mage::getModel('id_acs/antikatavoles')->load($pod, 'pod');
		if( $antikatavoli ) {
			$data = array(
				'status' => 'OK',
			);
			if( $antikatavoli->addData($data)->save() ) {
				Mage::getSingleton('adminhtml/session')->addSuccess( $this->__('Updated') );
			} else {
				Mage::getSingleton('adminhtml/session')->addError( $this->__('Could not update Record') );
			}
		} else {
			Mage::getSingleton('adminhtml/session')->addError( $this->__('Record not found') );
		}
	}
}