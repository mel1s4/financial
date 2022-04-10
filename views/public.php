<?php
// Write a new permalink entry on code activation
register_activation_hook( __FILE__, 'customop_activation' );
function customop_activation() {
        customop_custom_output();
        flush_rewrite_rules(); // Update the permalink entries in the database, so the permalink structure needn't be redone every page load
}

// If the plugin is deactivated, clean the permalink structure
register_deactivation_hook( __FILE__, 'customop_deactivation' );
function customop_deactivation() {
        flush_rewrite_rules();
}


// And now, the code that do the magic!!!
// This code create a new permalink entry
add_action( 'init', 'customop_custom_output' );
function customop_custom_output() {
        add_rewrite_tag( '%viroz_financial_dashboard%', '([^/]+)' );
        add_permastruct( 'viroz_financial_dashboard', '/%viroz_financial_dashboard%' );
}

// The following controls the output content
add_action( 'template_redirect', 'customop_display' );
function customop_display() {
        if ($query_var = get_query_var('viroz_financial_dashboard')) {
                if(!is_user_logged_in()) {
                        wp_redirect( home_url() );
                        exit;
                }
                header("Content-Type: text/html");

                include('public-dashboard.php');
                exit; // Don't forget the exit. If so, WordPress will continue executing the template rendering and will not fing anything, throwing the 'not found page'
        }
}