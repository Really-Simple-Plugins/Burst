<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! function_exists( 'burst_user_can_manage' ) ) {
	/**
	 * Check if user has Burst permissions 
	 * @return boolean true or false
	 */
	function burst_user_can_manage() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'burst_get_experiments' ) ) {

	/**
	 * Get array of experiment objects
	 *
	 * @param array $args
	 *
	 * @return array
	 */

	function burst_get_experiments( $args = array() ) {
		$defaults = array(
			'order'  => 'DESC',
			'orderby' => 'date_modified',
		);
		$args = wp_parse_args( $args, $defaults );
		$sql  = '';

		$orderby = sanitize_title($args['orderby']);

		$order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
		global $wpdb;

		// array with multiple post statuses
		if ( isset( $args['status'] ) && is_array($args['status']) ) {
			foreach ($args['status'] as $status) {
				$status = burst_sanitize_experiment_status($status);
				$statuses[] = "'".$status."'";
			}
			$statuses = implode (", ", $statuses);
			$sql .= "AND status IN ($statuses)";

		// one post staus as a string		
		} else if ( isset( $args['status'] ) ) {
			$status = burst_sanitize_experiment_status($args['status']);
			$sql .= " AND status = '$status'";
		} 

		$sql .= " ORDER BY $orderby $order";

		return  $wpdb->get_results( "select * from {$wpdb->prefix}burst_experiments where 1=1 $sql" );
	}
}

if ( !function_exists('burst_get_default_experiment_id')){
	/**
	 * Get the default experiment id
	 * @return bool|int
	 */
	function burst_get_default_experiment_id(){
		$experiments = burst_get_experiments();
		if ( $experiments && is_array($experiments) ) {
			$experiments = reset($experiments);
			return $experiments->ID;
		} else {
			return false;
		}
	}
}

if ( !function_exists( 'burst_setcookie') ) {
	function burst_setcookie( $key, $value, $expiration_days ){
		$options = array (
			'expires' => time() + (DAY_IN_SECONDS * apply_filters('burst_cookie_retention', $expiration_days) ),
			'path' => '/',
			'secure' => is_ssl(),
			'samesite' => 'Lax' // None || Lax  || Strict
		);

		setcookie($key, $value, $options );
	}
}

if ( !function_exists( 'burst_sanitize_experiment_status' )) {
	/**
	 * Sanitize the status
	 * @param string $status
	 *
	 * @return string
	 */
	function burst_sanitize_experiment_status($status) {
		$statuses = array(
			'draft',
			'active',
			'completed',
			'archived',
		);
		if ( in_array( $status, $statuses )) {
			return $status;
		} else {
			return 'draft';
		}
	}
}

