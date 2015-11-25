<?php 

class MJJ_Sort_Numbers_Admin {

	protected static $instance = null;

	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance

	private function __construct(){

		add_action( 'admin_menu', array( 'MJJ_Sort_Numbers_Admin', 'mjj_sort_numbers_tools_page' ) );
		
	}

	public function add_admin_styles(){

	}


	public function add_admin_scripts(){

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : ''; //.min


	} // end add_scripts

		//add the admin page to users http://codex.wordpress.org/Creating_Options_Pages
	public static function mjj_sort_numbers_tools_page(){
		// This page will be under "Settings"
		add_management_page(
			'MJJ Sort Numbers Tools',
			'MJJ Sort Numbers Tools',
			'manage_options',
			'mjj-sort-numbers-tools',
			array( 'MJJ_Sort_Numbers_Admin', 'create_admin_page' )
		);
	}

	// make a tool to re-populate the sort table. this should be in the admin class
	
	//creates the view page in admin
	public static function create_admin_page()
		{ ?>
			<div id="mjj-sort-numbers-tools" class="wrap">

				<?php

					//Marlene Dietrich
					screen_icon(); 

					$metatables = array( 'post' ); // the meta tables we can use -- with 4.4 you could add term meta but I'm not using it so it's not here and I'm not using comments because PERMALINKS
					$post_types =  get_post_types( array( 'public' => true ) );
					$query_var = get_option( 'sort_query_var', 'sort' );
					?>

				<h1>Sort numbers tools</h1>
				
				<style>
					table.form-table{
						width: 33%;
						margin-left: 2%;
					}
					.form-table th{
						padding-left: 10px;
						width: 50%;
					}

					.wp-core-ui .button-primary{
						margin-top: 30px;
					}

					h4{
						text-transform: uppercase;
					}
				</style>

				<form method="post">
				 <input type="hidden" name="sorting_form_nonce" value="<?php echo wp_create_nonce( 'mjj-sort-numbers-nonce-' . get_current_user_id() ); ?>" />

				 <h2>Add a metakey to sort</h2>
				 <p>Add in a metakey to be used for sorting. The value of the metakey must be a number. If it isn't, it won't work. This only sorts numbers. 
				 Make sure you use the right post type. This doesn't automatically run the repopulation function, so you will need to do that
				 if you are adding a metakey with existing values. The metakey will be used to sort all entries in the table which have it.</p>
				 <p>Metakeys and query vars should be unique. This is not enforced, so it's up to you to do it.</p>

				 <?php foreach( $post_types as $post_type ) : ?>

				 <h4>to <?php echo $post_type; ?>s</h4>

				 <table class="form-table striped widefat">
				 	
				 		<tr>
							<th> Add <?php echo $post_type; ?> metakey</th>
							<td><input type="text" name="add_<?php echo $post_type; ?>_metakey" value="" /></td>
				 		</tr>
				 		<tr>
				 			<th> Add query var for metakey</th>
							<td><input type="text" name="add_<?php echo $post_type; ?>_query_var" value="" /></td>
						</tr>

				 </table>

				 <?php endforeach; ?>

				 <h2>Delete a metakey from post types</h2>

				 <p>This will also delete all the metakey entries in the sort table but *not* in the meta table.</p>

				 	<?php foreach( $post_types as $post_type ) : ?>

				 	<h4><?php echo strtoupper( $post_type . ' metakeys' ); ?></h4>

				 		<?php $sorting_keys[ $post_type ] = get_option( 'mjj_sort_' . $post_type . '_metakeys' );	
	
						if( ! empty( $sorting_keys[ $post_type ] ) && is_array( $sorting_keys[ $post_type ] ) ) : ?>
							
							<p>To delete a <?php echo $post_type; ?> metakey and stop it being stored and available for sorting, tick the box.</p>
							
							<table class="form-table striped widefat">
							
							<?php
	
								foreach( $sorting_keys[ $post_type ] as $sorting_query_var => $sorting_key ) : ?>
									<tr>
										<th>
											<?php echo esc_attr( $sorting_query_var ); ?>
											<br />(meta key: <?php echo esc_attr( $sorting_key ); ?>)
										</th>
										<td><input type="checkbox" name="delete_<?php echo $post_type; ?>_metakeys[]" value="<?php echo esc_attr( $sorting_query_var ); ?>" /> tick to delete</td>
									</tr>
				 		
							<?php

								endforeach; ?>

							</table>
	
						<?php else : ?>
	
							<p>No <?php echo $post_type; ?> metakeys registered.</p>
	
						<?php endif; ?>

					<?php endforeach; ?>


				 <h2>Repopulate sort table for a given metakey</h2>

				 <p>This will remove all entries for a given metakey and table (posts or comments) in the sort table, then re-populate it from the correct meta table. <br />
				 You really should back up the database first. The metakey will already need to be registered as above to appear.</p>

				 		
				 		<?php 

				 			foreach( $metatables as $metatable ) : ?>

				 			<table class="form-table striped widefat">
				 				<tr>
									<th>Metakey in <?php echo $metatable; ?>meta</th>
									<td>
										<select name="<?php echo $metatable; ?>_metakey_to_fix">
											<option selected>None</option>
				 			
				 			<?php	
				 				foreach( $post_types as $post_type ) : 

				 					if( ! empty( $sorting_keys[ $post_type ] ) && is_array( $sorting_keys[ $post_type ] ) ) : ?>
										<?php if( is_array( $sorting_keys[ $post_type ] ) ) : ?>
										<?php foreach( $sorting_keys[ $post_type ] as $sorting_query_var => $sorting_key ) : ?>
												<option value="<?php echo $post_type; ?>&amp;<?php echo esc_attr( $sorting_key ); ?>"><?php echo esc_attr( $sorting_key ); ?> (query var: <?php echo esc_attr( $sorting_query_var ); ?>)</option>
										<?php endforeach; 
											  endif;
					 				endif; 
					 			
					 			endforeach; ?>
	
					 					</select>
									</td>
								</tr>

						</table>
					
					<?php 
							endforeach;
					?>

					<input type="submit" value="submit" class="button-primary" />
				
				</form>

				<?php

				if( current_user_can( 'manage_options' ) && isset( $_POST['sorting_form_nonce'] ) && wp_verify_nonce(  $_POST['sorting_form_nonce'], 'mjj-sort-numbers-nonce-' . get_current_user_id() ) ) :
	
					foreach( $post_types as $post_type ) :
	
						if( isset( $_POST['add_' . $post_type . '_metakey'] ) && !empty( $_POST['add_' . $post_type . '_metakey'] ) ){
							
							$metakey_to_add = $_POST['add_' . $post_type . '_metakey'];
							$query_var_to_add = $_POST['add_' . $post_type . '_query_var'];
							
							if( ! empty( $sorting_keys[ $post_type ] ) && is_array( $sorting_keys[ $post_type ] ) && isset( $sorting_keys[ $post_type ][ $metakey_to_add ] ) ){
								echo esc_attr( $metakey_to_add ) . ' is already in the sorting keys and has not been added.';
								return;
							}
							elseif( empty( $query_var_to_add ) || empty( $metakey_to_add ) ){
								echo 'You need to put in a query variable AND a metakey. It won&rsquo;t work otherwise. You&rsquo;ve only entered: ' . $metakey_to_add . $query_var_to_add;
								return;
							}
							else{
								$sorting_keys_array = ( empty( $sorting_keys[ $post_type ] ) || !is_array( $sorting_keys[ $post_type ] )  ) ? array() : $sorting_keys[ $post_type ];
								$sorting_keys_array[ esc_attr( $query_var_to_add ) ] = esc_attr( $metakey_to_add );
		
								update_option( 'mjj_sort_' . $post_type . '_metakeys', $sorting_keys_array );
		
								echo 'You have added the ' . esc_attr( $metakey_to_add ) . ' ' . $post_type . ' metakey to the sorting list.';
							}
						}
		
						if( isset( $_POST['delete_' . $post_type . '_metakeys'] ) && count( $_POST['delete_' . $post_type . '_metakeys'] > 0 ) ){
		
							$sorting_keys_to_delete = (array)$_POST['delete_' . $post_type . '_metakeys'];

							foreach( $sorting_keys_to_delete as $sorting_key_to_delete ){
								$sorting_metakeys_deleted[] = $sorting_keys[ $post_type ][ $sorting_key_to_delete ];
								unset( $sorting_keys[ $post_type ][ $sorting_key_to_delete ] );
							}
		
							update_option( 'mjj_sort_' . $post_type . '_metakeys', $sorting_keys[ $post_type ] );
		
							echo '<h2>You have deleted the following metakeys: </h2>';
							
							foreach( $sorting_metakeys_deleted as $sorting_metakey_to_delete ){
								
								$delete_metakeys_in_sort = self::delete_metakeys_in_sort( $sorting_metakey_to_delete );
	
								echo '<br />';
								echo $sorting_metakey_to_delete;
							}
						}

					endforeach;

					foreach( $metatables as $metatable ) :

						if( isset( $_POST[ $metatable . '_metakey_to_fix'] ) && !empty( $_POST[ $metatable . '_metakey_to_fix'] ) &&  $_POST[ $metatable . '_metakey_to_fix'] !== 'None' ){

							$repopulation_query = explode( '&',  $_POST[ $metatable . '_metakey_to_fix'] );
							$pt = $repopulation_query[0];
							$mk = $repopulation_query[1];
							
							$metakey_to_fix = esc_attr( $_POST[ $metatable . '_metakey_to_fix' ] );
		
							echo '<h2>The results for ' . $metatable . ': </h2>';
							
							self::repopulate_sorting_table( $pt, $mk, $metatable );
						}

					endforeach;

				elseif( isset( $_POST ) && ! empty( $_POST ) ) :
					echo '<h2>The security check failed.</h2>';

				endif; ?>


		   </div>

	<?php }

