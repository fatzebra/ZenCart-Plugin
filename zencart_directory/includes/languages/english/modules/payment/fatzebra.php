<?php
  // Admin Configuration Items
  define('MODULE_PAYMENT_FATZEBRA_TEXT_ADMIN_TITLE', 'Fat Zebra'); 
  define('MODULE_PAYMENT_FATZEBRA_TEXT_DESCRIPTION', '<a target="_blank" href="https://www.fatzebra.com.au/support/testing">Testing Details</a>');
  
  // Catalog Items
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CATALOG_TITLE', 'Credit Card');  // Payment option title as displayed to the customer
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE', 'Credit Card Type:');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_TYPE_AUTO', '(Detected automatically from the card number)');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_OWNER', 'Name on Credit Card:');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_NUMBER', 'Credit Card Number:');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CREDIT_CARD_EXPIRES', 'Credit Card Expiry Date:');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CVV', 'CVV Number (<a target="_blank" href="'.zen_href_link(FILENAME_POPUP_CVV_HELP).'" onclick="popupWindow(\''.str_replace('/','\/', zen_href_link(FILENAME_POPUP_CVV_HELP)) . '\'); return false;">' . 'More Info' . '</a>):');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_CVV_OMITTED', 'No CVV code was entered. Omitting it when your Card has one causes the transaction to fail.');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_OWNER', '* The owner\'s name of the credit card must be at least ' . CC_OWNER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_NUMBER', '* The credit card number must be at least ' . CC_NUMBER_MIN_LENGTH . ' characters.\n');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_JS_CC_CVV', '* The 3 or 4 digit CVV number must be entered.\n');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_DECLINED_MESSAGE', 'Payment was declined for the following reason:');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_DECLINED_MESSAGE_TRY_AGAIN', 'Please correct your Card information and try again, alternatively contact us or your Card provider if you need assistance.');
  define('MODULE_PAYMENT_FATZEBRA_TEXT_ERROR', 'Credit Card Error!');
?>