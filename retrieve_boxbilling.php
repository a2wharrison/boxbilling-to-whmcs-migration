<?php

echo '<style type="text/css">';
echo '<!--';
echo '.tab { margin-left: 40px; }';
echo '-->';
echo '</style>';

$boxbilling_token = ''; //You can generate API token in BB-ADMIN in BoxBilling
$boxbilling_url = 'https://www.boxbiliing.com/api'; //Replace https://www.boxbiliing.com with your domain

$whmcs_username = 'admin'; //Username
$whmcs_password = ''; //Password
$whmcsUrl = "https://www.whmcs.com/"; //Replace https://www.whmcs.com with your whmcs url

$payment_method = "banktransfer"; //An active payment method in WHMCS. All imported orders and invoices are going to be switched to this payment method.

/* DO NOT EDIT BELLOW THIS LINE 

The script will import all your BoxBilling data to WHMCS

*/

require("BoxBillingApi.php");
require("WHMCSApi.php");

//Admin API
$config = array(
    'api_role'  =>  'admin',
    'api_token' =>  $boxbilling_token,
    'api_url'   =>  $boxbilling_url,
);
$api_admin = new Service_BoxBilling($config);

//Client API
$config2 = array(
    'api_role'  =>  'client',
    'api_token' =>  $boxbilling_token,
    'api_url'   =>  $boxbilling_url,
);
$api_client= new Service_BoxBilling($config2); 

//Get Clients
$params = array(
    'status'  =>  'active'
);
$clients = $api_admin->client_get_list($params)["list"];
//Get Invoices
$invoices = $api_admin->invoice_get_list()["list"];
//Get Orders
$orders = $api_admin->order_get_list()["list"];
echo "<pre>";


