<?php
/**
 * Experiments Reports Table Class
 *
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class burst_experiment_Table extends WP_List_Table {

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @since 1.5
	 */
	public $per_page = 50;

	/**
	 * Number of customers found
	 *
	 * @var int
	 * @since 1.7
	 */
	public $count = 0;

	/**
	 * Total customers
	 *
	 * @var int
	 * @since 1.95
	 */
	public $total = 0;

	/**
	 * The arguments for the data set
	 *
	 * @var array
	 * @since  2.6
	 */
	public $args = array();

	/**
	 * Get things started
	 *
	 * @since 1.5
	 * @see   WP_List_Table::__construct()
	 */


	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular' => __( 'experiment', 'burst' ),
			'plural'   => __( 'experiments', 'burst' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Show the search field
	 *
	 * @param string $text     Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @return void
	 * @since 1.7
	 *
	 */

	
	public function search_box( $text, $input_id ) {
		/**
		* @todo Add filters
		$input_id = $input_id . '-search-input';
		$status   = $this->get_status();
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="'
			     . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="'
			     . esc_attr( $_REQUEST['order'] ) . '" />';
		}



		?>
		<p class="search-box">
			<label class="screen-reader-text"
			       for="<?php echo $input_id ?>"><?php echo $text; ?>
				:</label>
			<select name="status">
				<option value="active" <?php if ( $status === 'active' )
					echo "selected" ?>><?php _e( 'Active Experiments',
						'burst' ) ?></option>
			</select>
			<?php submit_button( $text, 'button', false, false,
				array( 'ID' => 'search-submit' ) ); ?>
		</p>
		<?php
		*/
	}
	

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string Name of the primary column.
	 * @since  2.5
	 * @access protected
	 *
	 */
	protected function get_primary_column_name() {
		return __( 'Name', 'burst' );
	}

	public function column_name( $item ) {
		$name = ! empty( $item['name'] ) ? $item['name']
			: '<em>' . __( 'Unnamed experiment', 'burst' )
			  . '</em>';
		$name = apply_filters( 'burst_experiment_name', $name );

		$actions = array(
			'edit'   => '<a href="'
			            . admin_url( 'admin.php?page=burst-experiments&id='
			                         . $item['ID'] ) . '&action=edit">' . __( 'Edit',
					'burst' ) . '</a>',
			'delete' => '<a class="burst-delete-experiment" data-id="' . $item['ID']
			            . '" href="#">' . __( 'Delete', 'burst' )
			            . '</a>'
		);

		$experiment_count = count( burst_get_experiments() );

		return $name . $this->row_actions( $actions );
	}

	public function column_status( $item ) {
		switch( $item['status'] ) {
			case 'archived':
				$status = __( 'Archived', 'burst' );
				break;
			case 'active':
				$status = __( 'Active', 'burst' );
				break;
			case 'completed':
				$status = __( 'Completed', 'burst' );
				break;
			case 'draft':
			default:
				$status = __( 'Draft', 'burst' );
				break;
		}
		$status =  '<span class="burst-bullet burst-green"></span> <b>' . $status . '</b>';
		return apply_filters( 'burst_experiment_status', $status );
	}

	public function column_kpi( $item ) {
		$kpi = ! empty( $item['kpi'] ) ? $item['kpi']
			: '<em>' . __( 'No KPI selected', 'burst' )
			  . '</em>';
		$kpi = apply_filters( 'burst_experiment_kpi', $kpi );

		return $kpi;
	}

	public function column_control_id( $item ) {
		$post = get_post($item['control_id']);
		$control_id = $item['control_id'] ? $post->post_title : __( 'No control ID', 'burst' );
		$control_id .= '</br><span style="color: grey; ">/'.$post->post_name.'</span>';
		$control_id = apply_filters( 'burst_experiment_control_id', $control_id );


		$actions = array(
			'edit'   => '<a href="'
			            . admin_url( 'post.php?post='
			                         . $item['control_id'] ) . '&action=edit">' . __( 'Edit control post',
					'burst' ) . '</a>',
		);
		return $control_id . $this->row_actions( $actions );
	}

	public function column_variant_id( $item ) {
		$post = get_post($item['variant_id']);
		$variant_id = $item['variant_id'] ? $post->post_title : 'No variant ID';
		$variant_id .= '</br><span style="color: grey; ">/'.$post->post_name.'</span>';
		$variant_id = apply_filters( 'burst_experiment_variant_id', $variant_id );

		$actions = array(
			'edit'   => '<a href="'
			            . admin_url( 'post.php?post='
			                         . $item['variant_id'] ) . '&action=edit">' . __( 'Edit variant post',
					'burst' ) . '</a>',
		);
		return $variant_id . $this->row_actions( $actions );
	}


	/**
	 * Retrieve the table columns
	 *
	 * @return array $columns Array of all the list table columns
	 * @since 1.5
	 */
	public function get_columns() {
		$columns = array(
			'name' => __( 'Name', 'burst' ),
			'control_id' => __( 'Control', 'burst' ),
			'variant_id' => __( 'Variant', 'burst' ),
			'kpi' => __( 'Key performance indicator', 'burst' ),
			'status' => __( 'Active', 'burst' ),
		);

//not sure what this should do @hessel
//		if ( ! $this->show_default_only ) {
//			$columns['control_id'] = __( 'Control', 'burst' );
//			$columns['variant_id'] = __( 'Variant', 'burst' );
//			$columns['kpi'] = __( 'Goal', 'burst' );
//			$columns['status'] = __( 'Active', 'burst' );
//		}

		return apply_filters( 'burst_experiment_columns', $columns );

	}

	/**
	 * Get the sortable columns
	 *
	 * @return array Array of all the sortable columns
	 * @since 2.1
	 */
	public function get_sortable_columns() {
		$columns = array(
			'name' => array( 'name', true ),
			'kpi' => array( 'kpi', true),
			'active' => array( 'active', true),
		);

		return $columns;
	}

	/**
	 * Outputs the reporting views
	 *
	 * @return void
	 * @since 1.5
	 */
	public function bulk_actions( $which = '' ) {
		// These aren't really bulk actions but this outputs the markup in the right place
	}

	/**
	 * Retrieve the current page number
	 *
	 * @return int Current page number
	 * @since 1.5
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}


	/**
	 * Retrieve the current status
	 *
	 * @return int Current status
	 * @since 2.1.7
	 */
	public function get_status() {
		return isset( $_GET['status'] ) ? sanitize_title( $_GET['status'] )
			: 'active';
	}

	/**
	 * Retrieves the search query string
	 *
	 * @return mixed string If search is present, false otherwise
	 * @since 1.7
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the reports data
	 *
	 * @return array $reports_data All the data for customer reports
	 * @global object $wpdb Used to query the database using the WordPress
	 *                      Database API
	 * @since 1.5
	 */
	public function reports_data() {

		if ( ! burst_user_can_manage() ) {
			return array();
		}

		$data    = array();
		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$search  = $this->get_search();
		$status  = $this->get_status();
		$order   = isset( $_GET['order'] )
			? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] )
			? sanitize_text_field( $_GET['orderby'] ) : 'id';

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
			'status'  => $status,
		);

		$args['name'] = $search;

		$this->args = $args;
		$experiments    = burst_get_experiments( $args );
		if ( $experiments ) {

			foreach ( $experiments as $experiment ) {
				$data[] = array(
					'ID'   => $experiment->ID,
					'name' => $experiment->title,
					'control_id' => $experiment->control_id,
					'variant_id' => $experiment->variant_id,
					'kpi' => $experiment->kpi,
					'test_running' => $experiment->test_running,
				);
			}
		}

		return $data;
	}


	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->reports_data();

		$this->total = count( burst_get_experiments() );

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $this->per_page ? ceil( (int) $this->total
		                                       / (int) $this->per_page ) : 1;

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
		) );
	}
}
