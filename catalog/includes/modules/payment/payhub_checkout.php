<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2008 osCommerce
*/

  class payhub_checkout {
    var $code, $title, $description, $enabled;

// class constructor
    function payhub_checkout() {
      global $order;
      
      $this->code = 'payhub_checkout';
      $this->title = MODULE_PAYMENT_PAYHUB_TEXT_TITLE;
      $this->public_title = MODULE_PAYMENT_PAYHUB_TEXT_PUBLIC_TITLE;
      # add notice if in demo mode
      if(MODULE_PAYMENT_PAYHUB_TESTMODE == "demo") $this->public_title .= " (**DEMO MODE**)"; 
      $this->description = MODULE_PAYMENT_PAYHUB_TEXT_DESCRIPTION;
      $this->sort_order = MODULE_PAYMENT_PAYHUB_SORT_ORDER;
      $this->enabled = ((MODULE_PAYMENT_PAYHUB_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();
    }

// class methods
    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYHUB_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYHUB_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    function javascript_validation() {
      return false;
    }

    function selection() {
      return array('id' => $this->code,
                   'module' => $this->public_title);
    }

    function pre_confirmation_check() {
      return false;
    }

    function confirmation() {
      global $order;

      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
      }

      $today = getdate(); 
      for ($i=$today['year']; $i < $today['year']+10; $i++) {
        $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }

      $confirmation = array('fields' => array(array('title' => 'Name on Card',
                                                    'field' => tep_draw_input_field('cc_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'])),
                                              array('title' => 'Card Number',
                                                    'field' => tep_draw_input_field('cc_number_nh-dns', '', 'autocomplete="off"')),
                                              array('title' => 'Card Expiration Date',
                                                    'field' => tep_draw_pull_down_menu('cc_expires_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('cc_expires_year', $expires_year)),
                                              array('title' => 'CVV/CID (Security Code)',
                                                    'field' => tep_draw_input_field('cc_cvc_nh-dns', '', 'size="5" maxlength="4" autocomplete="off"'))));


      return $confirmation;
    }

    function process_button() {
      return false;
    }

    function before_process() {
      global $HTTP_POST_VARS, $customer_id, $order, $sendto, $currency;


      $params = array('orgid' => substr(MODULE_PAYMENT_PAYHUB_ORGID, 0, 15),
                      'username' => substr(MODULE_PAYMENT_PAYHUB_API_USERNAME, 0, 15),
                      'password' => substr(MODULE_PAYMENT_PAYHUB_API_PASSWORD, 0, 15),
                      'tid' => substr(MODULE_PAYMENT_PAYHUB_TERMID, 0, 15),
                      'first_name' => substr($order->billing['firstname'], 0, 50),
                      'last_name' => substr($order->billing['lastname'], 0, 50),
                      'address1' => substr($order->billing['street_address'], 0, 60),
                      'city' => substr($order->billing['city'], 0, 40),
                      'state' => substr($order->billing['state'], 0, 40),
                      'zip' => substr($order->billing['postcode'], 0, 20),
                      'phone' => substr($order->customer['telephone'], 0, 25),
                      'email' => substr($order->customer['email_address'], 0, 255),
                      'ship_to_name' => $order->delivery['firstname'] . " " . $order->delivery['lastname'],
                      'ship_address1' => $order->delivery['street_address'],
                      'ship_address2' => "",
                      'ship_city' => $order->delivery['city'],
                      'ship_state' => substr($order->delivery['state'], 0, 40),
                      'ship_zip' => $order->delivery['postcode'],
                      'note' => substr(STORE_NAME, 0, 255),
                      'amount' => $order->info['total'],
                      'cc' => substr($HTTP_POST_VARS['cc_number_nh-dns'], 0, 22),
                      'month' => $HTTP_POST_VARS['cc_expires_month'],
		      'year' => $HTTP_POST_VARS['cc_expires_year'],
                      'cvv' => substr($HTTP_POST_VARS['cc_cvc_nh-dns'], 0, 4)
                      );

      $gateway_url = 'https://checkout.payhub.com/transaction/api';
      
      switch (MODULE_PAYMENT_PAYHUB_TESTMODE) {
        case 'live':
	  $params['mode'] = "live";
          break;
        default:
	  # "demo" mode will cause the merchant parameters to be overwritten at the 
	  # gateway_url host and the request redirected to a test server.
	  $params['mode'] = "demo";
      }

      $post_string = json_encode($params);

      $res_string = $this->sendTransactionToGateway($gateway_url, $post_string);
      $response = json_decode($res_string);
      #$response = preg_replace('/\//', '', $response);

      if ($response->RESPONSE_CODE != "00") {
        $error = 'Response Code:  ' . $response->RESPONSE_CODE . '.  ' . $response->RESPONSE_TEXT;
      }

      if ($error != false) {
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code.'&error='.urlencode($error), 'NONSSL', true, false));
        #tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code.'&error='.urlencode($error) . $response->RESPONSE_CODE . $response->RESPONSE_TEXT, 'NONSSL', true, false));
      }
    }

    function after_process() {
      return false;
    }

    function get_error() {
      global $HTTP_GET_VARS;
      
      $error = array('title' => 'Transaction Failed:',
                     'error' => $HTTP_GET_VARS['error']);
      return $error;
    }

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYHUB_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayHub Credit Card Module', 'MODULE_PAYMENT_PAYHUB_STATUS', 'False', 'Do you want to accept Credit Cards through PayHub?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Organization ID', 'MODULE_PAYMENT_PAYHUB_ORGID', '', 'The Organization ID for your PayHub account', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Username', 'MODULE_PAYMENT_PAYHUB_API_USERNAME', '', 'PayHub API Username generated for your account', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API Password', 'MODULE_PAYMENT_PAYHUB_API_PASSWORD', '', 'PayHub API Password generated for your account', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Terminal ID', 'MODULE_PAYMENT_PAYHUB_TERMID', '', 'PayHub Terminal ID generated for your account', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of Display.', 'MODULE_PAYMENT_PAYHUB_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYHUB_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('cURL Program Location', 'MODULE_PAYMENT_PAYHUB_CURL', '/usr/bin/curl', 'The location to the cURL program application.', '6', '0' , now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Mode', 'MODULE_PAYMENT_PAYHUB_TESTMODE', 'demo', 'Transaction mode used for processing orders', '6', '0', 'tep_cfg_select_option(array(\'demo\', \'live\'), ', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_PAYHUB_STATUS',
                   'MODULE_PAYMENT_PAYHUB_ORGID',
                   'MODULE_PAYMENT_PAYHUB_API_USERNAME',
                   'MODULE_PAYMENT_PAYHUB_API_PASSWORD',
                   'MODULE_PAYMENT_PAYHUB_TERMID',
                   'MODULE_PAYMENT_PAYHUB_TESTMODE',
                   'MODULE_PAYMENT_PAYHUB_ZONE',
                   'MODULE_PAYMENT_PAYHUB_ORDER_STATUS_ID',
                   'MODULE_PAYMENT_PAYHUB_SORT_ORDER',
                   'MODULE_PAYMENT_PAYHUB_CURL');
    }


    function sendTransactionToGateway($url, $parameters) {

    $ch = curl_init();

    $c_opts = array(CURLOPT_URL => $url,
                    CURLOPT_VERBOSE => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $parameters);

    curl_setopt_array($ch, $c_opts);

    $raw = curl_exec($ch);

    curl_close($ch);

    //$raw = preg_replace('/\//', '', $raw);

    return $raw;
    }

// format prices without currency formatting
    function format_raw($number, $currency_code = '', $currency_value = '') {
      global $currencies, $currency;

      if (empty($currency_code) || !$this->is_set($currency_code)) {
        $currency_code = $currency;
      }

      if (empty($currency_value) || !is_numeric($currency_value)) {
        $currency_value = $currencies->currencies[$currency_code]['value'];
      }

      return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }
  }
?>
