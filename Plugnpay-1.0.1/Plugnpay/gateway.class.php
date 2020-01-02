<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-3.0 http://opensource.org/licenses/GPL-3.0
 */
class Gateway {
	private $_config;
	private $_module;
	private $_basket;
	private $_result_message;
	private $_url;
	private $_path;

	public function __construct($module = false, $basket = false) {
		$this->_db	=& $GLOBALS['db'];

		$this->_module	= $module;
		$this->_basket	=& $GLOBALS['cart']->basket;
		$this->_url		= 'pay1.plugnpay.com';
		$this->_path	= '/payment/pay.cgi';
	}

	##################################################

	public function transfer() {
		$transfer = array(
			'action'	=> ($this->_module['mode']=='ss') ? 'https://'.$this->_url.$this->_path : currentPage(),
			'method'	=> 'post',
			'target'	=> '_self',
			'submit'	=> ($this->_module['mode']=='ss') ? 'auto'  : 'manual',
		);
		return $transfer;
	}

	##################################################

	public function repeatVariables() {
		return (isset($hidden)) ? $hidden : false;
	}

	public function fixedVariables() {
		if($this->_module['mode']=='ss') {
			
			$fp_sequence 	= $this->_basket['cart_order_id'].time(); // Enter an invoice or other unique number.
			$fp_timestamp 	= time();
			$fingerprint 	= $this->_getFingerprint($this->_module['acNo'],$this->_module['txnkey'], $this->_basket['total'], $fp_sequence, $fp_timestamp);
			
			$hidden = array(
				'authtype'			=> ($this->_module['payment_type']=='AUTH_CAPTURE') ? 'authpostauth'  : 'authonly', //AUTH_CAPTURE or AUTH_ONLY
				'publisher-name'	=> $this->_module['acNo'],
				'authhash'			=> $fingerprint,
				'card-amount'		=> $this->_basket['total'],
				'transtime'			=> $fp_timestamp,
				'order-id'			=> $fp_sequence,
				'client'			=> 'CubeCart_SS',
				'paymethod'			=> 'credit',
				'acct_code'			=> $this->_basket['cart_order_id'],
				'x_description'		=> "Payment for order #".$this->_basket['cart_order_id'],
				
				'card-name'			=> $this->_basket['billing_address']['first_name'] . ' ' . $this->_basket['billing_address']['last_name'],
				'card-address1'		=> $this->_basket['billing_address']['line1'],
				'card-address2'		=> $this->_basket['billing_address']['line2'],
				'card-city'			=> $this->_basket['billing_address']['town'],
				'card-state'		=> $this->_basket['billing_address']['state'],
				'card-zip'			=> $this->_basket['billing_address']['postcode'],
				'card-country'		=> $this->_basket['billing_address']['country_iso'],
				
				'shipname'			=> $this->_basket['delivery_address']['first_name'] . ' ' . $this->_basket['delivery_address']['last_name'],
				'address1'			=> $this->_basket['delivery_address']['line1'],
				'address2'			=> $this->_basket['delivery_address']['line2'],
				'city'				=> $this->_basket['delivery_address']['town'],
				'state'				=> $this->_basket['delivery_address']['state'],
				'zip'				=> $this->_basket['delivery_address']['postcode'],
				'country'			=> $this->_basket['delivery_address']['country_iso'],
				
				'email'				=> $this->_basket['billing_address']['email'],
				'phone'				=> $this->_basket['billing_address']['phone'],
				
				'ipaddress'			=> get_ip_address(),
				'transitiontype'	=> 'hidden',
				'success-link'		=> $GLOBALS['storeURL'].'/index.php?_g=remote&type=gateway&cmd=process&module=Plugnpay'
			);
		} else {
			$hidden['gateway']	= basename(dirname(__FILE__));
		}
		return (isset($hidden)) ? $hidden : false;
	}

	public function call() {
		return false;
	}

