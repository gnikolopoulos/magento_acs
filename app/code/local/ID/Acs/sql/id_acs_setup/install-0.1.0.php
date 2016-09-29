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

$this->addAttribute("order", "field_custom_price", array("type"=>"varchar"));
$this->addAttribute("quote", "field_custom_price", array("type"=>"varchar"));

$this->endSetup();