foreach($clients as $client) {
	// Import clients to WHMCS
    $config = array(
            'action' => 'AddClient',
            'username' => $whmcs_username,
            'password' => md5($whmcs_password),
            'firstname' => !empty($client["first_name"]) ? $client["first_name"] : 'empty',
            'lastname' => !empty($client["last_name"]) ? $client["last_name"] : 'empty',
            'email' => !empty($client["email"]) ? $client["email"] : 'empty',
            'address1' => !empty($client["address_1"]) ? $client["address_1"] : 'empty',
            'city' => !empty($client["city"]) ? $client["city"] : 'empty',
            'state' => !empty($client["state"]) ? $client["state"] : 'empty',
            'postcode' => !empty($client["postcode"]) ? $client["postcode"] : '0000',
            'country' => !empty($client["country"]) ? $client["country"] : 'US',
            'phonenumber' => !empty($client["phone"]) ? $client["phone"] : '0000',
            'password2' => 'Random',
            'clientip' => $client["ip"],
            'noemail' => true,
            'responsetype' => 'json',
    );
        
    if($client['notes'] !== null) {
        $config['notes'] = $client['notes'];
    }
        
        
    $whmcs = new Service_WHMCS();
    $import_client_result = $whmcs->send($config, $whmcsUrl);

    if ($import_client_result["result"]=='error') {
        echo "Failed to import client <b>".$config['firstname']." ".$config['lastname']."</b>. Remote WHMCS says: <b>".$import_client_result["message"]."</b><br>";
    }
    
    if ($import_client_result["result"]=='success') {
        echo "Successfully imported client <b>".$config['firstname']." ".$config['lastname']."</b> with id <b>".$import_client_result["clientid"]."</b><br>";
    }
  

	echo "<div class='tab'>";
	foreach ($invoices as $invoice) {
		if ($invoice['client']['id'] == $client['id']) {

			$client_invoices[]=$invoice;
		    
		    //Import Invoices

			if($invoice["status"] == canceled) {
			    $invoice_status = canceled;
			} else if($invoice["status"] == paid) {
			    $invoice_status = Paid;
			} else if($invoice["status"] == canceled) { //This is not a typo
			    $invoice_status = Cancelled;
			} else if($invoice["status"] == unpaid) {
			    $invoice_status = Unpaid;
			} else if($invoice["status"] == refunded) {
			    $invoice_status = Refunded;
			}

			$createDate = new DateTime($invoice["created_at"]);
			$created_at = $createDate->format('Y-m-d');

			$dueDate = new DateTime($invoice["due_at"]);
			$due_at = $dueDate->format('Y-m-d');
			$cnt=1;

	        $config = array(
	                'action' => 'CreateInvoice',
	                'username' => $whmcs_username,
	                'password' => md5($whmcs_password),
	                'userid' => $import_client_result["clientid"],
	                'status' => $invoice_status,
	                'sendinvoice' => '0',
	                'paymentmethod' => $payment_method,
	                'taxrate' => $invoice["taxrate"],
	                'date' => $created_at,
	                'duedate' => $due_at,
	                'autoapplycredit' => '0',
	                'responsetype' => 'json',
	        );
		            
            if($invoice['notes'] !== null) {
                $config['notes'] = $invoice['notes'];
            }
		            
			foreach($invoice["lines"] as $item) {
                $config['itemdescription'.$cnt] = $item["title"];
                $config['itemamount'.$cnt] = $item["price"];
                $config['itemtaxed'.$cnt] = $item["taxed"];
                $cnt++;
			}

		    $whmcs = new Service_WHMCS();
		    $import_invoice_result = $whmcs->send($config, $whmcsUrl);

	        if ($import_invoice_result["result"]=='error') {
	            echo "Failed to import invoice #<b>".$invoice['id']."</b>. Remote WHMCS says: <b>".$import_invoice_result["message"]."</b><br>";
	        }
	        
	        if ($import_invoice_result["result"]=='success') {
	            echo "Successfully imported invoice #<b>".$invoice['id']."</b>.<br>";
	        }
		}
	}

// This part is discontinued and not working properly becase of the lack of WHMCS API calls and I really didn't have time to mess with MySQL connections.
// This could still import domains but you need to add all TLDs to whmcs and add prices as I wasn't able to do it because of lack of API functions. 
//

/*
foreach ($orders as $order) {

	if ($order['client']['id'] == $client['id'] && $order['service_type'] == 'domain') {
       
	    //Import Domains

		if($order["status"] == pending_setup) {
		    $order_status = Active;
		} else if($order["status"] == active) { 
		    $order_status = Pending;
		} else {
		    $order_status;
		}

		if($order["config"]["period"] == '1M') {
		    $order_billing_cycle = 'monthly';
		} else if($order["config"]["period"] == '3M') {
		    $order_billing_cycle = 'quarterly';
		} else if($order["config"]["period"] == '6M') {
		    $order_billing_cycle = 'semi-annually';
		} else if($order["config"]["period"] == '1Y') {
		    $order_billing_cycle = 'annually';
		} else if($order["config"]["period"] == '2Y') {
		    $order_billing_cycle = 'biennially';
		} else if($order["config"]["period"] == '3Y') {
		    $order_billing_cycle = 'triennially';
		}

		if($order["config"]["period"] == '1W') {
		    echo "WHMCS doesnt support 1 week billing period therefore order ID".$order["id"]." won't be imported";
		} else {
			$createDate = new DateTime($order["created_at"]);
			$created_at = $createDate->format('Y-m-d');

			$dueDate = new DateTime($order["due_at"]);
			$due_at = $dueDate->format('Y-m-d');


	        $config = array(
	            'action' => 'AddOrder',
	            'username' => $whmcs_username,
	            'password' => md5($whmcs_password),
	            'clientid' => $import_client_result["clientid"],
	            'domain' => $order["config"]["register_sld"].$order["config"]["register_tld"],
	            'billingcycle' => $order_billing_cycle,
	            'domaintype' => register,
	            'regperiod' => $order["config"]["register_years"],
	            'dnsmanagement' => true,
	            'nameserver1' => $order["config"]["ns1"],
	            'nameserver2' => $order["config"]["ns2"],
	            'paymentmethod' => $payment_method,
	            'orderstatus' => $order_status,
	            'responsetype' => 'json',
	        );
		          
	        $whmcs = new Service_WHMCS();
			$import_order_result = $whmcs->send($config, $whmcsUrl);

	        if ($import_order_result["result"]=='error') {
	            echo "Failed to import order #<b>".$order['id']."</b>. Remote WHMCS says: <b>".$import_order_result["message"]."</b><br>";
	            
	        }
		        
	        if ($import_order_result["result"]=='success') {
	            echo "Successfully imported order #<b>".$order['id']."</b>.<br>";
	            die();
	        }
		}
	}
}

*/

echo "</div><br><br>";

}
