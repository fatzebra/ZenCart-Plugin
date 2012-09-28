<?php
  /**
  * Fat Zebra ZenCart Plugin
  *
  * Created September 2012 - Matthew Savage (matthew.savage@fatzebra.com.au)
  * Version 1.1
  *
  * The original source for this library, including its tests can be found at
  * https://github.com/fatzebra/ZenCart-Plugin
  *
  * Please visit http://docs.fatzebra.com.au for details on the Fat Zebra API
  * or https://www.fatzebra.com.au/help for support.
  *
  * Patches, pull requests, issues, comments and suggestions always welcome.
  */
  class FatZebra {
    var $code, $title, $description, $enabled;

    // class constructor
    function FatZebra() {
      global $order,$db;

      $this->code = 'fatzebra';
      $this->description = MODULE_PAYMENT_FATZEBRA_TEXT_DESCRIPTION;
      
      if ($_GET['main_page'] != '') {
        $this->title = MODULE_PAYMENT_FATZEBRA_TEXT_CATALOG_TITLE;
      } else {
        $this->title = MODULE_PAYMENT_FATZEBRA_TEXT_ADMIN_TITLE;
      }
      
      // Whether the module is installed or not
      $this->enabled = (MODULE_PAYMENT_FATZEBRA_ENABLED == 'True');
      $this->sort_order = MODULE_PAYMENT_FATZEBRA_SORT_ORDER;
      $this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', false);

      if ((int)MODULE_PAYMENT_FATZEBRA_ORDER_STATUS_ID > 0) {
          $this->order_status = MODULE_PAYMENT_FATZEBRA_ORDER_STATUS_ID;
      }

      if (is_object($order)) {
        $this->update_status();
      }
    }


    // class methods
    // Indicates if the payment method is suitable for the billing address's 'zone'
    function update_status() {
      global $order, $db;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_FATZEBRA_ZONE > 0) ) {
        $check_flag = false;
        $check = $db->Execute("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_FATZEBRA_ZONE . "' AND zone_country_id = '" . $order->billing['country']['id'] . "' ORDER BY zone_id");
        while (!$check->EOF) {
          if ($check->fields['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
          $check->MoveNext();
        }

        if ($check_flag == false) {
          $this->enabled = false;
        }
      }
    }

    // Validate the credit card information via javascript (Number, Owner, and CVV Lengths)
    function javascript_validation() {
     return '  if (payment_value == "' . $this->code . '") {' . "\n" .
            '    var cc_owner = document.checkout_payment.cc_owner.value;' . "\n" .
            '    var cc_number = document.checkout_payment.cc_number.value;' . "\n" .
            '    var cc_cvv = document.checkout_payment.cvv.value;' . "\n" .
            '    if (cc_owner == "" || cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
            '      error_message = error_message + "' . MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_OWNER . '";' . "\n" .
            '      error = 1;' . "\n" .
            '    }' . "\n" .
            '    if (cc_number == "" || cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
            '      error_message = error_message + "' . MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_NUMBER . '";' . "\n" .
            '      error = 1;' . "\n" .
            '    }' . "\n" .
            '    if (cc_cvv == "" || cc_cvv.length < "3") {' . "\n".
            '      error_message = error_message + "' . MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_CVV . '";' . "\n" .
            '      error = 1;' . "\n" .
            '    }' . "\n".
            '  }' . "\n";
    }

    // Display Credit Card Information Submission Fields on the Checkout Payment Page
    function selection() {
      global $order;
      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
      }

      $today = getdate();
      for ($i=$today['year']; $i < $today['year']+10; $i++) {
        $expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }

      $selection = array('id' => $this->code,
                         'module' => MODULE_PAYMENT_FATZEBRA_TEXT_CATALOG_TITLE,
                         'fields' => array(array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE,
                                                 'field' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE_AUTO),
                                           array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_OWNER,
                                                 'field' => zen_draw_input_field('cc[owner]', $order->billing['firstname'] . ' ' . $order->billing['lastname'], 'id="cc_owner"')),
                                           array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_NUMBER,
                                                 'field' => zen_draw_input_field('cc[number]', '', 'id="cc_number"')),
                                           array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_EXPIRES,
                                                 'field' => zen_draw_pull_down_menu('cc[expires_month]', $expires_month, "id='expires_month'") . '&nbsp;' . zen_draw_pull_down_menu('cc[expires_year]', $expires_year, "id='expires_month'")),
                                           array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CVV,
                                                 'field' => zen_draw_input_field("cc[cvv]", '', 'size="4" maxlength="4" id="cvv"'))
                                           )
                         );
      return $selection;
    }


    // Validate the credit card and store it into the class ivars for use when processing
    function pre_confirmation_check() {
      global $_POST, $messageStack;

      include(DIR_WS_CLASSES . 'cc_validation.php');

      $cc_validation = new cc_validation();
      $cc = $_POST['cc'];
      $result = $cc_validation->validate($cc['number'], $cc['expires_month'], $cc['expires_year']);
      $error = '';
      switch ($result) {
        case -1:
          $error = sprintf(TEXT_CCVAL_ERROR_UNKNOWN_CARD, substr($cc_validation->cc_number, 0, 4));
          break;
        case -2:
        case -3:
        case -4:
          $error = TEXT_CCVAL_ERROR_INVALID_DATE;
          break;
        case false:
          $error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
          break;
      }

      if ( ($result == false) || ($result < 1) ) {
        $messageStack->add_session('checkout_payment', $error . '<!-- ['.$this->code.'] -->', 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
      }

      $this->cc_card_type = $cc_validation->cc_type;
      $this->cc_card_number = $cc_validation->cc_number;
      $this->cc_expiry_month = $cc_validation->cc_expiry_month;
      $this->cc_expiry_year = $cc_validation->cc_expiry_year;
      $this->cc_cvv = $cc['cvv'];
    }

    // Display Credit Card Information on the Checkout Confirmation Page
    function confirmation() {
      global $_POST;
      $cc = $_POST['cc'];

      $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE,
                                                    'field' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE_AUTO),
                                              array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_OWNER,
                                                    'field' => $cc['owner']),
                                              array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_NUMBER,
                                                    'field' => substr($this->cc_card_number, 0, 4) . str_repeat('X', (strlen($this->cc_card_number) - 8)) . substr($this->cc_card_number, -4)),
                                              array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_EXPIRES,
                                                    'field' => strftime('%B, %Y', mktime(0,0,0,$cc['expires_month'], 1, '20' . $cc['expires_year']))),
                                              array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_CVV,
                                                    'field' => ($cc['cvv'].''==''?MODULE_PAYMENT_FATZEBRA_TEXT_CVV_OMITTED:str_repeat('X',strlen($cc['cvv']))))
                                              )
                            );
      return $confirmation;
    }

    // Prepares hidden fields on the 'confirmation' page to be submitted.
    function process_button() {
      $cc = $_POST['cc'];

      // These are hidden fields on the checkout confirmation page
      $process_button_string = zen_draw_hidden_field('cc_owner', $cc['owner']) .
                               zen_draw_hidden_field('cc_expires_month', $this->cc_expiry_month) .
                               zen_draw_hidden_field('cc_expires_year', substr($this->cc_expiry_year, -2)) .
                               zen_draw_hidden_field('cc_number', $this->cc_card_number).
                               zen_draw_hidden_field('cc_cvv', $cc['cvv']).
                               zen_draw_hidden_field(zen_session_name(), zen_session_id());

      return $process_button_string;
    }

    function before_process() {
      global $db, $order, $messageStack;

      $order->info['cc_number']  = str_pad(substr($temp=$_POST['cc_number'], -4), strlen($temp), 'X', STR_PAD_LEFT);
      $cc_expires_month = $_POST['cc_expires_month'];
      $cc_expires_year = (int)$_POST['cc_expires_year'] + 2000;
      $order->info['cc_type'] = MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE_AUTO;
      $order->info['cc_owner'] = $_POST['cc_owner'];
      $order->info['cc_cvv'] = str_pad('', strlen($_POST['cc_cvv']), 'X');

      
      // Create an order ID
      $last_order_id = $db->Execute("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1");
      $new_order_id = $last_order_id->fields['orders_id'];
      $new_order_id = ($new_order_id + 1);
      $new_order_id = (string)$new_order_id . '-' . zen_create_random_value(6, 'chars'); // Ensure uniqueness

      $gateway_url = MODULE_PAYMENT_FATZEBRA_SANDBOX == "True" ? "https://gateway.sandbox.fatzebra.com.au/v1.0/purchases" : "https://gateway.fatzebra.com.au/v1.0/purchases"; 

      $params = array("amount" => round($order->info['total']*100),
                      "reference" => $new_order_id,
                      "card_holder" => $_POST['cc_owner'],
                      "card_number" => $_POST['cc_number'],
                      "card_expiry" => $cc_expires_month . "/" . $cc_expires_year,
                      "cvv" => $_POST['cc_cvv'],
                      "customer_ip" => $_SERVER['REMOTE_ADDR'],
                      "test" => (MODULE_PAYMENT_FATZEBRA_TESTMODE == "True"));

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $gateway_url);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_USERPWD, MODULE_PAYMENT_FATZEBRA_USERNAME .":". MODULE_PAYMENT_FATZEBRA_TOKEN);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));    
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cacert.pem');
      curl_setopt($curl, CURLOPT_TIMEOUT, 10);
      $data = curl_exec($curl); 
      
      if (curl_errno($curl) !== 0) {
        throw new Exception("cURL error: " . curl_error($curl));
      }

      curl_close($curl);

      $result =  json_decode($data);
      if (is_null($result)) {
        $err = json_last_error();
        $messageStack->add_session('checkout_payment', MODULE_PAYMENT_FATZEBRA_TEXT_DECLINED_MESSAGE . "<br /> Gateway error - please contact the website owner.", 'error');
        if ($err == JSON_ERROR_SYNTAX) {
          error_log("JSON Syntax error. JSON attempted to parse: " . $data);
        } elseif ($err == JSON_ERROR_UTF8) {
          error_log("JSON Data invalid - Malformed UTF-8 characters. Data: " . $data);
        } else {
          error_log("JSON parse failed. Unknown error ({$err}). Data:" . $data);
        }

        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
      }

      if ($result->successful && $result->response->successful) {
        // Handle successful here
        $this->transaction_id = $result->response->id;
        $this->auth_id = $result->response->authorization;
        $this->order_status = 2; // Processing (payment made)
      } elseif ($result->successful && !$result->response->successful) {
        // Handle declined response here
        $messageStack->add_session('checkout_payment', MODULE_PAYMENT_FATZEBRA_TEXT_DECLINED_MESSAGE . $result->response->message . '<br />' . MODULE_PAYMENT_FATZEBRA_TEXT_DECLINED_MESSAGE_TRY_AGAIN, 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
      } else {
        // There are error here...
        $errors = "";
        foreach($result->errors as $error) {
          $errors .= "<li>{$error}</li>";
        }

        $messageStack->add_session('checkout_payment', "Communication or Validation error - please contact website owner.<br /><ul>" . $errors . "</ul>", 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
      }
    }

    // Functions performed after processing
    function after_process() {
      global $insert_id, $db;

      $sql = "insert into " . TABLE_ORDERS_STATUS_HISTORY . " (comments, orders_id, orders_status_id, customer_notified, date_added) values (:orderComments, :orderID, :orderStatus, -1, now() )";
      $sql = $db->bindVars($sql, ':orderComments', 'Credit Card payment.  AUTH: ' . $this->auth_id . '. Transaction ID: ' . $this->transaction_id . '.', 'string');
      $sql = $db->bindVars($sql, ':orderID', $insert_id, 'integer');
      $sql = $db->bindVars($sql, ':orderStatus', $this->order_status, 'integer');
      $db->Execute($sql);
  
      return false;
    }

    // Retrieves the error from the response
    function get_error() {
      global $_GET;

      return array('title' => MODULE_PAYMENT_FATZEBRA_TEXT_ERROR,
                   'error' => stripslashes(urldecode($_GET['error'])));
    }

    // Check to see if the module has been installed or not.
    function check() {
      global $db;

      if (!isset($this->_check)) {
        $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_FATZEBRA_ENABLED'");
        $this->_check = $check_query->RecordCount();
      }
      return $this->_check;
    }

    // Install the module configuration defaults
    function install() {
      global $db;

      $sql = "INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES 
                  ('Enable Fat Zebra Module', 'MODULE_PAYMENT_FATZEBRA_ENABLED', 'True', 'Enable Fat Zebra as a payment method for your site.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', DEFAULT, now()),
                  ('Username', 'MODULE_PAYMENT_FATZEBRA_USERNAME', 'TEST', 'Your account username.', '6', '0', DEFAULT, DEFAULT, now()),
                  ('Token', 'MODULE_PAYMENT_FATZEBRA_TOKEN', 'TEST', 'Your account token.', '6', '0', DEFAULT, DEFAULT, now()),
                  ('Use Test Mode', 'MODULE_PAYMENT_FATZEBRA_TESTMODE', 'True', 'Enable Test Mode for the gateway.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', DEFAULT, now()),
                  ('Use Sandbox', 'MODULE_PAYMENT_FATZEBRA_SANDBOX', 'True', 'Use the Sandbox Gateway (for testing).', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', DEFAULT, now()),
                  ('Sort order of display.', 'MODULE_PAYMENT_FATZEBRA_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', DEFAULT, DEFAULT, now()),
                  ('Payment Zone', 'MODULE_PAYMENT_FATZEBRA_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_cfg_pull_down_zone_classes(', 'zen_get_zone_class_title', now()),
                  ('Set Order Status', 'MODULE_PAYMENT_FATZEBRA_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value.', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now());";
      $db->Execute($sql);
    }

    // Remove the module configuration when uninstalling
    function remove() {
      global $db;

      $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    // Configuration keys used for the module
    // These become defined as constants
    function keys() {
      return array(
       'MODULE_PAYMENT_FATZEBRA_ENABLED',
       'MODULE_PAYMENT_FATZEBRA_USERNAME',
       'MODULE_PAYMENT_FATZEBRA_TOKEN',
       'MODULE_PAYMENT_FATZEBRA_TESTMODE',
       'MODULE_PAYMENT_FATZEBRA_SANDBOX',
       'MODULE_PAYMENT_FATZEBRA_SORT_ORDER',
       'MODULE_PAYMENT_FATZEBRA_ZONE',
       'MODULE_PAYMENT_FATZEBRA_ORDER_STATUS_ID');
    }
  }
?>