	public function process() {
	
		$order				= Order::getInstance();
		$cart_order_id 		= $this->_basket['cart_order_id'];
		$order_summary		= $order->getSummary($cart_order_id);
		$response_code 		= (string)$_POST['FinalStatus'];
		
		if($this->_module['mode']=='ss') {
			## Process the payment for Smart Screens	
			if($response_code=='success'){
				$status = 'Approved';
				$order->orderStatus(Order::ORDER_PROCESS, $_POST['acct_code']);
				$order->paymentStatus(Order::PAYMENT_SUCCESS, $_POST['acct_code']);
			} else {
				
				switch($response_code) {
					case 'badcard':
						$status = 'Declined';
					break;
					case 'problem':
						$status = 'Error';
					break;
					case 'fraud':
						$status = 'Held for Review';
					break;
				}
				
				$order->orderStatus(Order::ORDER_PENDING, $_POST['acct_code']);
				$order->paymentStatus(Order::PAYMENT_PENDING, $_POST['acct_code']);
			}
	
			$transData['notes']		= $_POST['MErrMsg'];
			$transData['order_id']	= $_POST['acct_code'];
			$transData['trans_id']	= $_POST['orderID'];
			$transData['amount']	= $_POST['card-amount'];
			$transData['extra']		= '';
		
		} else {
			## Process the payment for API
			$plugnpay_array = array(
				'publisher-name'		=> $this->_module['acNo'],
				'publisher-password'	=> $this->_module['txnkey'],
				//'password'			=> $this->_module['password'],
				'client'				=> 'CubeCart_API',
				'authtype'				=> ($this->_module['payment_type']=='AUTH_CAPTURE') ? 'authpostauth'  : 'authonly', //AUTH_CAPTURE or AUTH_ONLY
				'paymethod'				=> 'credit',
				'card-amount'			=> $this->_basket['total'],
				'card-number'			=> trim($_POST['cardNumber']),
				'card-exp'				=> str_pad($_POST['expirationMonth'], 2, '0', STR_PAD_LEFT) . '/' . substr($_POST["expirationYear"],2,2),
				'card-cvv'				=> trim($_POST['cvc2']),
				'acct_code'				=> $this->_basket['cart_order_id'],
				'x_description'			=> "Payment for order #".$this->_basket['cart_order_id'],
				'card-name'				=> trim($_POST['firstName']) . ' ' . trim($_POST['lastName']),
				'card-address1'			=> trim($_POST['addr1']),
				'card-address2'			=> trim($_POST['addr2']),
				'card-city'				=> trim($_POST['city']),
				'card-state'			=> trim($_POST['state']),
				'card-zip'				=> trim($_POST['postcode']),
				'card-country'			=> trim($_POST['country']),
				'email'					=> trim($_POST['emailAddress']),
				'ipaddress'				=> get_ip_address(),
				'shipname'				=> $this->_basket['delivery_address']['first_name'] . ' ' . $this->_basket['delivery_address']['last_name'],
				'address1'				=> $this->_basket['delivery_address']['line1'],
				'address2'				=> $this->_basket['delivery_address']['line2'],
				'city'					=> $this->_basket['delivery_address']['town'],
				'state'					=> $this->_basket['delivery_address']['state'],
				'zip'					=> $this->_basket['delivery_address']['postcode'],
				'country'				=> $this->_basket['delivery_address']['country_iso']
			);
			$request	= new Request($this->_url, $this->_path);
			$request->setSSL();
			$request->setData($plugnpay_array);
			$resp		= $request->send();
			$results 	= explode('&',$resp);

			#### INSERT PLUGNPAY'S PHP PARSE CODE HERE
			if($results[0] == 1) {
				$status	= 'Approved';
				$order->orderStatus(Order::ORDER_PROCESS, $cart_order_id);
				$order->paymentStatus(Order::PAYMENT_SUCCESS, $cart_order_id);
			} elseif($results[0] == 2) {
				$status	= 'Declined';
				$order->orderStatus(Order::ORDER_PENDING, $cart_order_id);
				$order->paymentStatus(Order::PAYMENT_DECLINE, $cart_order_id);
			} elseif($results[0] == 3) {
				$status	= 'Error';AUTH_CAPTURE
				$order->orderStatus(Order::ORDER_PENDING, $cart_order_id);
			}
	
			if($results[0] !== 1) {
				$this->_result_message	= $results[3];
			}
	
			$transData['notes']			= $this->_result_message;
			$transData['order_id']		= $results[7];
			$transData['trans_id']		= $results[37];
			$transData['amount']		= $results[9];
			$transData['status']		= $status;
			$transData['customer_id']	= $order_summary['customer_id'];
			$transData['extra']			= '';
		}
		
		$transData['status']		= $status;
		$transData['customer_id']	= $order_summary['customer_id'];
		$transData['gateway']		= 'PlugnPay ('.strtoupper($this->_module['mode']).')';
		$order->logTransaction($transData);
		
		if($status=='Approved') {
			httpredir(currentPage(array('_g', 'type', 'cmd', 'module'), array('_a' => 'complete')));
		}
	}

