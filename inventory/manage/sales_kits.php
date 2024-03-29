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
$page_security = 'SA_SALESKIT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Sales Kits & Alias Codes"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/includes/manufacturing.inc");

check_db_has_stock_items(_("There are no items defined in the system."));

simple_page_mode(true);
/*
if (isset($_GET['item_code']))
{
	$_POST['item_code'] = $_GET['item_code'];
	$selected_kit =  $_GET['item_code'];
}
*/
//--------------------------------------------------------------------------------------------------
function display_kit_items($selected_kit)
{
	$result = get_item_kit($selected_kit);
	div_start('bom');
	start_table(TABLESTYLE, "width=60%");
	$th = array(_("Stock Item"), _("Description"), _("Quantity"), _("Units"),
		'','');
	table_header($th);

	$k = 0;
	while ($myrow = db_fetch($result))
	{

		alt_table_row_color($k);

		label_cell($myrow["stock_id"]);
		label_cell($myrow["comp_name"]);
        qty_cell($myrow["quantity"], false, 
			$myrow["units"] == '' ? 0 : get_qty_dec($myrow["comp_name"]));
        label_cell($myrow["units"] == '' ? _('kit') : $myrow["units"]);
 		edit_button_cell("Edit".$myrow['id'], _("Edit"));
 		delete_button_cell("Delete".$myrow['id'], _("Delete"));
        end_row();

	} //END WHILE LIST LOOP
	end_table();
div_end();
}

//--------------------------------------------------------------------------------------------------

function update_component($kit_code, $selected_item)
{
	global $Mode, $Ajax, $selected_kit;
	//display_error(_($_POST));
//		set_focus('quantity');
//	
        
           if(isset($_POST['editable']))
                $ed=1;
            else
                $ed=0;
            
            
    if($_POST['quantity']==0)
    {
        	display_error(_("The quantity entered must be numeric and greater than zero."));
		set_focus('quantity');
		return;
        
    }
				//return;
	if (!check_num('quantity', 0))
	{
		display_error(_("The quantity entered must be numeric and greater than zero."));
		set_focus('quantity');
		return;
	}
   	elseif ($_POST['description'] == '')
   	{
      	display_error( _("Item code description cannot be empty."));
		set_focus('description');
		return;
   	}
	elseif ($selected_item == -1)	// adding new item or new alias/kit
	{
		if (get_post('item_code') == '') { // New kit/alias definition
			$kit = get_item_kit($_POST['kit_code']);
    		if (db_num_rows($kit)) {
			  	$input_error = 1;
    	  		display_error( _("This item code is already assigned to stock item or sale kit."));
				set_focus('kit_code');
				return;
			}
			if (get_post('kit_code') == '') {
	    	  	display_error( _("Kit/alias code cannot be empty."));
				set_focus('kit_code');
				return;
			}
		}
   	}

	if (check_item_in_kit($selected_item, $kit_code, $_POST['component'], true)) {
		display_error(_("The selected component contains directly or on any lower level the kit under edition. Recursive kits are not allowed."));
		set_focus('component');
		return;
	}

		/*Now check to see that the component is not already in the kit */
	if (check_item_in_kit($selected_item, $kit_code, $_POST['component'])) {
		display_error(_("The selected component is already in this kit. You can modify it's quantity but it cannot appear more than once in the same kit."));
		set_focus('component');
		return;
	}
	if ($selected_item == -1) { // new item alias/kit
		if ($_POST['item_code']=='') {
			$kit_code = $_POST['kit_code'];
			$selected_kit = $_POST['item_code'] = $kit_code;
			$msg = _("New alias code has been created.");
		} 
		 else
			$msg =_("New component has been added to selected kit.");

		add_item_code_sib( $kit_code, get_post('component'), get_post('description'),'',
			 get_post('category'), input_num('quantity'), 0,$ed);
		display_notification($msg);

	} else {
            
         
            
		$props = get_kit_props($_POST['item_code']);
		update_item_code($selected_item, $kit_code, get_post('component'),
			$props['description'], $props['category_id'], input_num('quantity'), 0,$ed);
		display_notification(_("Component of selected kit has been updated."));
	}
	$Mode = 'RESET';
	$Ajax->activate('_page_body');
}

//--------------------------------------------------------------------------------------------------

