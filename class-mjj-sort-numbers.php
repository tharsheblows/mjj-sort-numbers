<?php

class MJJ_Sort_Numbers {

	protected static $instance = null;

	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance

	private function __construct(){

		// use this to get the values to the sort table:
		// do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );
		// 
		// and then when the values are deleted (or post statuses are changed from publish or comments are unapproved or deleted ) use this:
		// do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );
		// 
		// these are around here : /Users/jjjay/vagrant-local/www/bloodsugarfix/htdocs/wp-includes/meta-functions.php L 428
		
		add_action('init', array( 'MJJ_Sort_Numbers', 'sorting_rewrite_tag' ), 10, 0);

		add_action( 'pre_get_posts', array( 'MJJ_Sort_Numbers', 'build_post_query'), 10, 1);

		// update sort table when meta updated
		add_action( 'updated_post_meta', array( 'MJJ_Sort_Numbers', 'add_to_sort_table' ), 10, 4 );
		// add in actions to:
		// 	DONE - update sort table on update meta -- use this: do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value ); -- that runs after the update
		// 	
		// 	- update sort table on making a recipe published or closed from one of the other statuses hmmm https://codex.wordpress.org/Post_Status_Transitions looks tedious oh well
		add_action(  'pending_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );
		add_action(  'draft_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );
		add_action(  'auto-draft_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );
		add_action(  'future_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );
		add_action(  'private_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );
		add_action(  'trash_to_publish',  array( 'MJJ_Sort_Numbers', 'change_status_to_publish' ), 10, 1 );

		// 	- delete from sort table when post deleted -- https://codex.wordpress.org/Plugin_API/Action_Reference/delete_post
		// 	- delete from sort table when recipe is un published -- https://codex.wordpress.org/Post_Status_Transitions
	
	}

	public function add_styles() {

	}


	public function add_scripts() {

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : ''; //.min

	} // end add_scripts

	public static function sorting_rewrite_tag(){
		// this adds the query var you chose in the admin to the rewrite array and allows you to use it like $wp_query->query_vars['film_title']
		// 
		// no no it needs to end up /?rating=asc FOR EACH METAKEY ie do one query var for each metakey instead of one overall
		
		$post_types = get_post_types( array( 'public' => true ) );
		$sort_options = array();

		foreach( $post_types as $post_type ){
			
			$post_sort_options = get_option( 'mjj_sort_' . $post_type . '_metakeys', false );
			
			if( !empty( $post_sort_options ) ){
				$sort_options = array_merge( (array)$sort_options, (array)$post_sort_options );
			}
		}

		$comment_sort_options = (array)get_option( 'mjj_sort_comment_metakeys', false );

		if( !empty( $comment_sort_options ) ){
			$sort_options = array_merge( $sort_options, $comment_sort_options );
		}
		
		if( !empty( $sort_options ) && is_array( $sort_options ) ){
			foreach( $sort_options as $query_var => $metakey ){
				add_rewrite_tag('%' . $query_var . '%', '([^&]+)');
			}
		}
	}

	public static function build_post_query( $query ){

		global $wp_query;

		$can_sort = ( is_archive() || is_post_type_archive() ) ? true : false;

		if( ! apply_filters( 'mjj_can_sort', $can_sort, $query ) ){
			return $query;
		}

		$post_type = ( isset( $query->query['post_type' ] ) ) ? esc_attr( $query->query['post_type' ] ) : 'post';

		$sort_var = get_option( 'mjj_sort_' . $post_type . '_metakeys', false );
		$sort_order = '';

		if( !empty( $sort_var ) ){
			foreach( $sort_var as $var => $key ){
				// you can only use asc or desc 
				// this will return the last key / value pair which has a query variable in the options array
				if( isset( $wp_query->query_vars[ $var ] ) ){   
					$sort_order = $wp_query->query_vars[ $var ];
				}
			}
			
			if( $sort_order === 'asc' ){
				add_filter('split_the_query', function( $return ){ return true; }, 10, 1 );
				add_filter('posts_request_ids', array( 'MJJ_Sort_Numbers', 'post_sort_asc' ), 10, 2 );
	
			}
			
			elseif( $sort_order == 'desc' ){
				add_filter('split_the_query', function( $return ){ return true; }, 10, 1 );
				add_filter('posts_request_ids', array( 'MJJ_Sort_Numbers', 'post_sort_desc' ), 10, 2 );
			}
		}

		return $query;
	}

	
	public static function post_sort_desc( $request, $query ){

		global $wpdb;

		$posts_per_page = ( isset( $query->query_vars[ 'posts_per_page' ] ) ) ? (int)$query->query_vars[ 'posts_per_page' ] : 25 ;

		$offset =  ( isset( $query->query[ 'paged' ] ) ) ? (int)$query->query[ 'paged' ] : 1;

		$limit =  ( $offset - 1 ) * $posts_per_page . ',' . $posts_per_page;

		$sort_request_ids = $wpdb->prepare(
			"
        		SELECT SQL_CALC_FOUND_ROWS object_id
       			FROM    wp_mjj_sort_posts sort
    			WHERE meta_key = '%s'
        		ORDER BY sort_value DESC, post_date DESC
        		LIMIT {$limit}
			",
			'_mjj_recipe_rating'
			);

		return $sort_request_ids;

	}

	public static function post_sort_asc( $request, $query ){

		global $wpdb;

		$posts_per_page = ( isset( $query->query_vars[ 'posts_per_page' ] ) ) ? (int)$query->query_vars[ 'posts_per_page' ] : 25 ;

		$offset =  ( isset( $query->query[ 'paged' ] ) ) ? (int)$query->query[ 'paged' ] : 1;

		$limit =  ( $offset - 1 ) * $posts_per_page . ',' . $posts_per_page;

		$sort_request_ids = $wpdb->prepare(
			"
        		SELECT SQL_CALC_FOUND_ROWS object_id
       			FROM    wp_mjj_sort_posts sort
    			WHERE meta_key = '%s'
        		ORDER BY sort_value ASC, post_date ASC
        		LIMIT {$limit}
			",
			'_mjj_recipe_rating'
			);

		return $sort_request_ids;

	}

	// ok what functions do I need?
	// I ONLY need this to list the posts in order, ASC or DESC. I suppose I could do each star separately as well with this.
	// The metavalue will be redundant a given posts's metakey's metavalue in the postmeta table.
	// The definitive value will be in postmeta. If it gets messed up, look there.
	//
	// These at least:
	//  - register metakeys to use : these must be <45 characters and will be used in rewrite rules so name them sanely
	//  - create entry then put into cache
	//  	- check when metas are inserted and if there's a metakey we want in the array, insert it into the table
	//   	- metavalue has to be a number (double) or have an error
	//  - read entry
	//  	- from cache if it's there
	//  	- read and then place into cache
	//  - update entry then replaced cached value if it's there
	//  	- check when metas are inserted and if there's a metakey we want in the array, insert it into the table
	//   	- metavalue has to be a number (double) or have an error
	//  - delete entry and delete cache
	//  	- do when metakey is deleted
	//  - make a query so that I get an array of post objects to use
	//  - add in a query variable for rewrites to use the query and for pagination, use metakey
	//  - can I include this in the meta grab? Hmmm.
	//  - update_sort_numbers_cache function for the cache updates
	//
	//  - taxonomy sort? Ack. No this is fine, get the post ids first from taxonomy_term_relationships, then go to mjj_sort t
	//  	then get the posts. It'll be ok, really.
	//
	//  What are defaults?
	//   - wp_posts table
	//   - post post_type
	//   - metakey must be defined or error



	public static function update_sort_numbers( $post_id, $post_date, $key, $value ){

		global $wpdb;

		
			error_log( $post_id, 0 );
			error_log( $key, 0 );
			error_log( $value, 0 );

		if( !empty( $post_id ) && !empty( $key ) && !empty( $value ) ){

			
			$current_sort_value = MJJ_Sort_Numbers::get_sort_value( $post_id, $key );

			error_log( $current_sort_value, 0 );

			if( $current_sort_value !== null ){
				$sort_id = $current_sort_value->ID;
				$post_date = $current_sort_value->post_date;
				$update = $wpdb->update( 'wp_mjj_sort_posts', array( 'sort_value' => $value ), array( 'ID' => $sort_id ) );
				return $update; // returns false for an error and if it matches, it returns 0 so use false === $update to test that it's worked
			}
			else{
				$post_date = ( !empty( $post_date ) ) ? $post_date : get_post( $post_id )->post_date;
				$insert = $wpdb->insert( 'wp_mjj_sort_posts', array( 'object_id' => (int)$post_id, 'post_date' => $post_date, 'meta_key' => $key, 'sort_value' => $value  ), array( '%s', '%s', '%s', '%f' ) );
				return $insert;
			}
			
		}

		return false;
	}

	public static function get_sort_value( $post_id, $key ){

		global $wpdb; 

		$current_sort_value = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT * 
				FROM wp_mjj_sort_posts 
				WHERE object_id=%d AND meta_key=%s
				",
				$post_id,
				$key
				)
			);

		return $current_sort_value; // returns null if no row in table
	}


	public static function add_to_sort_table( $meta_id = 0, $object_id, $meta_key, $_meta_value ){
		
		$post = get_post( $object_id );
		$post_type = $post->post_type;
		$post_date = $post->post_date;

		$post_sort_options = get_option( 'mjj_sort_' . $post_type . '_metakeys', false );

		if( empty( $post_sort_options ) ){
			return;
		}

		foreach( $post_sort_options as $query_var => $registered_metakey ){
			if( $registered_metakey === $meta_key ){
				MJJ_Sort_Numbers::update_sort_numbers( $object_id, $post_date, $registered_metakey, $_meta_value );
			}
		}

	}

	public static function change_status_to_publish( $old_status, $new_status, $post ){
		
		$post_sort_options = get_option( 'mjj_sort_' . $post->post_type . '_metakeys', false );
		
		foreach( $post_sort_options as $query_var => $registered_metakey ){
			
			$post_sort_value = get_post_meta( $post->ID, $registered_metakey, true );
			
			if( !empty( $post_sort_meta ) ){
				MJJ_Sort_Numbers::update_sort_numbers( $post->ID, $post->post_date, $registered_metakey, $post_sort_meta );
			}
		}
	}


}
