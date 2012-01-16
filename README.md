#Ubersmith PHP API Client
##I heard you like automation...


This client has what you need to get started interacting with the Ubersmith API in PHP. Checkout sample.php for an idea of how to get started.


###Usage


```php
<?php

require_once dirname(__FILE__) .'/class.uber_api_client.php';

$client = new uber_api_client('http://billing.mycompany.com/','admin','admin');

try {
	$result = $client->call('client.get',array(
		'client_id' => 1001,
	));
	
	print_r($result);
} catch (Exception $e) {
	print 'Error: '. $e->getMessage() .' ('. $e->getCode() .')';
}

// end of script
