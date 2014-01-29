<?php
/*
Plugin Name: Edit Any Table
Plugin URI: http://redeyedmonster.co.uk/edit-any-table/
Description: Dashboard widget which allows the editing of all tables in any database
Version: 2.0.0
Author: Nigel Bachmann
Text Domain: EditAnyTable
Domain Path: /languages
Author URI: http://redeyedmonster.co.uk
License: GPL2

Copyright 2012  Nigel Bachmann  (email : nigel@redeyedmonster.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


add_action('plugins_loaded','EditAnyTable_init');
function EditAnyTable_init()
{
    //$plugin_dir = plugin_dir_url(__FILE__);//$plugin_dir.'languages'
    load_plugin_textdomain( 'EditAnyTable', false, basename(dirname(__FILE__)).'/languages/');
}


//load the options page
require('eat_options.php');


// main function for dashboard widget
function EditAnyTable()
{

	require('eat_scripts.js');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style("wp-jquery-ui-dialog");

	$options = get_option('eat_options');
	$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);

	if(!$eat_db->dbh)
	{
			echo '<strong>'.__('Unable to connect to database, check your settings','EditAnyTable').'</strong>';
			return;
	}
		
	//$result = $eat_db->get_col($eat_db->prepare("show tables",null));
	
	?>
	
	<!-- Store the number of columns to be displayed which can be passed across to the next page -->
	<input type="hidden" id="eat_cols" value="<?php echo $options['eat_cols']; ?>" />
	<!-- get and store the plugin path so that it is accessible -->
	<input type="hidden" id="eat_path" value="<?php echo plugin_dir_url(__FILE__); ?>" />
	
	<!-- Show a link to instructions -->
	<a href="http://redeyedmonster.co.uk/edit-any-table#using"><?php _e('Instructions','EditAnyTable');?></a><br /><br />

	
	<button class="button-primary" title="<?php _e('Open selected table','EditAnyTable');?>" id="buttonGo"><?php _e('Open','EditAnyTable'); ?></button>
    <select id="selectedTable">
			<option value="NONE">*<?php _e('Choose Table to Edit','EditAnyTable') ?>*&nbsp;&nbsp;</option>
			
	<?php


    foreach($options as $option)
    {

        if( strpos($option,'eat_table_') !== false)
        {
            //only show tables selected in the settings
            $tableName = substr($option,10);

            ?>
            <option value="<?php echo $tableName; ?>"><?php echo $tableName; ?></option>
            <?php
        }

    }
	
	?>
	</select>
	<?php _e('on database','EditAnyTable');?>: <strong><?php echo ($options['eat_friendly']==""?$options['eat_db']:$options['eat_friendly']) ?></strong>
	<div id="outputDiv"></div>
	
	<?php
	
}

add_action('wp_ajax_UpdateRecord','UpdateSelected');
function UpdateSelected()
{
	//get the posted values
	$table2Edit = $_POST['table2Edit'];
	$keys = $_POST['keys'];
	$values = $_POST['values'];
	$keysU = $_POST['keysU'];
	$valuesU = $_POST['valuesU'];
	

	// get the key/value pairs for the update primaries
	$keysArray = explode("~", $keys);
	$valsArray = explode("~", $values);
	// get the key/value pairs for the update sets
	$keysUArray = explode("~", $keysU);
	$valsUArray = explode("~", $valuesU);
	
	
	if(count($keysArray)==0)
	{
			echo '<br />'.__('Cannot update this record because there are no primary keys in the table','EditAnyTable');
	}
	else
	{

		//build where array
		$whereArray = array();
		for($i = 0;$i < count($keysArray); $i++)
		{
			if($keysArray[$i] != "")
			{
				$newParam = array($keysArray[$i] => sanitize_text_field($valsArray[$i]));
				$whereArray = array_merge($whereArray,$newParam);
			}
		}
				
		//build set commands
		$setArray = array();
		for($i = 0;$i < count($keysUArray); $i++)
		{
			if($keysUArray[$i] != "")
			{
				$newParam = array($keysUArray[$i] => sanitize_text_field($valsUArray[$i]));
				$setArray = array_merge($setArray,$newParam);
			}
		}
		
		//Connect to the database
		$options = get_option('eat_options');
		$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
		
		if($eat_db->update($table2Edit,$setArray,$whereArray))
		{
			echo '<br /><strong>'.__('Record Updated','EditAnyTable').'</strong>';
		}
		else
		{
			echo '<br /><strong>'.__('Unable to update record','EditAnyTable').'</strong><br />';
			$eat_db->show_errors();
			$eat_db->print_error();
			$eat_db->hide_errors();
		}
		
	}
	
	die();
	
	
}

add_action('wp_ajax_DeleteRecord','DeleteSelected');
function DeleteSelected()
{
	//get the posted values
	$table2Edit = $_POST['table2Edit'];
	$keys = $_POST['keys'];
	$values = $_POST['values'];
	

	// get the key/value pairs for the delete
	$keysArray = explode("~", $keys);
	$valsArray = explode("~", $values);
		
	if(count($keysArray)==0)
	{
			echo '<br />'.__('Cannot delete this record because there are no primary keys in the table','EditAnyTable');
	}
	else
	{
		//Connect to the database
		$options = get_option('eat_options');
		$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
		
		//Get a single record for column info
		$sql = $eat_db->prepare("select * from ".$table2Edit." LIMIT 1",null);
		//echo $sql."<br />";
		$records = $eat_db->get_results($sql,'ARRAY_N');
		
		//get column information
		$cols = $eat_db->get_col_info('name',-1);
		$numeric = $eat_db->get_col_info('numeric',-1);
		
		//build where
		$where = "";
		$vals = array();
		for($i = 0;$i < count($keysArray); $i++)
		{
		
			//need to find out if the value is for a numeric field or not
			$isNumeric = 0;
			for($in = 0; $in < count($cols); $in++)
			{
				if($cols[$in] == $keysArray[$i])
				{
					$isNumeric = $numeric[$in] == 1;
				}
			}
		
			if($keysArray[$i] != "")
			{
				if($i != 0)
				{
					$where = $where." and ";
				}
				
				if($isNumeric)
				{
					$where = $where.$keysArray[$i]." = %d";
				}
				else
				{
					$where = $where.$keysArray[$i]." = %s";
				}
				$vals[] = sanitize_text_field($valsArray[$i]);
				
			}
		}
		//echo $where;
		
		//prepare the delete statement
		$sql = $eat_db->prepare("DELETE from ".$table2Edit." where ".$where, $vals);
		//echo $sql;
		$result = $eat_db->query($sql);
		if($result)
		{
			echo '<br /><strong>'.__('Record Deleted','EditAnyTable').'</strong>';
		}
		else
		{
			echo '<br /><strong>'.__('Unable to delete record','EditAnyTable').'</strong><br />';
			$eat_db->show_errors();
			$eat_db->print_error();
			$eat_db->hide_errors();
		}
		
	}
	
	die();
	
}

add_action('wp_ajax_AddRecord','CreateRecord');
function CreateRecord()
{
	//get the posted values
	$table2Edit = $_POST['table2Edit'];
	$keys = $_POST['keys'];
	$values = $_POST['values'];
	$eat_cols = $_POST['eat_cols'];
    $offSet = "0";

	?>
	<!-- Store the values we need but don't want to show in hidden fields which can be passed across to the next page -->
	<input type="hidden" id="eat_cols" value="<?php echo $eat_cols; ?>" />	
	<input type="hidden" id="keys" value="<?php echo $keys ?>" />
	<input type="hidden" id="values" value="<?php echo $values ?>" />
	<input type="hidden" id="offSet" value="<?php echo $offSet ?>" />
	
	<?php
	
	// get key/value pairs for the insert
	$keysArray = explode("~", $keys);
	$valsArray = explode("~", $values);
	
	//build the array for the insert
	$insertArray=array();
	for($i = 0;$i < count($keysArray); $i++)
	{
		if($keysArray[$i] != "")
		{
			$newParam = array($keysArray[$i] => sanitize_text_field($valsArray[$i]));
			$insertArray = array_merge($insertArray,$newParam);
		}
	}
	
	//Connect to the database
	$options = get_option('eat_options');
	$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
	
	if($eat_db->insert($table2Edit,$insertArray))
	{
		echo '<br />'.__('New Record Created','EditAnyTable');
	}
	else
	{
		echo '<br />'.__('Unable to create new record','EditAnyTable').'<br />';
		$eat_db->show_errors();
		$eat_db->print_error();
		$eat_db->hide_errors();	
	}
	
	die();
}


//PHP functions to handle the Ajax requests
add_action('wp_ajax_GetRecords','ReturnRecords');
function ReturnRecords()
{
	$table2Edit = $_POST['table2Edit'];
	$keys = $_POST['keys'];
	$values = $_POST['values'];
	$offSet = $_POST['offSet'];
	$eat_cols = $_POST['eat_cols'];
	$fuzzy = $_POST['fuzzy'];
	
	?>
	<!-- Store the values we need but don't want to show in hidden fields which can be passed across to the next page -->
	<input type="hidden" id="eat_cols" value="<?php echo $eat_cols; ?>" />	
	<input type="hidden" id="keys" value="<?php echo $keys ?>" />
	<input type="hidden" id="values" value="<?php echo $values ?>" />
	<input type="hidden" id="offSet" value="<?php echo $offSet ?>" />
	<input type="hidden" id="fuzzy" value="<?php echo $fuzzy ?>" />
	
		
	<?php
	
	// get the users data
	$keysArray = explode("~", $keys);
	$valsArray = explode("~", $values);
	//Connect to the database
	$options = get_option('eat_options');
	$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
	
	//Get a single record for column info
	$sql = $eat_db->prepare("select * from ".$table2Edit." LIMIT 1",null);
	//echo $sql."<br />";
	$records = $eat_db->get_results($sql,'ARRAY_N');
	
	//get column information
	$cols = $eat_db->get_col_info('name',-1);
	$types = $eat_db->get_col_info('type',-1);
	$primary = $eat_db->get_col_info('primary_key',-1);
	$numeric = $eat_db->get_col_info('numeric',-1);
		
	//build where
	$where = "";
	$vals = array();
	for($i = 0;$i < count($keysArray); $i++)
	{
	
		//need to find out if the value is for a numeric field or not
		$isNumeric = 0;
		for($in = 0; $in < count($cols); $in++)
		{
			if($cols[$in] == $keysArray[$i])
			{
				$isNumeric = $numeric[$in] == 1;
			}
		}
	
		if($keysArray[$i] != "")
		{
			if($i != 0)
			{
				$where = $where." and ";
			}
			
			if($isNumeric)
			{
				$where = $where.$keysArray[$i]." = %d";
				$vals[] = sanitize_text_field($valsArray[$i]);
			}
			else
			{
				if($fuzzy == "checked")
				{
					$where = $where.$keysArray[$i]." like %s";
					$vals[] = sanitize_text_field('%'.$valsArray[$i].'%');
				}
				else
				{
					$where = $where.$keysArray[$i]." = %s";
					$vals[] = sanitize_text_field($valsArray[$i]);
				}
			}
			
			
		}
	}
		
	//Get the records
	if(count($vals)>0)	
	{
		$sql = $eat_db->prepare("select * from ".$table2Edit." where ".$where." LIMIT ".$offSet.", ".$eat_cols."",$vals);
	}	
	else	
	{			
		$sql = $eat_db->prepare("select * from ".$table2Edit." LIMIT ".$offSet.", ".$eat_cols."",null);
	}
	$records = $eat_db->get_results($sql,'ARRAY_N');
	
	//lets work out how many columns we're going to display (max from options)
	$numCols = $eat_db->num_rows;
	?>
	<hr>
	<?php
	if($offSet > 0)
	{
	?>
	<button class="button" id="buttonPrev">&lt;&lt; <?php echo __('Previous','EditAnyTable').' '.$eat_cols ?></button>&nbsp;
	<?php
	}
	if($numCols == (int)$eat_cols)
	{
	?>
	<button class="button" id="buttonNext"><?php echo __('Next','EditAnyTable').' '.$eat_cols ?> &gt;&gt;</button>
	<?php
	}
	if($numCols > 0)
	{
			
		?>
		<div style="overflow: auto">
			<table id="tableCols">
				<tr>
					<td><strong><?php _e('Column','EditAnyTable'); ?></strong></td>
			<?php
			for($i = 0; $i < $numCols; $i++)
			{
				
				
				?>
				<td></td>
				<?php
				
			}
				?>
				</tr>
				<?php
			//need to write the results vertically
			for($i = 0; $i < count($cols); $i++)
			{
				?>
				<tr>
					<td><?php echo $cols[$i]; ?></td>
				<?php
				
				for($in = 0; $in < $numCols; $in++)
				{
					$row = $records[$in];
					if($primary[$i] == 1)
					{
						?>
						<td style="background-color:white" id="PRIMARY:<?php echo $cols[$i]; ?>"><?php echo $row[$i]; ?></td>
						<?php
					}
					else
					{
						?>
						<td id="<?php echo $cols[$i]; ?>"><input type="text"  value="<?php echo sanitize_text_field($row[$i]); ?>" /></td>
						<?php
					}
				}
				?>
				</tr>
				<?php
			}
			?>
				<tr>
					<td></td>
				<?php
				for($i = 0; $i < $numCols; $i++)
				{
                    if(in_array(1,$primary)) //Do not show save or delete buttons if there is no primary key
                    {

                ?>
                        <td>
                            <?php
                            // Check that editor has rights to add
                            if(current_user_can('administrator') || (current_user_can('editor') && $options['eat_editorPrivEdit']=='yes'))
                            {
                               ?>
                                <button class="button-primary" id="save<?php echo $i+1; ?>"><?php _e('Save','EditAnyTable'); ?></button>&nbsp;
                                <?php
                            }

                            // Check that editor has rights to delete
                            if(current_user_can('administrator') || (current_user_can('editor') && $options['eat_editorPrivDelete']=='yes'))
                            {
                                ?>
                                <button class="button-primary" id="delete<?php echo $i+1; ?>"><?php _e('Delete','EditAnyTable'); ?></button>
                                <?php
                            }

                            ?>
                        </td>
                        <?php
                    }
				}
				?>
				</tr>
			</table>
		</div>
		<?php
		
	}
	else
	{
		_e('No Results Found','EditAnyTable');
	}
	
	die();
}


add_action('wp_ajax_GetTable','TableDetails');
function TableDetails()
{
    //get options
    $options = get_option('eat_options');

	//Get required values
	$table2Edit = $_POST['table2Edit'];
	$eat_cols = $_POST['eat_cols'];
	
	//connect to the database
	$options = get_option('eat_options');
	$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
		
	//get a sample row
	$result = $eat_db->get_results("select * from ".$table2Edit." LIMIT 0, 1");
	
	//get column information
	$cols = $eat_db->get_col_info('name',-1);
	$types = $eat_db->get_col_info('type',-1);
	$primary = $eat_db->get_col_info('primary_key',-1);
	
	
	//build the table
	//if($eat_db->num_rows > 0) Removed for 1.3.0
	//{
		?>
		<hr>
		<div>
			<button class="button-primary" title="Find records matching the values entered" id="buttonFind"><?php _e('Find','EditAnyTable'); ?></button>
			&nbsp;
			<input type="checkbox" id="fuzzy" />&nbsp;<?php _e('Fuzzy','EditAnyTable'); ?>
            <?php
            // Check that editor has rights to add
            if(current_user_can('administrator') || (current_user_can('editor') && $options['eat_editorPrivAdd']=='yes'))
            {
            ?>
			&nbsp;
			<button class="button-primary" title="<?php _e('Add a new record with the values entered','EditAnyTable');?>" id="buttonAdd"><?php _e('Add','EditAnyTable'); ?></button>
            <?php
            }
            ?>
			&nbsp;
			<button class="button" title="<?php _e('Clear all the values','EditAnyTable');?>" id="buttonReset"><?php _e('Reset','EditAnyTable');?></button>
		</div>
		<hr>
		<div style="overflow: auto">
			<table id="tableCols">
				<tr>
					<td><strong><?php _e('Column','EditAnyTable');?></strong></td>
					<td><strong><?php _e('Value','EditAnyTable');?></strong></td>
				</tr>
			<?php
				for($i=0;$i<count($cols);$i++)
				{
				?>
					<tr>
						<td>
							<?php 
								echo $cols[$i]." (".$types[$i].")"; 
								if($primary[$i]==1)
								{
									echo " [PRIMARY]";
								}
							?>
							
						</td>
						<td>
							<input type="text" id="<?php echo sanitize_text_field($cols[$i]); ?>" />
						</td>
						
					</tr>
				<?php
				}
				?>
			</table>
		</div>
		<?php
	//}

	die();
}

//hook it up
function add_dashboard_widget_eat()
{
	$options = get_option('eat_options');
	if(((current_user_can('administrator') && $options['eat_admin']=='yes')||((current_user_can('administrator') || current_user_can('editor')) && $options['eat_editor']=='yes')) && $options['eat_display']=='widget')
	{
		wp_add_dashboard_widget('eat', 'Edit Any Table', 'EditAnyTable');
	}
}
add_action('wp_dashboard_setup','add_dashboard_widget_eat');

//Create separate page for plugin
add_action('admin_menu', 'edit_any_table_menu');

function edit_any_table_menu() {
    $options = get_option('eat_options');
    if(((current_user_can('administrator') && $options['eat_admin']=='yes')||((current_user_can('administrator') || current_user_can('editor')) && $options['eat_editor']=='yes')) && $options['eat_display']=='page')
    {
    add_dashboard_page('Edit Any Table', 'Edit Any Table', 'read', 'edit_any_table', 'EditAnyTable');
    }
}

// Add settings link on plugin page
function your_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=eat_options.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'your_plugin_settings_link' );

?>