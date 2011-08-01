<?php
/*
Plugin Name: WP CSV to Database
Version: v1.8
Plugin URI: http://www.tipsandtricks-hq.com/?p=2116
Author: Ruhul Amin
Author URI: http://www.tipsandtricks-hq.com/
Description: Simple WordPress plugin to insert CSV file content into any WordPress database table.
*/

define('WP_CSV_TO_DB_VERSION', "1.8");
define('WP_CSV_TO_DB_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_CSV_TO_DB_URL', plugins_url('',__FILE__));

function readAndDump($src_file,$table_name,$column_string="",$start_row=2)
{
	global $wpdb;
	$errorMsg = "";

	if(empty($src_file))
	{
            $errorMsg .= "<br />Input file is not specified";
            return $errorMsg;
    }

	$file_path = csv_to_db_get_abs_path_from_src_file($src_file);	
	
	$file_handle = fopen($file_path, "r");
	if ($file_handle === FALSE) {
		// File could not be opened...
		$errorMsg .= 'Source file could not be opened!<br />';
		$errorMsg .= "Error on fopen('$file_path')";	// Catch any fopen() problems.
		return $errorMsg;
	}
		
	$row = 1;
	while (!feof($file_handle) ) 
	{
		$line_of_text = fgetcsv($file_handle, 1024);
		if ($row < $start_row)
		{
			// Skip until we hit the row that we want to read from.
			$row++;
			continue;
		}
		$columns = count($line_of_text);
		//echo "<br />Column Count: ".$columns."<br />";
		
		if ($columns>1)
		{
	        	$query_vals = "'".$wpdb->escape($line_of_text[0])."'";
	        	for($c=1;$c<$columns;$c++)
	        	{
	        		$line_of_text[$c] = utf8_encode($line_of_text[$c]);
					$line_of_text[$c] = addslashes($line_of_text[$c]);
	                $query_vals .= ",'".$wpdb->escape($line_of_text[$c])."'";
	        	}
	        	//echo "<br />Query Val: ".$query_vals."<br />";
                        $query = "INSERT INTO $table_name ($column_string) VALUES ($query_vals)";
	                
                        //echo "<br />Query String: ". $query;
                        $results = $wpdb->query($query);
                        if(empty($results))
                        {
                            $errorMsg .= "<br />Insert into the Database failed for the following Query:<br />";
                            $errorMsg .= $query;
                        }
	                //echo "<br />Query result".$results;
	    }
		$row++;
	}
	fclose($file_handle);
	
	return $errorMsg;
}

function csv_to_db_get_abs_path_from_src_file($src_file)
{
	if(preg_match("/http/",$src_file))
	{
		$path = parse_url($src_file, PHP_URL_PATH);
		$abs_path = $_SERVER['DOCUMENT_ROOT'].$path;
		$abs_path = realpath($abs_path);
		if(empty($abs_path)){
			$wpurl = get_bloginfo('wpurl');
			$abs_path = str_replace($wpurl,ABSPATH,$src_file);
			$abs_path = realpath($abs_path);			
		}
	}
	else
	{
		$relative_path = $src_file;
		$abs_path = realpath($relative_path);
	}
	return $abs_path;
}

