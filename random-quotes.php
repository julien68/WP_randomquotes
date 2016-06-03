<?php 
 
/*
Plugin Name: Random fantasy quotes
Description: Adds a "nice" footer to posts with a random fantasy quote
Version: 0.1.3
Author: Julien choiniere
*/

global $fantasy_quote_version;
$fantasy_quote_version = '0.1.3';

/* Creates database table and populates it */
register_activation_hook( __FILE__, 'fantasy_quote_activation' );
function fantasy_quote_activation() {	
	$installed_ver = get_option( "fantasy_quote_version" );
	global $fantasy_quote_version;
	
	if ( $installed_ver != $fantasy_quote_version ){
		// Create DB table here 
		fantasy_quote_create_db();
		// Populate DB table here; Instead of populating on table creation, maybe populate if table is empty on activation?
		fantasy_quote_populate_db();
		
		update_option( "fantasy_quote_version", $fantasy_quote_version );
	}	
}

function fantasy_quote_create_db() {
	global $wpdb;
	
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'fantasy_quotes';

	$wpdb->query("DROP TABLE IF EXISTS $table_name"); //Too extreme? Read about best practice for DB updgrades
    $sql = "CREATE TABLE $table_name (quote varchar(150));";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

//Definitively NOT the way to go, but in this case it's good enough
function fantasy_quote_populate_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fantasy_quotes';
        $fileName = __DIR__ . "/quotes.csv"; //full path to the DB seed

        $handle = fopen($fileName, "r"); //read only
		
        if ($handle) {
			// Break csv file down into individual lines
			while (($data = fgetcsv($handle, 1000, ";", "\"")) !== FALSE) {
				$query = "INSERT INTO $table_name VALUES ('" . $data[0] . "');"; //A tad simple for only one value, otherwise break into chunks
				// Insert row into table
				$wpdb->query($query);
			}
			// Closes CSV file
			fclose($handle);
        }
}

/* Main function, this adds the quote to the post*/
add_filter('the_content', 'random_quote');
function random_quote($content) {	
        global $wpdb;
        $query = "SELECT quote FROM " . $wpdb->prefix . "fantasy_quotes ORDER BY RAND() LIMIT 1";
        $random_row = $wpdb->get_row($query);

        $random_quote_footer = <<<EOT
        <p>"$random_row->quote"</p>
EOT;

        return $content . $random_quote_footer;
}

/* Menu shenanigans */
add_action('admin_menu', 'fantasy_admin_pages');

function fantasy_admin_pages(){
        add_menu_page('Fantasy Quotes','Fantasy Quotes', 'manage_options', 'fantasy-quotes-admin-handle', 'fantasy_quotes_admin_page' );
        add_submenu_page('fantasy-quotes-admin-handle', 'Fantasy Quotes All Quotes','All Quotes', 'manage_options', 'fantasy-quotes-admin-handle', 'fantasy_quotes_admin_page');
        add_submenu_page('fantasy-quotes-admin-handle', 'Fantasy Quotes Add','Add Quote', 'manage_options', 'fantasy-quotes-admin-add-handle', 'fantasy_quotes_admin_add');
        add_submenu_page('fantasy-quotes-admin-handle', 'Fantasy Quotes Export Data','Export Data', 'manage_options', 'fantasy-quotes-admin-export-handle', 'fantasy_quotes_admin_export');
}

function fantasy_quotes_admin_page(){
        echo "<h2>Fantasy quotes</h2>";
        echo "<br />";

        //get all quotes; That's nice and dandy, until you have 5 millions quotes. Break this into pages
		global $wpdb;
		$query = "SELECT quote FROM " . $wpdb->prefix . "fantasy_quotes";
        $rows = $wpdb->get_results($query);
        //start table
		echo "<table>";
        //foreach rows collected
		foreach ($rows as $row) {
			echo "<tr><td>";
			echo $row->quote;
			echo "</tr></td>";
		}
        //close table
		echo "</table>";
}

function fantasy_quotes_admin_add(){
        echo "<h2>Fantasy quotes ADD</h2>";
        echo "<br />";

        //form?
		//should probably update the data.csv as well?
}

function fantasy_quotes_admin_export(){
        echo "<h2>Fantasy quotes export</h2>";
        echo "<br />";

        //Download button? -> function with csv file as output
		//Import option too?
}
