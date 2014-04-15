<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

print_r($_SESSION); 
exit;

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Customers"), @$_REQUEST['popup'], false, "", $js); 

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/contacts_view.inc");


	begin_transaction();
        
		add_customer($_POST['cus_name'], $_POST['cus_name'], $_POST['cus_address'],
			'00', 'PKR', '', '',
			'1', '4', 0, 0,
			'10000', 1, '');

		$selected_id = $_POST['customer_id'] = db_insert_id();
         
		if (isset($auto_create_branch) && $auto_create_branch == 1)
		{
        	add_branch($selected_id, $_POST['cus_name'], $_POST['cus_name'],
                $_POST['cus_address'], '1', 1, 2, '',
                get_company_pref('default_sales_discount_act'), get_company_pref('debtors_act'), get_company_pref('default_prompt_payment_act'),
                'DEF', $_POST['cus_address'], 0, 0, 1, '');
                
        	$selected_branch = db_insert_id();
        
			add_crm_person($_POST['cus_name'], $_POST['cus_name'], '', $_POST['cus_address'], 
				$_POST['cus_phone'], '', '', $_POST['cus_email'], '', '');

			$pers_id = db_insert_id();
			add_crm_contact('cust_branch', 'general', $selected_branch, $pers_id);

			add_crm_contact('customer', 'general', $selected_id, $pers_id);
		}
                
		commit_transaction();

		display_notification(_("A new customer has been added."));
                //$_SESSION['add_cus1']=1;
                //$_SESSION['Items']->customer_id=$selected_id;
                        
              header("Location: ../sales_order_entry.php?NewOrder=Yes&add=$selected_id");
                
                
?>