function wpCsvToDBSettingsMenu()
{
	echo '<div class="wrap">';
	echo '<div id="poststuff"><div id="post-body">'; 	 	   	
	
    if (isset($_POST['info_update']))
    {
        update_option('wp_csvtodb_starting_row', stripslashes((string)$_POST["wp_csvtodb_starting_row"]));
        update_option('wp_csvtodb_db_table_name', stripslashes((string)$_POST["wp_csvtodb_db_table_name"]));
        update_option('wp_csvtodb_db_column_names', stripslashes((string)$_POST["wp_csvtodb_db_column_names"]));
        
        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Options Updated!';
        echo '</strong></p></div>';
    }
    if (isset($_POST['save_file_location']))
    {
        update_option('wp_csvtodb_input_file_url', stripslashes((string)$_POST["wp_csvtodb_input_file_url"]));

        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'File Location Saved!';
        echo '</strong></p></div>';
    }
    if (isset($_POST['import_to_db']))
    {
        $file_name = get_option('wp_csvtodb_input_file_url');
        $table_name = get_option('wp_csvtodb_db_table_name');
        $column_string = get_option('wp_csvtodb_db_column_names');
        $start_row = get_option('wp_csvtodb_starting_row');

        $errorMsg = readAndDump($file_name,$table_name,$column_string,$start_row);
        
        echo '<div id="message" class="updated fade"><p><strong>';
        if(empty($errorMsg))
        {
            echo 'File content has been successfully imported into the database!';
        }
        else
        {
            echo "Error occured while trying to import!<br />";
            echo $errorMsg;
        }
        echo '</strong></p></div>';

    }

    if(isset($_POST['file_upload']))
    {
                $target_path = WP_CONTENT_DIR.'/plugins/'.WP_CSV_TO_DB_FOLDER."/uploads/";
		$target_path = $target_path . basename( $_FILES['uploadedfile']['name']);

		//echo "<br />Target Path: ".$target_path;
		echo '<div id="message" class="updated fade"><p><strong>';
		if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path))
		{
		    echo "The file ".  basename( $_FILES['uploadedfile']['name'])." has been uploaded";
		    
		    $file_name = WP_CSV_TO_DB_URL.'/uploads/'.basename( $_FILES['uploadedfile']['name']);
		    update_option('wp_csvtodb_input_file_url', $file_name);
		} 
		else
		{
		    echo "There was an error uploading the file, please try again!";
		}
                echo '</strong></p></div>';
    }


    ?>
    <h2>WP CSV to DB Settings v <?php echo WP_CSV_TO_DB_VERSION; ?></h2>

 	<p>For information, updates and detailed documentation, please visit the The <a href="http://www.tipsandtricks-hq.com/?p=2116" target="_blank">WP CSV to DB</a> plugin page</p>

	<div class="postbox">
	<h3><label for="title">Quick Usage Guide</label></h3>
	<div class="inside">

	<p>1. Specify the general options.</p>
    <p>2. Specify the input file (Upload the CSV file or specify the location of a pre-uploaded CSV file)</p>
	<p>3. Hit the "Import to DB" button to import the CSV file content into the database table.</p>
    </div></div>
        
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    <input type="hidden" name="info_update" id="info_update" value="true" />

	<div class="postbox">
	<h3><label for="title">1. Specify General Settings</label></h3>
	<div class="inside">

    <table width="100%" border="0" cellspacing="0" cellpadding="6">

    <tr valign="top"><td width="25%" align="left">
    Starting Row
    </td><td align="left">
    <input name="wp_csvtodb_starting_row" type="text" size="5" value="<?php echo get_option('wp_csvtodb_starting_row'); ?>"/>
    <br /><i>Row number in the CSV file where you want the plugin to start reading from (e.g. 2).</i><br /><br />
    </td></tr>

    <tr valign="top"><td width="25%" align="left">
    Database Table Name
    </td><td align="left">
    <input name="wp_csvtodb_db_table_name" type="text" size="60" value="<?php echo get_option('wp_csvtodb_db_table_name'); ?>"/>
    <br /><i>The name of the database table where the values will be inserted to (e.g. wp_products_table).</i><br /><br />
    </td></tr>

    <tr valign="top"><td width="25%" align="left">
    Database Column Names
    </td><td align="left">
    <textarea name="wp_csvtodb_db_column_names" cols="70" rows="6"><?php echo get_option('wp_csvtodb_db_column_names'); ?></textarea>
    <br /><i>Column names seperated by comma (,). Leave empty if the values specified in the CSV file matches with the number of columns in the table.</i>
    </td></tr>
    
    </table>
    
    <div class="submit">
        <input type="submit" name="info_update" value="<?php _e('Save'); ?> &raquo;" />
    </div>

    </div></div>

    </form>
       

	<div class="postbox">
	<h3><label for="title">2. Specify The Input CSV File</label></h3>
	<div class="inside">

        <strong>Upload a File</strong>
        <br />

	<form enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
	<input type="hidden" name="file_upload" id="file_upload" value="true" />

	<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
	Choose a CSV file to upload: <input name="uploadedfile" type="file" /><br />
	<input type="submit" value="Upload File" />

	</form>
	
	<br /><strong>OR</strong><br /><br />
	
	<strong>Specify the file URL </strong>
	<br /><br />

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <input name="wp_csvtodb_input_file_url" type="text" size="100" value="<?php echo get_option('wp_csvtodb_input_file_url'); ?>"/>
        <br />

        <div class="submit">
            <input type="submit" name="save_file_location" value="<?php _e('Save File Location'); ?> &raquo;" />
        </div>
        </form>

        </div></div>
        
	<div class="postbox">
	<h3><label for="title">3. Import Into The Database</label></h3>
	<div class="inside">

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">

        <div class="submit">
            <input type="submit" name="import_to_db" value="<?php _e('Import to DB'); ?> &raquo;" />
        </div>
        </form>
        </div></div>
       
    <?php  
    echo '</div></div>';
    echo '</div>';
}

// Display The Options Page
function wpCsvToDbOptionsPage () 
{
     add_options_page('WP CSV To DB', 'CSV To DB', 'manage_options', __FILE__, 'wpCsvToDBSettingsMenu');  
}

add_action('admin_menu','wpCsvToDbOptionsPage');

?>