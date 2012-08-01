<?php
/*
Plugin Name: Edit Any Table
Plugin URI: http://redeyedmonster.co.uk/2012/07/04/edit-any-table/
Description: Dashboard widget which allows the editing of all tables in any database
Version: 1.1.0
Author: Nigel Bachmann
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
			echo "<strong>Unable to connect to database, check your settings</strong>";
			return;
	}
		
	$result = $eat_db->get_col($eat_db->prepare("show tables"));
	
	?>
	
	<!-- Store the number of columns to be displayed which can be passed across to the next page -->
	<input type="hidden" id="eat_cols" value="<?php echo $options['eat_cols']; ?>" />
	<!-- get and store the plugin path so that it is accessable -->
	<input type="hidden" id="eat_path" value="<?php echo plugin_dir_url(__FILE__); ?>" />
	<button class="button-primary" title="Open selected table" id="buttonGo">Open</button>
	<select id="selectedTable">
			<option value="NONE">*Choose Table to Edit*&nbsp;&nbsp;</option>
			
	<?php
	
	foreach($result as $table)
	{
		?>
		<option value="<?php echo $table; ?>"><?php echo $table; ?></option>
		<?php
	}
	
	?>
	</select>
	on database: <strong><?php echo ($options['eat_friendly']==""?$options['eat_db']:$options['eat_friendly']) ?></strong>
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
			echo "<br />Cannot update this record because there are no primary keys in the table";
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
			echo "<br /><strong>Record Updated</strong>";
		}
		else
		{
			echo "<br /><strong>Unable to update record</strong><br />";
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
			echo "<br />Cannot delete this record because there are no primary keys in the table";
	}
	else
	{
		//Connect to the database
		$options = get_option('eat_options');
		$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
		
		//Get a single record for column info
		$sql = $eat_db->prepare("select * from ".$table2Edit." LIMIT 1");
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
			echo "<br /><strong>Record Deleted</strong>";
		}
		else
		{
			echo "<br /><strong>Unable to delete record</strong><br />";
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
		echo "<br />New Record Created";
	}
	else
	{
		echo "<br />Unable to create new record<br />";
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
	
	?>
	<!-- Store the values we need but don't want to show in hidden fields which can be passed across to the next page -->
	<input type="hidden" id="eat_cols" value="<?php echo $eat_cols; ?>" />	
	<input type="hidden" id="keys" value="<?php echo $keys ?>" />
	<input type="hidden" id="values" value="<?php echo $values ?>" />
	<input type="hidden" id="offSet" value="<?php echo $offSet ?>" />
	
	<?php
	
	// get the users data
	$keysArray = explode("~", $keys);
	$valsArray = explode("~", $values);
	//Connect to the database
	$options = get_option('eat_options');
	$eat_db = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
	
	//Get a single record for column info
	$sql = $eat_db->prepare("select * from ".$table2Edit." LIMIT 1");
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
			}
			else
			{
				$where = $where.$keysArray[$i]." = %s";
			}
			$vals[] = sanitize_text_field($valsArray[$i]);
			
		}
	}
		
	//Get the records
	$sql = $eat_db->prepare("select * from ".$table2Edit." where ".$where." LIMIT ".$offSet.", ".$eat_cols."",$vals);
	$records = $eat_db->get_results($sql,'ARRAY_N');
	
	//lets work out how many columns we're going to display (max from options)
	$numCols = $eat_db->num_rows;
	?>
	<hr>
	<?php
	if($offSet > 0)
	{
	?>
	<button class="button" id="buttonPrev">&lt;&lt; Previous <?php echo $eat_cols ?></button>&nbsp;
	<?php
	}
	if($numCols == (int)$eat_cols)
	{
	?>
	<button class="button" id="buttonNext">Next <?php echo $eat_cols ?> &gt;&gt;</button>
	<?php
	}
	if($numCols > 0)
	{
			
		?>
		<div style="overflow: auto">
			<table id="tableCols">
				<tr>
					<td><strong>Column</strong></td>
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
						<td id="<?php echo $cols[$i]; ?>"><input type="text" value="<?php echo sanitize_text_field($row[$i]); ?>" /></td>
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
					?>
					<td><button class="button-primary" id="save<?php echo $i+1; ?>">Save</button>&nbsp;<button class="button-primary" id="delete<?php echo $i+1; ?>">Delete</button></td>
					<?php
				}
				?>
				</tr>
			</table>
		</div>
		<?php
		
	}
	else
	{
		echo "No Results Found";
	}
	
	die();
}


add_action('wp_ajax_GetTable','TableDetails');
function TableDetails()
{
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
	if($eat_db->num_rows > 0)
	{
		?>
		<hr>
		<div>
			<button class="button-primary" title="Find records matching the values entered" id="buttonFind">Find</button>
			&nbsp;
			<button class="button-primary" title="Add a new record with the values entered" id="buttonAdd">Add</button>
			&nbsp;
			<button class="button" title="Clear all the values" id="buttonReset">Reset</button>
		</div>
		<hr>
		<div style="overflow: auto">
			<table id="tableCols">
				<tr>
					<td><strong>Column</strong></td>
					<td><strong>Value</strong></td>
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
	}

	die();
}

//hook it up
function add_dashboard_widget_eat()
{
	$options = get_option('eat_options');
	if((current_user_can('administrator') && $options['eat_admin']=='yes')||((current_user_can('administrator') || current_user_can('editor')) && $options['eat_editor']=='yes'))
	{
		wp_add_dashboard_widget('eat', 'Edit Any Table', 'EditAnyTable');
	}
}
add_action('wp_dashboard_setup','add_dashboard_widget_eat');

// Add settings link on plugin page
function your_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=eat_options.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'your_plugin_settings_link' );

?>