if (get_post('update_name')) {
	update_kit_props(get_post('item_code'), get_post('description'), get_post('category'),get_post('editable'));
	display_notification(_('Kit common properties has been updated'));
	$Ajax->activate('_page_body');
}

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{	update_component($_POST['item_code'], $selected_id); 


}

if ($Mode == 'Delete')
{
	// Before removing last component from selected kit check 
	// if selected kit is not included in any other kit. 
	// 
	$other_kits = get_where_used($_POST['item_code']);
	$num_kits = db_num_rows($other_kits);

	$kit = get_item_kit($_POST['item_code']);
	if ((db_num_rows($kit) == 1) && $num_kits) {

		$msg = _("This item cannot be deleted because it is the last item in the kit used by following kits")
			.':<br>';

		while($num_kits--) {
			$kit = db_fetch($other_kits);
			$msg .= "'".$kit[0]."'";
			if ($num_kits) $msg .= ',';
		}
		display_error($msg);
	} else {
		delete_item_code($selected_id);
		display_notification(_("The component item has been deleted from this bom"));
		$Mode = 'RESET';
	}
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	unset($_POST['quantity']);
	unset($_POST['component']);
}
//--------------------------------------------------------------------------------------------------

start_form();

echo "<center>" . _("Select a sale kit:") . "&nbsp;";
echo sales_kits_list('item_code', null, _('New kit'), true);
echo "</center><br>";
$props = get_kit_props($_POST['item_code']);

if (list_updated('item_code')) {
	if (get_post('item_code') == '')
		$_POST['description'] = '';
	$Ajax->activate('_page_body');
}

$selected_kit = $_POST['item_code'];
//----------------------------------------------------------------------------------
if (get_post('item_code') == '') {
// New sales kit entry
	start_table(TABLESTYLE2);
	text_row(_("Alias/kit code:"), 'kit_code', null, 20, 21);
} else
{
	 // Kit selected so display bom or edit component
	$_POST['description'] = $props['description'];
	$_POST['category'] = $props['category_id'];
	start_table(TABLESTYLE2);
	text_row(_("Description:"), 'description', null, 50, 200);
	stock_categories_list_row(_("Category:"), 'category', null);
	submit_row('update_name', _("Update"), false, 'align=center colspan=2', _('Update kit/alias name'), true);
	end_row();
	end_table(1);
	display_kit_items($selected_kit);
	echo '<br>';
	start_table(TABLESTYLE2);
}

	if ($Mode == 'Edit') {
		$myrow = get_item_code($selected_id);
            //    print_r($myrow);
		$_POST['component'] = $myrow["stock_id"];
		$_POST['quantity'] = number_format2($myrow["quantity"], get_qty_dec($myrow["stock_id"]));
               $_POST['editable']=$myrow["editable"];
	}
	hidden("selected_id", $selected_id);
	
	sales_local_items_list_row(_("Component:"),'component', null, false, true);

//	if (get_post('description') == '')
//		$_POST['description'] = get_kit_name($_POST['component']);
	if (get_post('item_code') == '') { // new kit/alias
		if ($Mode!='ADD_ITEM' && $Mode!='UPDATE_ITEM') {
			$_POST['description'] = $props['description'];
			$_POST['category'] = $props['category_id'];
		}
		text_row(_("Description:"), 'description', null, 50, 200);
		stock_categories_list_row(_("Category:"), 'category', null);
	}
	$res = get_item_edit_info(get_post('component'));
	$dec =  $res["decimals"] == '' ? 0 : $res["decimals"];
	$units = $res["units"] == '' ? _('kits') : $res["units"];
	if (list_updated('component')) 
	{
		$_POST['quantity'] = number_format2(1, $dec);
		$Ajax->activate('quantity');
		$Ajax->activate('category');
                 
	}
	
	qty_row(_("Quantity:"), 'quantity', number_format2(1, $dec), '', $units, $dec);
        if($_POST['editable']==1)
            $checked='checked="checked"';
            //    else
             //       $checked='';
        
        echo '<tr><td class="label">Editable:</td>
<td><input class="amount" type="checkbox" '.$checked.' name="editable" size="15" maxlength="15" dec="0" value="1"></td>
</tr>';
        //qty_row(_("Quantity:"), 'quantity', number_format2(1, $dec), '', $units, $dec);

	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
//----------------------------------------------------------------------------------

end_page();

?>
