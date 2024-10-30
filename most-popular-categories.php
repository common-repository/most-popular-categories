<?php
/**
Plugin Name: Most Popular Categories
Version: 1.0
Plugin URI: http://justmyecho.com/2010/11/most-popular-categories-widget-for-wordpress/
Description: Lists most popular categories in a widget
Author: Robin Dalton
Author URI: http://justmyecho.com
**/

function popular_categories_load_widget() {
	register_widget( 'Most_Popular_Categories_Widget' );
}

class Most_Popular_Categories_Widget extends WP_Widget {
			
	/* Set up some default widget settings. */
	var $defaults = array();

	function Most_Popular_Categories_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'popularcategories', 'description' => __('Display popular categories.', 'popularcategories') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => 'popcat-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'popcat-widget', __('Most Popular Categories', 'popularcategories'), $widget_ops, $control_ops );
		
		$this->defaults = array ('catlist_title' => __( '', 'popularcategories' ),
							'catlist_show_count' => __( 1, 'popularcategories' ),
							'catlist_order' => __( 1, 'popularcategories' ),
							'catlist_limit' => __( 10, 'popularcategories' ),
							'catlist_dropdown' => __( 0, 'popularcategories' ),
							'catlist_cat_exc' => __( '', 'popularcategories'),
							'catlist_cache' => __( '', 'popularcategories')
						 );
		
		if(!get_option('popularcategories')) {
			add_option('popularcategories', $this->defaults);
		}
	}
	
	function widget( $args, $instance ) {
		extract( $args );		
		$cache = get_option('popularcategories');
		
		echo $before_widget;
		if($instance['catlist_title'] != '') {
			echo $before_title . $instance['catlist_title'] . $after_title;
		}
		
		echo $cache['catlist_cache'];
		
		echo $after_widget;	
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		foreach($new_instance as $key => $val) {
			$instance[$key] = strip_tags( $new_instance[$key] );
		}
		$instance['catlist_show_count'] = ( $new_instance['catlist_show_count'] == 1 ) ? 1 : 0;
		$instance['catlist_dropdown'] = ( $new_instance['catlist_dropdown'] == 1 ) ? 1 : 0;

		update_option('popularcategories', $instance);
		catlist_generate_cat_cache();

		return $instance;
	}

	function form( $instance ) {
		
		$instance = wp_parse_args( (array) $instance, $this->defaults ); 
		
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'catlist_title' ); ?>"><?php _e('Title:', 'popularcategories'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'catlist_title' ); ?>" name="<?php echo $this->get_field_name( 'catlist_title' ); ?>" value="<?php echo $instance['catlist_title']; ?>" style="width:225px;" />
		</p>

		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'catlist_show_count' ); ?>" name="<?php echo $this->get_field_name( 'catlist_show_count' ); ?>" value="1"<?php echo ($instance['catlist_show_count'] == 1) ? ' checked="checked"' : ''; ?>>
			<label for="<?php echo $this->get_field_id( 'catlist_show_count' ); ?>"><?php _e('Display Count', 'popularcategories'); ?></label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'catlist_order' ); ?>"><?php _e('Category List order:', 'popularcategories'); ?></label>
			<select id="<?php echo $this->get_field_id( 'catlist_order' ); ?>" name="<?php echo $this->get_field_name( 'catlist_order' ); ?>">
				<option value="1"<?php echo ($instance['catlist_order'] == 1) ? ' selected="selected"' : ''; ?>>Most Popular</option>
				<option value="2"<?php echo ($instance['catlist_order'] == 2) ? ' selected="selected"' : ''; ?>>Alphabetical</option>
				</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'catlist_limit' ); ?>"><?php _e('Category Limit:', 'popularcategories'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'catlist_limit' ); ?>" name="<?php echo $this->get_field_name( 'catlist_limit' ); ?>" value="<?php echo $instance['catlist_limit']; ?>" style="width:50px;" /><br />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'catlist_cat_exc' ); ?>"><?php _e('Exclude Category by ID:', 'popularcategories'); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'catlist_cat_exc' ); ?>" name="<?php echo $this->get_field_name( 'catlist_cat_exc' ); ?>" value="<?php echo $instance['catlist_cat_exc']; ?>" style="width:225px;" />
			<span style="font-size:.9em;">Separate ID's by comma.</span>
		</p>

		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'catlist_dropdown' ); ?>" name="<?php echo $this->get_field_name( 'catlist_dropdown' ); ?>" value="1"<?php echo ($instance['catlist_dropdown'] == 1) ? ' checked="checked"' : ''; ?>>
			<label for="<?php echo $this->get_field_id( 'catlist_dropdown' ); ?>"><?php _e('Display as a drop down', 'popularcategories'); ?></label>
		</p>
		
	<?php
	}
}

function catlist_generate_cat_cache() {
	global $wpdb;
	
	$instance = get_option('popularcategories');

    $myurl = get_bloginfo('url');
    $category_base = get_settings('category_base');
    $term_tax_table = $wpdb->prefix . 'term_taxonomy';
    $term_table = $wpdb->prefix . 'terms';
    
    $cat_exc_sql = ($instance['catlist_cat_exc'] != '') ? 'AND b.term_id NOT IN ('.$instance['catlist_cat_exc'].')' : '';
    
    $query = "SELECT a.name, a.slug, b.count FROM $term_tax_table b
    			LEFT JOIN $term_table a
    			ON b.term_id = a.term_id
    			WHERE b.taxonomy = 'category'
    			$cat_exc_sql
    			ORDER BY b.count DESC
    			LIMIT $instance[catlist_limit]";
    
    $get_categories = $wpdb->get_results($query);
    
    if($get_categories) {
    	if($instance['catlist_order'] == 2) {
    		usort($get_categories, "resort_cat_list");
    	}
    	
    	if($instance['catlist_dropdown'] == 1) {
    		
    		$cache = '<select onChange="document.location.href=this.options[this.selectedIndex].value;">';
			$cache .= "<option>Categories</option>\n";
			
			foreach($get_categories as $cat) {
				$cache .= "<option value=\"".$category_base."/".$cat->slug."\">".$cat->name;
					if($instance['catlist_show_count'] == 1) {
						$cache .= " (".$cat->count.")";
					}
				$cache .= "</option>\n";
			}
			$cache .= "</select>\n";
			
		} else {
    
   			$cache = '<ul class="popular-category-list">';
    
			foreach($get_categories as $cat) {
				$cache .= '<li><a href="' . $category_base . '/' . $cat->slug . '">' . $cat->name;
					if($instance['catlist_show_count'] == 1) {
						$cache .= ' (' . $cat->count . ')';
					}
				$cache .= '</a></li>';
			}
	
			$cache .= '</ul>';
		}
	
	$instance['catlist_cache'] = $cache;

	update_option('popularcategories', $instance);
	}	
}
function resort_cat_list($a, $b) {
    return strcmp($a->name, $b->name);
}

add_action('widgets_init', 'popular_categories_load_widget');
add_action('edit_post', 'catlist_generate_cat_cache');
add_action('delete_post', 'catlist_generate_cat_cache');
add_action('publish_post', 'catlist_generate_cat_cache');
?>