<?php
/**

* Add custom field to the checkout page

*/

add_action('woocommerce_after_order_notes', 'custom_checkout_field');

function custom_checkout_field($checkout) {

    echo '<div id="skype"><h2>' . __('Enter you skype') . '</h2>';
    woocommerce_form_field('custom_field_name', array(
                                                'type' => 'text',
                                                'class' => array(
                                                    'my-field-class form-row-wide'
                                                ),
                                                'label' => __('Add you skype'),
                                                'placeholder' => __('Please, Enter you skype') ,

    ),
    $checkout->get_value('custom_field_name'));
    echo '</div>';
}


// Creating a Films Custom Post Type
function crunchify_films_custom_post_type() {
	$labels = array(
		'name'                => __( 'Films' ),
		'singular_name'       => __( 'Film'),
		'menu_name'           => __( 'Films'),
		'parent_item_colon'   => __( 'Parent FIlm'),
		'all_items'           => __( 'All Films'),
		'view_item'           => __( 'View Deal'),
		'add_new_item'        => __( 'Add New Deal'),
		'add_new'             => __( 'Add New'),
		'edit_item'           => __( 'Edit Deal'),
		'update_item'         => __( 'Update Deal'),
		'search_items'        => __( 'Search Deal'),
		'not_found'           => __( 'Not Found'),
		'not_found_in_trash'  => __( 'Not found in Trash')
	);
	$args = array(
		'label'               => __( 'films'),
		'description'         => __( 'Best Crunchify films'),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields'),
		'public'              => true,
		'hierarchical'        => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'has_archive'         => true,
		'can_export'          => true,
		'exclude_from_search' => false,
	        'yarpp_support'       => true,
		'taxonomies' 	      => array('post_tag'),
		'publicly_queryable'  => true,
		'capability_type'     => 'page'
    );

    register_post_type( 'films', $args );
}
add_action( 'init', 'crunchify_films_custom_post_type', 0 );

// Let us create Taxonomy for Custom Post Type
add_action( 'init', 'crunchify_create_films_custom_taxonomy', 0 );
 
//create a custom taxonomy name it "type" for your posts
function crunchify_create_films_custom_taxonomy() {
 
  $labels = array(
    'name' => 'Category Films',
    'singular_name' => _x( 'Cat Films', 'twentyseventeen' ),
    'search_items' =>  __( 'Search Films' ),
    'all_items' => __( 'All Films' ),
    'parent_item' => __( 'Parent Category Films' ),
    'parent_item_colon' => __( 'Parent Category Films:' ),
    'edit_item' => __( 'Edit Category Films' ), 
    'update_item' => __( 'Update Category Films' ),
    'add_new_item' => __( 'Add New Category Films' ),
    'new_item_name' => __( 'New Category Films Name' ),
    'menu_name' => __( 'Category Films' ),
  ); 	
 
  register_taxonomy('film_category',array('films'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'category-films' ),
  ));
}

function global_notice_meta_box_callback( $post ) {

    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'film_price_nonce', 'film_price_nonce' );

    $value = get_post_meta( $post->ID, 'film_price', true );
    echo '<labet for="film_price"> Price </label>';
    echo '<input type="number" style="width:100%" id="film_price" name="film_price" value="' . esc_attr( $value ) . '" />';
}

add_action( 'add_meta_boxes', 'global_notice_meta_box' );

function global_notice_meta_box() {

    $screens = array( 'films' );

    foreach ( $screens as $screen ) {
        add_meta_box(
            'global-notice',
            __( 'Enter price Film', 'twentyseventeen' ),
            'global_notice_meta_box_callback',
            $screen,
            'side'
        );
    }
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id
 */
function save_film_price_notice_meta_box_data( $post_id ) {

    // Check if our nonce is set.
    if ( ! isset( $_POST['film_price_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['film_price_nonce'], 'film_price_nonce' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    }
    else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if ( ! isset( $_POST['film_price'] ) ) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field( $_POST['film_price'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, 'film_price', $my_data );
}

add_action( 'save_post', 'save_film_price_notice_meta_box_data' );


class WCCPT_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {

    /**
     * Method to read a product from the database.
     * @param WC_Product
     */

    public function read( &$product ) {

        $product->set_defaults();

        if ( ! $product->get_id() || ! ( $post_object = get_post( $product->get_id() ) ) || ! in_array( $post_object->post_type, array( 'films', 'product' ) ) ) { // change films with your post type
            throw new Exception( __( 'Invalid product.', 'woocommerce' ) );
        }

        $id = $product->get_id();

        $product->set_props( array(
            'name'              => $post_object->post_title,
            'slug'              => $post_object->post_name,
            'date_created'      => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
            'date_modified'     => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
            'status'            => $post_object->post_status,
            'description'       => $post_object->post_content,
            'short_description' => $post_object->post_excerpt,
            'parent_id'         => $post_object->post_parent,
            'menu_order'        => $post_object->menu_order,
            'reviews_allowed'   => 'open' === $post_object->comment_status,
        ) );

        $this->read_attributes( $product );
        $this->read_downloads( $product );
        $this->read_visibility( $product );
        $this->read_product_data( $product );
        $this->read_extra_data( $product );
        $product->set_object_read( true );
    }

    /**
     * Get the product type based on product ID.
     *
     * @since 3.0.0
     * @param int $product_id
     * @return bool|string
     */
    public function get_product_type( $product_id ) {
        $post_type = get_post_type( $product_id );
        if ( 'product_variation' === $post_type ) {
            return 'variation';
        } elseif ( in_array( $post_type, array( 'films', 'product' ) ) ) { // change films with your post type
            $terms = get_the_terms( $product_id, 'product_type' );
            return ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
        } else {
            return false;
        }
    }
}

add_filter( 'woocommerce_data_stores', 'woocommerce_data_stores' );

function woocommerce_data_stores ( $stores ) {      
    $stores['product'] = 'WCCPT_Product_Data_Store_CPT';
    return $stores;
}


add_filter('woocommerce_product_get_price','reigel_woocommerce_get_price',20,2);
function reigel_woocommerce_get_price( $price, $custom_post){
    error_log($custom_post);
	if (get_post_type($custom_post->get_id()) == 'films')
		$price = get_post_meta($custom_post->get_id(), "film_price", true);
	return $price;
}

add_filter('the_content','rei_add_to_cart_button', 20,1);
function rei_add_to_cart_button($content){
	global $post;
	if ($post->post_type !== 'films') {return $content; }
	
	ob_start();
	?>
    <h2> Цена : <?php echo get_post_meta($post->ID, "film_price", true)?> </h2>
	<form action="" method="post">
		<input name="add-to-cart" type="hidden" value="<?php echo $post->ID ?>" />
		<input name="quantity" type="number" value="1" min="1"  />
		<input name="submit" type="submit" value="Add to cart" />
	</form>
	<?php
	
	return ob_get_clean().$content;
}

/**
 * Add Scype to registration form
 */
function wooc_extra_register_fields() {
    $skype = isset( $_POST['billing_phone'] ) ? esc_attr_e( $_POST['billing_phone'] ) : '';
    ?>
       <p class="form-row form-row-wide">
       <label for="skype"><?php _e( 'Skype', 'woocommerce' ); ?></label>
       <input type="text" class="input-text" name="skype" id="skype" value="<?php $skype ?>" placehoder="Enter you skype"/>
       </p>
       <div class="clear"></div>
       <?php
 }
 add_action( 'woocommerce_register_form_start', 'wooc_extra_register_fields' );

?>