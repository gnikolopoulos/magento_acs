<?php

class ID_Acs_Model_Carrier extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'id_acs';

    public function collectRates( Mage_Shipping_Model_Rate_Request $request )
    {
        if (!$this->getConfigData('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');

        //$request->getPackageValue(); //Get total order value
        //$request->getPackageValueWithDiscount(); //Get order total after discount

        $result->append($this->_getStandardShippingRate($request));
        $result->append($this->_getReceptionShippingRate($request));
        if( Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml' ) {
            $result->append($this->_getReturnShippingRate($request));
            $result->append($this->_getFreeShippingRate($request));
            //$result->append($this->_getSaturdayShippingRate($request));
            //$result->append($this->_getExchangeShippingRate($request));
        }

        return $result;
    }

    protected function _getStandardShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('standard');
        $rate->setMethodTitle($this->getConfigData('label_standard'));

        // TODO: First check if products include skis etc
        if ($data->getAllItems()) {
            foreach ($data->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if (!$child->getProduct()->isVirtual()) {
                            $product_id = $child->getProductId();
                            $productObj = Mage::getModel('catalog/product')->load($product_id);
                            if( $productObj->getData('product_category') == '841' ||
                                $productObj->getData('product_category') == '846' ||
                                $productObj->getData('product_category') == '1027' ||
                                $productObj->getData('product_category') == '1083' ||
                                $productObj->getData('product_category') == '1084' ) {
                                $rate->setPrice( $this->getConfigData('price-large') );
                            } elseif( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
                                $rate->setPrice(0);
                            } else {
                                $rate->setPrice($this->getConfigData('price'));
                            }
                        }
                    }
                } else {
                    $product_id = $item->getProductId();
                    $productObj = Mage::getModel('catalog/product')->load($product_id);
                    if( $productObj->getData('product_category') == '841' ||
                        $productObj->getData('product_category') == '846' ||
                        $productObj->getData('product_category') == '1027' ||
                        $productObj->getData('product_category') == '1083' ||
                        $productObj->getData('product_category') == '1084' ) {
                        $rate->setPrice( $this->getConfigData('price-large') );
                    } elseif( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
                        $rate->setPrice(0);
                    } else {
                        $rate->setPrice($this->getConfigData('price'));
                    }
                }
            }
        }

        /*
        if( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
            $rate->setPrice(0);
        } else {
            $rate->setPrice($this->getConfigData('price'));
        }
        */
        $rate->setCost($this->getConfigData('cost'));

        return $rate;
    }

    protected function _getReceptionShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('reception');
        $rate->setMethodTitle($this->getConfigData('label_reception'));

        if ($data->getAllItems()) {
            foreach ($data->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if (!$child->getProduct()->isVirtual()) {
                            $product_id = $child->getProductId();
                            $productObj = Mage::getModel('catalog/product')->load($product_id);
                            if( $productObj->getData('product_category') == '841' ||
                                $productObj->getData('product_category') == '846' ||
                                $productObj->getData('product_category') == '1027' ||
                                $productObj->getData('product_category') == '1083' ||
                                $productObj->getData('product_category') == '1084' ) {
                                $rate->setPrice( $this->getConfigData('price-large') );
                            } elseif( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
                                $rate->setPrice(0);
                            } else {
                                $rate->setPrice($this->getConfigData('price'));
                            }
                        }
                    }
                } else {
                    $product_id = $item->getProductId();
                    $productObj = Mage::getModel('catalog/product')->load($product_id);
                    if( $productObj->getData('product_category') == '841' ||
                        $productObj->getData('product_category') == '846' ||
                        $productObj->getData('product_category') == '1027' ||
                        $productObj->getData('product_category') == '1083' ||
                        $productObj->getData('product_category') == '1084' ) {
                        $rate->setPrice( $this->getConfigData('price-large') );
                    } elseif( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
                        $rate->setPrice(0);
                    } else {
                        $rate->setPrice($this->getConfigData('price'));
                    }
                }
            }
        }

        /*
        if( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
            $rate->setPrice(0);
        } else {
            $rate->setPrice($this->getConfigData('price'));
        }
        */
        $rate->setCost($this->getConfigData('cost'));

        return $rate;
    }

    protected function _getReturnShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('return');
        $rate->setMethodTitle($this->getConfigData('label_return'));

        $rate->setPrice($this->getConfigData('return_price'));
        $rate->setCost($this->getConfigData('cost'));

        return $rate;
    }

    protected function _getFreeShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('free');
        $rate->setMethodTitle($this->getConfigData('label_free'));

        $rate->setPrice(0);
        $rate->setCost(0);

        return $rate;
    }

    protected function _getSaturdayShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('saturday');
        $rate->setMethodTitle($this->getConfigData('label_saturday'));

        $rate->setPrice($this->getConfigData('saturday_price'));
        $rate->setCost($this->getConfigData('cost'));

        return $rate;
    }

    protected function _getExchangeShippingRate($data)
    {
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('exchange');
        $rate->setMethodTitle($this->getConfigData('label_exchange'));

        if( $data->getPackageValueWithDiscount() >= floatval($this->getConfigData('free')) ) {
            $rate->setPrice(0);
        } else {
            $rate->setPrice($this->getConfigData('price'));
        }
        $rate->setCost($this->getConfigData('cost'));

        return $rate;
    }

    public function getAllowedMethods()
    {
        return array(
            'standard' => $this->getConfigData('label_standard'),
            'reception' => $this->getConfigData('label_reception'),
            'return' => $this->getConfigData('label_return'),
            'free' => $this->getConfigData('label_free'),
            //'saturday' => $this->getConfigData('label_saturday'),
            //'exchange' => $this->getConfigData('label_exchange'),
        );
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('https://www.acscourier.net/el/my-shipments-status?p_p_id=ACSCustomersAreaTrackTrace_WAR_ACSCustomersAreaportlet&p_p_lifecycle=1&p_p_state=normal&p_p_mode=view&p_p_col_id=column-4&p_p_col_pos=1&p_p_col_count=2&_ACSCustomersAreaTrackTrace_WAR_ACSCustomersAreaportlet_javax.portlet.action=trackTrace&generalCode=' . $tracking)
            ->setTracking($tracking)
            ->setCarrierTitle($this->getConfigData('admin_title'));
        return $track;
    }

}