<?php

return [
    'db' => [
        'driver' => 'oci8',
        'connection_string' => '(DESCRIPTION =
        (ADDRESS = (PROTOCOL = TCP)(HOST =10.153.64.21)(PORT = 1521))
        (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = HRIS)
	)
        )',

    'username' => 'HRIS_KISOK',
     'password' => 'HRIS_KISOK',


        
        'platform_options' => ['quote_identifiers' => false]
    ],
    'service_manager' => [
        'factories' => [
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
        ],
    ],
];
