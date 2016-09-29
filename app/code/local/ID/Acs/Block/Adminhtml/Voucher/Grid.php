<?php

class ID_Acs_Block_Adminhtml_Voucher_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * constructor
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('id_acs_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        //$this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     *
     * @access protected
     * @return ID_Acs_Block_Adminhtml_Voucher_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('id_acs/voucher')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return ID_Acs_Block_Adminhtml_Voucher_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            array(
                'header' => Mage::helper('acs')->__('ID'),
                'index'  => 'entity_id',
                'width'  => '20px',
                'type'   => 'number'
            )
        );
        $this->addColumn(
            'pod',
            array(
                'header'    => Mage::helper('acs')->__('POD No'),
                'align'     => 'left',
                'width'     => '200px',
                'index'     => 'pod',
            )
        );
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('acs')->__('Created at'),
                'index'  => 'created_at',
                'width'  => '150px',
                'type'   => 'datetime',
            )
        );
        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('acs')->__('Status'),
                'index'  => 'status',
                'width'  => '50px',
            )
        );
        $this->addColumn(
            'action',
            array(
                'header'  =>  Mage::helper('acs')->__('Action'),
                'width'   => '100px',
                'type'    => 'action',
                'getter'  => 'getPod',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('acs')->__('Print Voucher'),
                        'url'     => array('base'=> '*/*/reprintVoucher'),
                        'field'   => 'pod',
                        'target'  => '_blank',
                    ),
                    array(
                        'caption' => Mage::helper('acs')->__('Delete Voucher'),
                        'url'     => array('base'=> '*/*/deleteVoucher'),
                        'field'   => 'pod',
                        'target'  => '_blank',
                    )
                ),
                'filter'    => false,
                'is_system' => true,
                'sortable'  => false,
            )
        );
        $this->addExportType('*/*/exportCsv', Mage::helper('acs')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('acs')->__('Excel'));
        $this->addExportType('*/*/exportXml', Mage::helper('acs')->__('XML'));
        return parent::_prepareColumns();
    }

    /**
     * get the row url
     *
     * @access public
     * @param ID_Acs_Model_Voucher
     * @return string
     */
    public function getRowUrl($row)
    {
        //return $this->getUrl('*/*/printVoucher', array('massnumber' => $row->getMassnumber()));
        return false;
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid_voucher', array('_current'=>true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return ID_Acs_Block_Adminhtml_Voucher_Grid
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
}
