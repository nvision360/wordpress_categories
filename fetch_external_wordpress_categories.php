<?php
/**
Plugin Name: Fetch External Categories
Plugin URI: http://www.nvision360.com
Description: This plugin fetches external categories in a hierarchal order
Version: 1.0.0
Author: Abdul Rehman
Author URI: http://www.nvision360.com 
Author Email: abdul@nvision360.com 
*/
if ( !class_exists('WPEXCATS_Fetch_External_Categories') ) {
    class WPEXCATS_Fetch_External_Categories {
	
		function __construct() {
			add_action('WPEXCATS_Fetch_cats_prune', array(&$this, 'WPEXCATS_Fetch_Ext_cats'));
			add_action('admin_menu', array(&$this, 'WPEXCATS_hook_update_categories_link'));
        	add_filter('cron_schedules', 'WPEXCATS_change_cron_schedule' );
		}
		
		function WPEXCATS_activate_importing(){
			if (!wp_next_scheduled('WPEXCATS_Fetch_cats_prune')){
				wp_schedule_event(time(), 'hourly', 'WPEXCATS_Fetch_cats_prune');
			}
		}
		
		function WPEXCATS_deactivate_importing(){
			wp_clear_scheduled_hook('WPEXCATS_Fetch_cats_prune');
		}
		
		function WPEXCATS_Fetch_Ext_cats(){
			$Error = false;
			//calling API
			$cat_results = $this->WPEXCATS_Fetch_API();
			//checking if an array has been retrieved
			if(!is_array($cat_results) || empty($cat_results)){
				$Error = true;
			}
			if(!$Error){
				//looping through terms
				foreach($cat_results['categories'] as $value){
					$id = $value['id'];
					$name = sanitize_title($value['name']);
					//checking if it's a parent category
					if(empty($value['parent_id']) || $value['parent_id'] == "null"){
						$existing_cat_id = $this->WPEXCATS_get_cat_term_by_field_id($id);
						//utilizing custom meta key to check if term already exists
						if(empty($existing_cat_id)){
							$this->WPEXCATS_insert_cat($name,0,$id);
						}
						else{
							$this->WPEXCATS_update_cat($existing_cat_id->term_id,$name,0,$id);
						}
					}
					else{
						//for child categories
						$parent = $this->WPEXCATS_get_cat_term_by_field_id($value['parent_id']);
						//utilizing custom meta key to check if term already exists
						$existing_cat_id = $this->WPEXCATS_get_cat_term_by_field_id($id);
						if(empty($existing_cat_id)){
							$this->WPEXCATS_insert_cat($name,$parent->term_id,$id);
						}
						else{
							$this->WPEXCATS_update_cat($existing_cat_id->term_id,$name,$parent->term_id,$id);
						}
					}
				}
				echo '<h2>Import/Update All Categories</h2>';
				if(!$Error){
					echo '<p>All categories have been fetched and updated successfully</p>';
				}
				else{
					echo '<p>Unable to retreive information from API. Please update configuration file</p>';
				}
			}
		}
		
		function WPEXCATS_Fetch_API(){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/x-www-form-urlencoded; charset=utf-8");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch, CURLOPT_URL, API_ENDPOINT);
			curl_setopt($ch, CURLOPT_GET, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			$content = curl_exec($ch); 
			$results = json_decode($content,true);
			curl_close($ch);
			return $results;
		}
		
		function WPEXCATS_get_cat_term_by_field_id($field_id){
			$args = array(	'taxonomy' => 'category',
				'meta_key' => 'cat_field_id',
				'meta_value' => $field_id,
				'hide_empty' => false
			);
			$cat_parent_term = get_terms($args); 
			if(!empty($cat_parent_term)){
				return $cat_parent_term[0];
			}
			return false;
		}
		//inserting new terms with custom meta key
		function WPEXCATS_insert_cat($cat_name,$cat_parent,$cat_api_id){
			$cat_values = array(
				'cat_name' => $cat_name,
				'category_nicename' => $cat_name,
				'category_parent' => $cat_parent,
				'taxonomy' => APP_TAXONOMY
			);
			$new_cat_id = wp_insert_category($cat_values);
			/*
			adding a meta key for API ID field
			this will be used to check for existing term and update based on ID
			*/
			add_term_meta($new_cat_id, "cat_field_id", $cat_api_id );
		}
		//updating term name, slug and parent
		function WPEXCATS_update_cat($cat_id,$cat_name,$cat_parent,$cat_api_id){
			$cat_values = array(
				'name' => $cat_name,
				'slug' => $cat_name,
				'parent' => $cat_parent
			);
			wp_update_term($cat_id, APP_TAXONOMY, $cat_values);
		}
		//WP-admin settings page link
		function WPEXCATS_hook_update_categories_link() {
			add_options_page(
				'Update Categories Now', 
				'Update Categories',
				'manage_options',
				'update_external_categories',
				array($this, 'WPEXCATS_Fetch_Ext_cats')
			);
		}              
		//hook for setting schedules in seconds
		function WPEXCATS_change_cron_schedule( $schedules ) {
			$schedules['runs_after_every_thirty_minutes'] = array(
					'interval'  => APP_INTERVAL
			);
			return $schedules;
		}
    }
    $WPEXCATS_Fetch_External_Categories = new WPEXCATS_Fetch_External_Categories();
	//registering hooks for schedules, main function
	register_activation_hook( __FILE__, 'WPEXCATS_Fetch_External_Categories' );
	register_activation_hook( __FILE__, array(&$this, 'WPEXCATS_activate_importing'));
	register_deactivation_hook( __FILE__, array(&$this, 'WPEXCATS_deactivate_importing'));
	require_once('config.php');
}
?>