if ( !function_exists('burst_experiment_not_reached_sample_size')) {
	/**
	 * Check if any of the active expirements is running 30 days, and has not reached the sample size yet.
	 *
	 * @return bool
	 */
	function burst_experiment_not_reached_sample_size(){
		$experiments = burst_get_experiments( array(
			'status' => 'active',
		));
		foreach ( $experiments as $experiment ) {
			$experiment = new BURST_EXPERIMENT($experiment->ID);
			$one_month_ago = strtotime('-30 days');
			if ($one_month_ago > $experiment->date_started && !$experiment->has_reached_minimum_sample_size() ) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('burst_read_more')) {
	/**
	 * Create a generic read more text with link for help texts.
	 *
	 * @param string $url
	 * @param bool   $add_space
	 *
	 * @return string
	 */
	function burst_read_more( $url, $add_space = true ) {
		$html
			= sprintf( __( "For more information on this subject, please read this %sarticle%s",
			'burst' ), '<a target="_blank" href="' . $url . '">',
			'</a>' );
		if ( $add_space ) {
			$html = '&nbsp;' . $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'burst_get_template' ) ) {
	/**
	 * Get a template based on filename, overridable in theme dir
	 * @param $filename
	 *
	 * @return string
	 */

	function burst_get_template( $filename , $args = array() ) {

		$file       = trailingslashit( burst_path ) . 'templates/' . $filename;
		$theme_file = trailingslashit( get_stylesheet_directory() )
		              . trailingslashit( basename( burst_path ) )
		              . 'templates/' . $filename;

		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( !file_exists($file) ) {
			return false;
		}

		if ( strpos( $file, '.php' ) !== false ) {
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			$contents = file_get_contents( $file );
		}

		if ( !empty($args) && is_array($args) ) {
			foreach($args as $fieldname => $value ) {
				$contents = str_replace( '{'.$fieldname.'}', $value, $contents );
			}
		}

		return $contents;
	}
}

if ( ! function_exists( 'burst_array_filter_multidimensional' ) ) {
	function burst_array_filter_multidimensional(
		$array, $filter_key, $filter_value
	) {
		$new = array_filter( $array,
			function ( $var ) use ( $filter_value, $filter_key ) {
				return isset( $var[ $filter_key ] ) ? ( $var[ $filter_key ]
				                                        == $filter_value )
					: false;
			} );

		return $new;
	}
}

if ( !function_exists('burst_generate_test_data')){
	/**
	 * Function to be used only for testing purposes.
	 * It will fill the database with random data.
	 * - create an experiment.
	 * - Get that experiment's id
	 * - prefill the experiment id below.
	 */
	function burst_generate_test_data(){
		global $wpdb;


		$experiment_id = 10;

		for ( $i=30;$i>=0;$i--){
			$daytime = strtotime("-$i days");
			//generate random nr of hits between 4 and 100
			$hitcount = rand(0,100);
			for ( $hits=0;$hits<$hitcount;$hits++){
				//divide day in seconds, equally divided by hits numer
				$between_hits = round(DAY_IN_SECONDS / ($hitcount+1), 0);
				$time = $daytime + ($hits * $between_hits);

				$test_versions = array(
					'control',
					'variant',
				);
				$test_version_id = rand(0,1);
				$conversion = rand(0,6);
				$conversion = ($conversion==4);
				$burst_uid = 'test_uid_'.time();
				$test_version = $test_versions[$test_version_id];
				$url = site_url();

				$update_array = array(
					'page_url'            		=> $url,
					'time'               		=> $time,
					'uid'               		=> $burst_uid,
					'test_version'				=> $test_version,
					'experiment_id'				=> $experiment_id,
					'conversion'				=> $conversion,
				);
				$wpdb->insert(
					$wpdb->prefix . 'burst_statistics',
					$update_array
				);
			}
		}


	}
}
//burst_generate_test_data();

/**
 * Callback to ajax load the posts dropdown in the metabox
 *
 */
function burst_get_posts_ajax_callback(){
 	if (!burst_user_can_manage()) return;

	$return = array();
 	$query_settings = array();
 	foreach ( $_GET['query_settings'] as $key => $value ) {
	    $key = sanitize_text_field($key);
	    $value = sanitize_text_field($value);
	    $query_settings[$key] = $value;
    }

 	$default_args = array(
		's'=> sanitize_text_field( $_GET['q'] ),
		'post_type'=> 'any',
		'posts_per_page' => 25
	);

	$args = array_merge($default_args, $query_settings);

	$search_results = new WP_Query( $args );
	if( $search_results->have_posts() ) :
		while( $search_results->have_posts() ) : $search_results->the_post();	
			// shorten the title a little
			$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
			$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
		endwhile;
	endif;
	echo json_encode( $return );
	die;
}
add_action( 'wp_ajax_burst_get_posts', 'burst_get_posts_ajax_callback' ); // wp_ajax_{action}

if ( ! function_exists( 'burst_localize_date' ) ) {

	function burst_localize_date( $date ) {
		$month             = date( 'F', strtotime( $date ) ); //june
		$month_localized   = __( $month ); //juni
		$date              = str_replace( $month, $month_localized, $date );
		$weekday           = date( 'l', strtotime( $date ) ); //wednesday
		$weekday_localized = __( $weekday ); //woensdag
		$date              = str_replace( $weekday, $weekday_localized, $date );

		return $date;
	}
}

if ( ! function_exists( 'burst_display_date' ) ) {

	function burst_display_date( $date ) {
		$display_date = date_i18n(get_option( 'date_format' ), $date);
		return $display_date;
	}
}

/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 *
 * This function uses type hints now (PHP 7+ only), but it was originally
 * written for PHP 5 as well.
 * 
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */

if ( ! function_exists( 'burst_random_str' ) ) {
	function burst_random_str(
	    int $length = 64,
	    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
	    if ($length < 1) {
	        throw new \RangeException("Length must be a positive integer");
	    }
	    $pieces = [];
	    $max = mb_strlen($keyspace, '8bit') - 1;
	    for ($i = 0; $i < $length; ++$i) {
	        $pieces []= $keyspace[random_int(0, $max)];
	    }
	    return implode('', $pieces);
	}
}

if ( ! function_exists( 'burst_post_has_experiment' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param int|bool $post_id
	 *
	 * @return bool
	 */
	
	function burst_post_has_experiment($post_id = false){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}
		if (!$post_id) return false;

		$experiment_id = get_post_meta($post_id, 'burst_experiment_id');
		return intval($experiment_id) ? true : false;
	}

}

if ( ! function_exists( 'burst_get_experiment_id_for_post' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param int|bool $post_id
	 *
	 * @return bool
	 */
	

	function burst_get_experiment_id_for_post( $post_id = false ){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}

		if (!$post_id) return false;

		return get_post_meta($post_id, 'burst_experiment_id', true);
	}

}

if ( !function_exists( 'burst_sanitize_test_version' )) {
	/**
	 * Sanitize the test version
	 *
	 * @param string $str
	 *
	 * @return string
	 */

	function burst_sanitize_test_version( $str ) {
		$test_versions = array(
			'variant',
			'control'
		);

		if ( in_array( $str, $test_versions ) ) {
			return $str;
		} else {
			return 'control';
		}
	}
}

if ( ! function_exists( 'burst_get_current_post_type' ) ) {

	/**
	 * Get the current post type
	 * @param int $post_id
	 *
	 * @return string|bool
	 */
	
	function burst_get_current_post_type($post_id = false){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}
		if (!$post_id) return false;

		$post = get_post($post_id);
		if (!$post) return false;

		return $post->post_type;
	}

}

if ( ! function_exists( 'burst_get_current_post_id' ) ) {

	/**
	 * Get the current post type
	 * @param $post_id
	 *
	 * @return string
	 */
	
	function burst_get_current_post_id(){
		$post_id = get_the_ID();
		
		if ( !$post_id ){
			$post_id = isset($_GET['post']) && is_numeric($_GET['post']) ? intval($_GET['post']) : false;
		}

		return $post_id;
	}
}

if ( !function_exists( 'burst_get_current_url') ) {
	/**
	 * Function to get the current URL used in the load_experiment_content function
	 * @return string The current URL
	 */
	function burst_get_current_url() {
		return parse_url( get_permalink(), PHP_URL_PATH );
	}

}

if ( ! function_exists( 'burst_get_all_post_statuses' ) ) {

	/**
	 * Get the current post type
	 * @param $post_id
	 *
	 * @return array
	 */
	
	function burst_get_all_post_statuses($exceptions = array()){
		$post_statuses = get_post_stati();
		
		$filtered_post_statuses = array();
		foreach ($post_statuses as $post_status => $value) {
			if (!in_array($post_status, $exceptions)) {
				$filtered_post_statuses[] = $post_status;
			}
		}
		$filtered_post_statuses[] = 'experiment';
		
		return $filtered_post_statuses;
	}

}
if ( ! function_exists( 'burst_display_experiment_status' ) ) {

	function burst_display_experiment_status($experiment_status, $get_array = false) {
		switch( $experiment_status ) {
				case 'archived':
					$status_text = __( 'Archived', 'burst' );
					$class = 'grey';
					break;
				case 'active':
					$class = 'rsp-blue-yellow';
					$status_text = __( 'Active', 'burst' );
					break;
				case 'completed':
					$status_text = __( 'Completed', 'burst' );
					$class = 'rsp-green';
					break;
				case 'loading':
					$status_text = __( 'Loading...', 'burst' );
					$class = 'grey loading initial-loading';
					break;
				case 'draft':
				default:
					$status_text = __( 'Draft', 'burst' );
					$class = 'grey';
					break;
			}
			$status = false;
			if ($get_array) {
				$status = array(
					'class' => $class,
					'title' => $status_text,
				);
			} else {
				$status =  '<div class="burst-experiment-status"><span class="burst-bullet ' . $class . '"></span><span class="burst-experiment-status__text">' . $status_text . '</span></div>';
			}
			
			return $status;
	}
}

if ( ! function_exists( 'burst_get_report_url' ) ) {
	/**
	 * Get the URL that leads to the dashboard and show data for the experiment ID
	 * @param  int $experiment_id
	 * @return string Url to the dashboard
	 */
	function burst_get_report_url($experiment_id) {
		return admin_url('admin.php?page=burst&experiment_id='. $experiment_id .'');
	}
}