	##################################################

	private function formatMonth($val) {
		return $val." - ".strftime("%b", mktime(0,0,0,$val,1 ,2009));
	}

	public function form() {
		
		## Process transaction
		if (isset($_POST['cardNumber'])) {
			$return	= $this->process();
		}

		// Display payment result message
		if (!empty($this->_result_message))	{
			$GLOBALS['gui']->setError($this->_result_message);
		}

		//Show Expire Months
		$selectedMonth	= (isset($_POST['expirationMonth'])) ? $_POST['expirationMonth'] : date('m');
		for($i = 1; $i <= 12; ++$i) {
			$val = sprintf('%02d',$i);
			$smarty_data['card']['months'][]	= array(
				'selected'	=> ($val == $selectedMonth) ? 'selected="selected"' : '',
				'value'		=> $val,
				'display'	=> $this->formatMonth($val),
			);
		}

		## Show Expire Years
		$thisYear = date("Y");
		$maxYear = $thisYear + 10;
		$selectedYear = isset($_POST['expirationYear']) ? $_POST['expirationYear'] : ($thisYear+2);
		for($i = $thisYear; $i <= $maxYear; ++$i) {
			$smarty_data['card']['years'][]	= array(
				'selected'	=> ($i == $selectedYear) ? 'selected="selected"' : '',
				'value'		=> $i,
			);
		}
		$GLOBALS['smarty']->assign('CARD', $smarty_data['card']);
		
		$smarty_data['customer'] = array(
			'first_name'	=> isset($_POST['firstName']) ? $_POST['firstName'] : $this->_basket['billing_address']['first_name'],
			'last_name'		=> isset($_POST['lastName']) ? $_POST['lastName'] : $this->_basket['billing_address']['last_name'],
			'email'			=> isset($_POST['emailAddress']) ? $_POST['emailAddress'] : $this->_basket['billing_address']['email'],
			'add1'			=> isset($_POST['addr1']) ? $_POST['addr1'] : $this->_basket['billing_address']['line1'],
			'add2'			=> isset($_POST['addr2']) ? $_POST['addr2'] : $this->_basket['billing_address']['line2'],
			'city'			=> isset($_POST['city']) ? $_POST['city'] : $this->_basket['billing_address']['town'],
			'state'			=> isset($_POST['state']) ? $_POST['state'] : $this->_basket['billing_address']['state'],
			'postcode'		=> isset($_POST['postcode']) ? $_POST['postcode'] : $this->_basket['billing_address']['postcode']
		);
		
		$GLOBALS['smarty']->assign('CUSTOMER', $smarty_data['customer']);
		
		## Country list
		$countries = $GLOBALS['db']->select('CubeCart_geo_country', false, false, array('name' => 'ASC'));
		if ($countries) {
			$currentIso = isset($_POST['country']) ? $_POST['country'] : $this->_basket['billing_address']['country_iso'];
			foreach ($countries as $country) {
				$country['selected']	= ($country['iso'] == $currentIso) ? 'selected="selected"' : '';
				$smarty_data['countries'][]	= $country;
			}
			$GLOBALS['smarty']->assign('COUNTRIES', $smarty_data['countries']);
		}
		
		## Check for custom template for module in skin folder
		$file_name = 'form.tpl';
		$form_file = $GLOBALS['gui']->getCustomModuleSkin('gateway', dirname(__FILE__), $file_name);
		$GLOBALS['gui']->changeTemplateDir($form_file);
		$ret = $GLOBALS['smarty']->fetch($file_name);
		$GLOBALS['gui']->changeTemplateDir();
		return $ret;
	}
	
	private static function _getFingerprint($api_login_id, $transaction_key, $amount, $fp_sequence, $fp_timestamp) {
        if (function_exists('hash_hmac')) {
            return hash_hmac("md5", $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount . "^", $transaction_key); 
        }
        return bin2hex(mhash(MHASH_MD5, $api_login_id . "^" . $fp_sequence . "^" . $fp_timestamp . "^" . $amount . "^", $transaction_key));
    }
}
