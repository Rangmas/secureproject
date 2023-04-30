<?php
/** 
 * Plugin Name: Contact Enquiry form
 * Description: Customers can send the email
 * Author: Group Project Plugin
 * Version: 1.0.0
 * Text-Domain: Contact-Enquiry-form
*/

// This block of code ensures that the plugin is being accessed from within WordPress, to prevent direct access from users.
if (!defined('ABSPATH'))
{
    die('No one is permitted here: Restricted Area. Go Back!');
    exit;
}

// This is a PHP class that defines the ContactEnquiryForm plugin and its functionality.
class ContactEnquiryForm{

    public function __construct()
    {
        // This is an action hook that runs when WordPress initializes, and calls the create_custom_post_type() function.
        add_action('init', array($this, 'create_custom_post_type'));

        //Add assets (js, css, etc)
        add_action ('wp_enqueue_scripts', array($this,'load_assets'));

        //Add shortcode
        add_shortcode('Contact-Enquiry-Form', array($this, 'load_shortcode'));

        //Load Javascript
        add_action('wp_footer', array($this, 'load_scripts'));

        //Register REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    // This function creates a custom post type called "contact_enquiry_form".
    public function create_custom_post_type()
    {
        // The $args array contains the arguments to be passed to the register_post_type() function, which creates the custom post type.
        $args = array(
            'public'=> true,                           // This sets the post type to be publicly visible on the front end.
            'has_archive'=> true,                      // This enables an archive page for this post type.
            'supports'=> array('title'),               // This specifies that the post type supports only the title field.
            'exclude_from_search'=> true,              // This excludes the post type from search results.
            'publicly_queryable'=> false,              // This disables the post type from being queried publicly.
            'capability'=> 'manage_options',           // This specifies the capability required to manage this post type.
            'labels'=> array(
                'name'=>'Contact Enquiry Form',        // This sets the plural name of the post type.
                'singular_name'=> 'Contact Enquiry Form Entry',  // This sets the singular name of the post type.
            ),
            'menu_icon'=>'dashicons-edia-text',        // This sets the icon to be displayed in the WordPress admin menu.
        );
        // This function call creates the custom post type with the arguments provided in the $args array.
        register_post_type('contact_enquiry_form', $args);
    }

    public function load_assets() // The css and javascript functions that wordpress supports so it can run the css and js files.
    {
        wp_enqueue_style(
            'contact-Enquiry-Form',
            plugin_dir_url(__FILE__) . 'css/Contact-Enquiry-Form.css',
            array(),
            1,
            'all'      
        );
        wp_enqueue_script(
            'contact-Enquiry-Form',
            plugin_dir_url(__FILE__) . 'js/Contact-Enquiry-Form.js',
            array('jquery'),
            1,
            true
        );
    }
    public function load_shortcode() //loading form fill up details
    {
        ?>
        <div class="Contact-Enquiry-Form">
        <h1>Send us an Email</h1>
        <p>Please fill the below form</p>
        <form id="Contact-Enquiry-Form__Form">
    <div class="form-group mb-2">
        <input name="name" type="text" placeholder="Name" class="form-control">
    </div>
    <div class="form-group mb-2">
        <input name="email" type="email" placeholder="Email" class="form-control">
    </div>
    <div class="form-group mb-2">
        <input name="Phone" type="tel" placeholder="Phone" class="form-control">
    </div>
    <div class="form-group mb-2">
    <textarea name="message" placeholder="Type your message" class="form-control"></textarea>
    </div>
    <div class="form-group">
        <button class="btn btn-success btn-block w-130">Send Message</button>
    </div> 
</form>
    
    <?php
    }
    public function load_scripts() //nonce number only once created that will protect from csrf attack
    {
    ?>
        <script>
            var nonce='<?php echo wp_create_nonce('wp_rest');?>';
            (function($)
            {
                $('#Contact-Enquiry-Form__Form').submit(function(event){
                   event.preventDefault();
                    var form=$(this).serialize();
                
                    $.ajax({
                        method:'post',
                        url:'<?php echo get_rest_url(null,'Contact-Enquiry-Form/v1/send-email');?>',
                        headers:{'X-WP-Nonce': nonce},
                        data: form
                    })
                });
    
            })(jQuery)
        </script>
    <?php
    }
    
    public function register_rest_api() //creating a rest api route and call back handle function to send the email form data
    {
        register_rest_route('Contact-Enquiry-Form/v1','send-email', array(
'methods'=> 'POST',
'callback'=> array($this,'handle_Contact_Enquiry_Form')
        ));
    }
public function handle_Contact_Enquiry_Form($data)
{

    $headers = $data->get_headers(); //recieving headers
    $params = $data->get_params(); //recieving parameters

    $nonce = $headers['x_wp_nonce'][0];  //checks the x_wp_nonce header value to ensure that the request is legitimate and not a CSRF attack.
    if(!wp_verify_nonce($nonce,'wp_rest')) //  This is done by comparing the nonce value with the value of the wp_rest nonce.
    {
        return new WP_REST_Response('Message not sent', 422);  // If the nonce is invalid, the function returns a REST response with an error message and a status code of 422.
    }

    // Validate email input
    if (isset($params['email']) && !is_email($params['email'])) {
        return new WP_REST_Response('Invalid email address', 422);
    }

    // Sanitize email input
    if (isset($params['email'])) {
        $email = sanitize_email($params['email']);
    }

    // Sanitize message input
    $message = isset($params['message']) ? sanitize_text_field($params['message']) : '';

    // Insert data into the database
    global $wpdb;
    $wpdb->insert(
        'Contact-Enquiry-Form',
        array(
            'email' => $email,
            'message' => $message,
            'date' => current_time('mysql'),
        ),
        array(
            '%s',
            '%s',
            '%s',
        )
    );

    $post_id = wp_insert_post([
        'post_type'=> 'Contact-Enquiry-Form',
        'post_title'=> 'Contact enquiry',
        'post_status'=>'publish'
    ]);
    
//If the nonce is valid, the function proceeds with handling the contact enquiry form submission.
    if($post_id)
    {
        return new WP_REST_Response('Thank You For Your Email', 200);  // message is sent successfully
    }
}

}
// This line creates a new instance of the ContactEnquiryForm class, which runs the __construct() function and sets up the plugin.
new ContactEnquiryForm();