	private static function repopulate_sorting_table( $post_type, $var_key, $metatable = 'post' ){

		if( empty( $var_key ) || $var_key === 'none_to_sort' ){
			return;
		}

		global $wpdb;

		$tablename = 'wp_mjj_sort_' . esc_attr( $metatable ) . 's';

		$delete = self::delete_metakeys_in_sort( $var_key, $metatable );
		
		if( $delete === false ){
			echo 'Repopulating the sorting table didn&rsquo;t work for some reason.';
			return false;
		}

		if( $metatable === 'post' ){

			// we're only going to add the published posts to this table 

			$repopulate_query = $wpdb->prepare(
			"
			INSERT INTO {$tablename}
				( object_id, post_date, meta_key, sort_value )
				SELECT meta.post_id,   posts.post_date, meta.meta_key, CAST( meta.meta_value AS DECIMAL(7,4) )
				FROM {$wpdb->posts} AS posts
				INNER JOIN
					( 	SELECT post_id, meta_key, meta_value
						FROM {$wpdb->postmeta}
						WHERE meta_key = '%s'
					) meta
				ON meta.post_id = posts.ID 
				WHERE posts.post_type = '%s' AND ( posts.post_status='publish' OR posts.post_status='closed' )
			",
			$var_key,
			$post_type
			);
		}
		elseif( $metatable === 'comment' ){

			// we're only going to add the approved comments to this table 

			$repopulate_query = $wpdb->prepare(
			"
			INSERT INTO {$tablename}
				( object_id, post_id, meta_key, sort_value )
				SELECT meta.comment_id, comments.comment_post_ID, meta.meta_key, CAST( meta.meta_value AS DECIMAL(7,4) )
				FROM {$wpdb->comments} AS comments
				INNER JOIN
					( 	SELECT comment_id, meta_key, meta_value
						FROM {$wpdb->commentmeta}
						WHERE meta_key = '%s'
					) meta
				ON meta.comment_id = comments.comment_ID 
				WHERE comments.comment_approved ='1' 
			",
			$var_key
			);
		}

		else{
			echo 'No object table found.';
			return;
		}
		

		$wpdb->query( $repopulate_query );

		echo 'Repopulation done.';
		return;

	}

	private static function delete_metakeys_in_sort( $metakey ){

		global $wpdb;

		$tablename = 'wp_mjj_sort_posts';

		// are there any entries to delete?
		$exists = $wpdb->query(
			$wpdb->prepare(
				"
					SELECT COUNT(*) FROM {$tablename}
					WHERE meta_key = '%s'
				",
				$metakey
			)
		);

		$delete = ( $exists > 0 ) ? $wpdb->delete( $tablename, array( 'meta_key' => esc_attr( $metakey ) ), array( '%s' ) ) : true;

		return $delete;

	}


}