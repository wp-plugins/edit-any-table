<?php
//add the admin options page
add_action('admin_menu','eat_admin_add_page');

function eat_admin_add_page()
{
	add_options_page('Edit Any Table Options','Edit Any Table','manage_options','eat_options','eat_options_page');
}

function eat_options_page()
{
	?>

	<h2>Edit Any Table Options</h2>
	Configure the plugin here
	<form action="options.php" method="post">
	<?php 
		settings_fields('eat_options');
		do_settings_sections('eat1');
		do_settings_sections('eat2');
		do_settings_sections('eat3');
	?>
	<br />
	<input name="Submit" type="submit"  value="<?php esc_attr_e('Save Changes'); ?>" />
	<?php
	$options = get_option('eat_options');
	//test connection
	$test = new wpdb($options['eat_user'],$options['eat_pwd'],$options['eat_db'],$options['eat_host']);
	
	if(!$test->dbh)
	{
		echo "<br /><strong>Unable to connect to your external database</strong><br />Check your Database Settings";		
	}
	//visible in dashboard?
	if($options['eat_editor'] != 'yes' && $options['eat_admin'] != 'yes')
	{
			echo '<br /><strong>No one will see this in the Dashboard</strong><br />Check the Admin Settings';
	}
	?>
	</form></div>	
	<?php
	

	
}

//add the admin settings host, database, user, password
add_action('admin_init','eat_admin_init');
function eat_admin_init()
{
	register_setting('eat_options','eat_options','eat_options_validate');
	add_settings_section('eat_main', 'Database Settings','eat_db_section_text','eat1');
	add_settings_field('eat_host','Host','display_eat_host','eat1','eat_main');
	add_settings_field('eat_db','Database','display_eat_db','eat1','eat_main');
	add_settings_field('eat_user','Username','display_eat_user','eat1','eat_main');
	add_settings_field('eat_pwd','Password','display_eat_pwd','eat1','eat_main');
	add_settings_section('eat_main', 'Admin Settings','eat_ad_section_text','eat2');
	add_settings_field('eat_admin', 'Admin Access', 'display_eat_admin','eat2','eat_main');
	add_settings_field('eat_editor', 'Editor Access', 'display_eat_editor','eat2','eat_main');
	add_settings_section('eat_main', 'Display Settings','eat_ds_section_text','eat3');
	add_settings_field('eat_cols', 'Default number of search results to display at a time', 'display_eat_cols','eat3','eat_main');
	add_settings_field('eat_friendly','Enter a friendly name to be displayed for the database (leave blank to display actual name','display_eat_friendly','eat3','eat_main');
}

function display_eat_friendly()
{
	$options = get_option('eat_options');
	echo "<input id='eat_friendly' name='eat_options[eat_friendly]' size='40' type='text' value='{$options['eat_friendly']}' />"; 	
}

function display_eat_cols()
{
	$options = get_option('eat_options');
	echo "<input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='1' ".($options['eat_cols']=='1'?'checked':'')."/>1
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='2' ".($options['eat_cols']=='2'?'checked':'')."/>2
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='3' ".($options['eat_cols']=='3'?'checked':'')."/>3
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='4' ".($options['eat_cols']=='4'?'checked':'')."/>4
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='5' ".($options['eat_cols']=='5'?'checked':'')."/>5
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='6' ".($options['eat_cols']=='6'?'checked':'')."/>6
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='7' ".($options['eat_cols']=='7'?'checked':'')."/>7
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='8' ".($options['eat_cols']=='8'?'checked':'')."/>8
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='9' ".($options['eat_cols']=='9'?'checked':'')."/>9
		  <input id='eat_cols' name='eat_options[eat_cols]'  type='radio' value='10' ".($options['eat_cols']=='10'?'checked':'')."/>10";
}

function display_eat_editor()
{
	$options = get_option('eat_options');
	echo "<input id='eat_editor' name='eat_options[eat_editor]'  type='checkbox' value='yes' ".($options['eat_editor']=='yes'?'checked':'')." />"; 
}

function display_eat_admin()
{
	$options = get_option('eat_options');
	echo "<input id='eat_admin' name='eat_options[eat_admin]'  type='checkbox' value='yes' ".($options['eat_admin']=='yes'?'checked':'')." />"; 
}


function display_eat_host()
{
	$options = get_option('eat_options');
	echo "<input id='eat_host' name='eat_options[eat_host]' size='40' type='text' value='{$options['eat_host']}' />"; 
}

function display_eat_db()
{
	$options = get_option('eat_options');
	echo "<input id='eat_db' name='eat_options[eat_db]' size='40' type='text' value='{$options['eat_db']}' />"; 
}

function display_eat_user()
{
	$options = get_option('eat_options');
	echo "<input id='eat_user' name='eat_options[eat_user]' size='40' type='text' value='{$options['eat_user']}' />"; 
}

function display_eat_pwd()
{
	$options = get_option('eat_options');
	echo "<input id='eat_pwd' name='eat_options[eat_pwd]' size='40' type='password' value='{$options['eat_pwd']}' />"; 
}


function eat_options_validate($input)
{
	
	$output = $input;
	return $output;
}

function eat_db_section_text()
{
?>
	<p>Enter the values to enable connection to your chosen database</p>
<?php
}

function eat_ad_section_text()
{
?>
	<p>Who has access to the Edit Any Table Dashboard Widget?</p>
<?php
}

function eat_ds_section_text()
{
?>
	<p>Edit any Table displays best in a one column layout.  If you are using more than one column you may want to change how some elements are displayed</p>
<?php
}





?>