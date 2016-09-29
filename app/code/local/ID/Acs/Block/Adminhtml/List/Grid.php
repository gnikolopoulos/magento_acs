<?php

class ID_Acs_Block_Adminhtml_List_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
     * @return ID_Acs_Block_Adminhtml_List_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('id_acs/list')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare grid collection
     *
     * @access protected
     * @return ID_Acs_Block_Adminhtml_List_Grid
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
            'massnumber',
            array(
                'header'    => Mage::helper('acs')->__('Mass Number'),
                'align'     => 'left',
                'width'     => '200px',
                'index'     => 'massnumber',
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
            'action',
            array(
                'header'  =>  Mage::helper('acs')->__('Action'),
                'width'   => '100px',
                'type'    => 'action',
                'getter'  => 'getMassnumber',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('acs')->__('Print'),
                        'url'     => array('base'=> '*/*/printList'),
                        'field'   => 'massnumber',
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
     * @param ID_Acs_Model_List
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/printList', array('massnumber' => $row->getMassnumber()));
    }

    /**
     * get the grid url
     *
     * @access public
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid_list', array('_current'=>true));
    }

    /**
     * after collection load
     *
     * @access protected
     * @return ID_Acs_Block_Adminhtml_List_Grid
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
}
