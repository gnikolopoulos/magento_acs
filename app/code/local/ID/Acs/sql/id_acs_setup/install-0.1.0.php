<?php

$this->startSetup();
$table_list = $this->getConnection()
    ->newTable($this->getTable('id_acs/list'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Entity ID'
    )
    ->addColumn(
        'massnumber',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'Mass Number'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'List Creation Time'
    )
    ->setComment('List Table');

$this->getConnection()->createTable($table_list);

$table_voucher = $this->getConnection()
    ->newTable($this->getTable('id_acs/voucher'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Entity ID'
    )
    ->addColumn(
        'pod',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'POD No'
    )
    ->addColumn(
        'orderno',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'Order No'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'POD Creation Time'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255
        null,
        array(
            'nullable'  => false,
        ),
        'POD Status'
    )
    ->setComment('Voucher Table');

$this->getConnection()->createTable($table_voucher);

$table_antik = $this->getConnection()
    ->newTable($this->getTable('id_acs/antikatavoles'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Entity ID'
    )
    ->addColumn(
        'pod',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'POD No'
    )
    ->addColumn(
        'customer_name',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        null,
        array(),
        'Customer Name'
    )
    ->addColumn(
        'order',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        null,
        array(
            'nullable'  => false,
        ),
        'Order Increment ID'
    )
    ->addColumn(
        'value',
        Varien_Db_Ddl_Table::TYPE_DECIMAL, 255,
        null,
        array(
            'nullable'  => false,
            'scale'     => 2,
            'precision' => 9,
        ),
        'POD Value'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        null,
        array(
            'nullable'  => false,
        ),
        'POD Status'
    )
    ->addColumn(
        'date',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'Date Delivered'
    )
    ->setComment('Voucher Table');

$this->getConnection()->createTable($table_antik);

$this->addAttribute("order", "field_custom_price", array("type"=>"varchar"));
$this->addAttribute("quote", "field_custom_price", array("type"=>"varchar"));

$this->endSetup();