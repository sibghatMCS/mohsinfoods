<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Janusz Dobrwolski
// date_:	2008-01-14
// Title:	Print Delivery Notes
// draft version!
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$packing_slip = 0;
//----------------------------------------------------------------------------------------------------

print_deliveries();

//----------------------------------------------------------------------------------------------------

function print_deliveries()
{
	global $path_to_root, $packing_slip, $alternative_tax_include_on_docs, $suppress_tax_rates, $no_zero_lines_amount;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$packing_slip = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$orientation = $_POST['PARAM_5'];

	if (!$from || !$to) return;

	$orientation = ($orientation ? 'L' : 'P');
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);
	$from = min($fno[0], $tno[0]);
	$to = max($fno[0], $tno[0]);

	$cols = array(4, 60, 225, 300, 325, 385, 450, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'right', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		if ($packing_slip == 0)
			$rep = new FrontReport(_('DELIVERY'), "DeliveryNoteBulk", user_pagesize(), 9, $orientation);
		else
			$rep = new FrontReport(_('PACKING SLIP'), "PackingSlipBulk", user_pagesize(), 9, $orientation);
	}
    if ($orientation == 'L')
    	recalculate_cols($cols);
	for ($i = $from; $i <= $to; $i++)
	{
			if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
				continue;
			$myrow = get_customer_trans($i, ST_CUSTDELIVERY);
			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER); // ?
			if ($email == 1)
			{
				$rep = new FrontReport("", "", user_pagesize(), 9, $orientation);
				if ($packing_slip == 0)
				{
					$rep->title = _('DELIVERY NOTE');
					$rep->filename = "Delivery" . $myrow['reference'] . ".pdf";
				}
				else
				{
					$rep->title = _('PACKING SLIP');
					$rep->filename = "Packing_slip" . $myrow['reference'] . ".pdf";
				}
			}
                        $_SESSION['i']=$i;
			$rep->SetHeaderType('Header2');
			$rep->currency = $cur;
			$rep->Font();
			$rep->Info($params, $cols, null, $aligns);

			$contacts = get_branch_contacts($branch['branch_code'], 'delivery', $branch['debtor_no'], true);
			$rep->SetCommonData($myrow, $branch, $sales_order, '', ST_CUSTDELIVERY, $contacts);
			$rep->NewPage();

   			$result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
			$SubTotal = 0;
			while ($myrow2=db_fetch($result))
			{
                            
                              if($myrow2["unit_price"]==0)
                                continue;

                            
                            
				if ($myrow2["quantity"] == 0)
					continue;
					
				$Net = round2(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		if ($myrow2["discount_percent"]==0)
		  			$DisplayDiscount ="";
	    		else
		  			$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
				
                 
                        
$desp=explode('_', $myrow2['StockDescription']);
$rep->TextCol(0, 1,$desp[0], -2);
				$oldrow = $rep->row;
                                
				$rep->TextColLines(1, 2, '', -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				if ($Net != 0.0  || !is_service($myrow2['mb_flag']) || !isset($no_zero_lines_amount) || $no_zero_lines_amount == 0)
				{			
					$rep->TextCol(2, 3,	$DisplayQty, -2);
					$rep->TextCol(3, 4,	$myrow2['units'], -2);
                                           $sql1="SELECT co . * FROM 0_debtor_trans dt, customer_order co WHERE trans_no =$i
                    AND TYPE =13 AND dt.order_ = co.sale_order";
                       
                        $res1=  mysql_query($sql1);
                        $ssqle1=  mysql_fetch_array($res1);
                                        
                                          if($ssqle1['kg']==0)
                        {
//					if ($packing_slip == 0)
//					{
//						$rep->TextCol(4, 5,	$DisplayPrice, -2);
//						$rep->TextCol(5, 6,	$DisplayDiscount, -2);
//						$rep->TextCol(6, 7,	$DisplayNet, -2);
//					}
                        }
				}	
				$rep->row = $newrow;
				//$rep->NewLine(1);
				if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					$rep->NewPage();
			}

			$memo = get_comments_string(ST_CUSTDELIVERY, $i);
			if ($memo != "")
			{
				$rep->NewLine();
				$rep->TextColLines(1, 5, $memo, -2);
			}

   			$DisplaySubTot = number_format2($SubTotal,$dec);
   			$DisplayFreight = number_format2($myrow["ov_freight"],$dec);

    		$rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			$doctype=ST_CUSTDELIVERY;
                          $sql="SELECT co . * FROM 0_debtor_trans dt, customer_order co WHERE trans_no =$i
                    AND TYPE =13 AND dt.order_ = co.sale_order";
                       
                        $res=  mysql_query($sql);
                        $ssqle=  mysql_fetch_array($res);
                       
                     
                        
//                        $rep->NewLine();
//                        
//                        
//                        $rep->TextCol(3, 6, _("Packing Charges"), -2);
//			$rep->TextCol(6, 7,	$ssqle['packing_charges'], -2);
//                        $rep->NewLine();
//                        $rep->TextCol(3, 6, _("Service Charges"), -2);
//			$rep->TextCol(6, 7,	$ssqle['service_charges'], -2);
//                        $rep->NewLine();
//                        $rep->TextCol(3, 6, _("BBQ  Charges"), -2);
//			$rep->TextCol(6, 7,	$ssqle['bbq_charges'], -2);
//                        $rep->NewLine();
//                        
//                         $rep->TextCol(3, 6, _("Fare Charges"), -2);
//			$rep->TextCol(6, 7,	$ssqle['fare_charges'], -2);
//                        $rep->NewLine();
                        
                       
                        
                              $extra=$ssqle['fare_charges'] +$ssqle['service_charges'] + $ssqle['packing_charges'] +$ssqle['bbq_charges'];   
                        
			if ($packing_slip == 0)
			{
				//$rep->TextCol(3, 6, _("Total Charges"), -2);
				//$rep->TextCol(6, 7,	$extra, -2);
//				$rep->NewLine();
//				$rep->TextCol(3, 6, _("Total Charges"), -2);
//				$rep->TextCol(6, 7,	$DisplayFreight, -2);
//				$rep->NewLine();
				$tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $i);
				$first = true;
    			while ($tax_item = db_fetch($tax_items))
    			{
    				if ($tax_item['amount'] == 0)
    					continue;
    				$DisplayTax = number_format2($tax_item['amount'], $dec);
 
 					if (isset($suppress_tax_rates) && $suppress_tax_rates == 1)
 		   				$tax_type_name = $tax_item['tax_type_name'];
 		   			else
 		   				$tax_type_name = $tax_item['tax_type_name']." (".$tax_item['rate']."%) ";
 
 					if ($tax_item['included_in_price'])
    				{
   						if (isset($alternative_tax_include_on_docs) && $alternative_tax_include_on_docs == 1)
    					{
    						if ($first)
    						{
								$rep->TextCol(3, 6, _("Total Tax Excluded"), -2);
								$rep->TextCol(6, 7,	number_format2($tax_item['net_amount'], $dec), -2);
								$rep->NewLine();
    						}
							$rep->TextCol(3, 6, $tax_type_name, -2);
							$rep->TextCol(6, 7,	$DisplayTax, -2);
							$first = false;
    					}
    					else
							$rep->TextCol(3, 7, _("Included") . " " . $tax_type_name . _("Amount") . ": " . $DisplayTax, -2);
					}
    				else
    				{
						$rep->TextCol(3, 6, $tax_type_name, -2);
						$rep->TextCol(6, 7,	$DisplayTax, -2);
					}
					$rep->NewLine();
    			}
                        
                        
//                          $rep->TextCol(3, 6, _("Discount"), -2);
//			$rep->TextCol(6, 7,	$ssqle['discount'], -2);
//                        $rep->NewLine();
//                        
//                        $rep->TextCol(3, 6, _("Advancea"), -2);
//			$rep->TextCol(6, 7,	$ssqle['advance'], -2);
//                        $rep->NewLine();
//                        
//                           $toal=0;
                        if($ssqle['kg']==1)
                        {
                            $SubTotal=$ssqle['total'];
                        }
                        
                       
                      $balnvce=$SubTotal-($ssqle['discount']+$ssqle['advance']) + $ssqle['fare_charges'] +$ssqle['service_charges'] + $ssqle['packing_charges'] +$ssqle['bbq_charges'];   
                        
                      $balnvce=number_format2($balnvce,$dec);
                      $rep->Font('bold');
                      $rep->TextCol(3, 6, _("Net Balance"), -2);
			$rep->TextCol(6, 7,	$balnvce, -2);
                        
    			//$rep->NewLine();
		//		$DisplayTotal = number_format2($myrow["ov_freight"] +$myrow["ov_freight_tax"] + $myrow["ov_gst"] +
		//			$myrow["ov_amount"],$dec);
		//		$rep->Font('bold');
		//		$rep->TextCol(3, 6, _("TOTAL DELIVERY INCL. VAT"), - 2);
		//		$rep->TextCol(6, 7,	$DisplayTotal, -2);
		//		$words = price_in_words($myrow['Total'], ST_CUSTDELIVERY);
		//		if ($words != "")
			//	{
			//		$rep->NewLine(1);
			//		$rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
			//	}	
				$rep->Font();
			}	
			if ($email == 1)
			{
				$rep->End($email);
			}
	}
	if ($email == 0)
		$rep->End();
}

?>