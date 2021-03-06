<?php

class ID_Acs_Model_Observer
{

	public function addButtonVoucher($observer)
	{
	    $container = $observer->getBlock();
	    $order = Mage::app()->getRequest()->getParams();

	    if( $container instanceof Mage_Adminhtml_Block_Sales_Order_View ) {
	    	$order_obj = Mage::getModel('sales/order')->load($order['order_id']);
	    	if( !$order_obj->isCanceled() && $order_obj->canShip() && (substr($this->order->getShippingMethod(), 0, 7) === 'id_acs_') ) {
		        $data = array(
		            'label'     => Mage::helper('acs')->__('Create Voucher'),
		            'class'     => 'go',
		            'onclick'   => 'setLocation(\''  . Mage::helper('adminhtml')->getUrl('*/acs/create', array('order' => $order['order_id'])) . '\')',
		        );
		        $container->addButton('create_voucher', $data);

		        /*
		         * Hide Ship and Invoice buttons
		         */
		        $container->removeButton('order_ship');
		        $container->removeButton('order_invoice');
		    }

		    if( !$order_obj->isCanceled() && $order_obj->getStatus() == 'complete' && (substr($this->order->getShippingMethod(), 0, 7) === 'id_acs_') ) {
		        $data = array(
		            'label'     => Mage::helper('acs')->__('Print Voucher'),
		            'class'     => 'go',
		            'onclick'   => 'setLocation(\''.Mage::helper('adminhtml')->getUrl('*/acs/reprintVoucher', array('order' => $order['order_id'])) . '\')',
		        );
		        $container->addButton('print_voucher', $data);

		        $data = array(
		            'label'     => Mage::helper('acs')->__('Delete Voucher'),
		            'class'     => 'go',
		            'onclick'	=> "confirmSetLocation('".Mage::helper('acs')->__('Are you sure you want to delete this voucher?')."', '".Mage::helper('adminhtml')->getUrl('*/acs/deleteVoucher', array('order' => $order['order_id']))."')"
		        );
		        $container->addButton('delete_voucher', $data);
		    }
	    }

	    return $this;
	}

	public function addActions($observer)
	{
		$block = $observer->getEvent()->getBlock();
	    if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction' && $block->getRequest()->getControllerName() == 'sales_order')
	    {
	      $block->addItem('createvouchers', array(
	        'label' => Mage::helper('acs')->__('Create Vouchers'),
	        'url' => Mage::app()->getStore()->getUrl('*/acs/massVouchers'),
	      ));

	      $block->addItem('printvouchers', array(
	        'label' => Mage::helper('acs')->__('Print Vouchers'),
	        'url' => Mage::app()->getStore()->getUrl('*/acs/massReprint'),
	      ));

	      $block->addItem('validateaddress', array(
	        'label' => Mage::helper('acs')->__('Validate Address'),
	        'url' => Mage::app()->getStore()->getUrl('*/acs/massValidate'),
	      ));
	    }

	  return $this;
	}

	public function saveCustomData($event)
	{
		//Mage::log('Save data: '.$event->getRequestModel()->getPost('field_custom_price'));
		$quote = $event->getSession()->getQuote();
		$quote->setData('field_custom_price', $event->getRequestModel()->getPost('field_custom_price'));

		return $this;
	}

}