<?php
/*
    Plugin Name:Post like and dislike
    Description:Helps to estimate like and dislike for post
    Version:1.0
    Author: Puja Sinha
*/
class PostLikeDisLike
{
    /*
     * query in wordpress database
     * https://developer.wordpress.org/reference/classes/wpdb/
     * */
    private $tableName;
    private $dbCharSet;
    private $version;
    private $pageSlug;
    private $firstSection;

    /**
     * PostLikeDisLike constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->version = get_option( 'post-like-dislike', '1.0' );
        $this->tableName = $wpdb->prefix . 'post_like_dislike';
        $this->dbCharSet = $wpdb->get_charset_collate();
        $this->pageSlug = "post-like-setting";
        $this->firstSection = "post_like_first_section";

        /*hook for activate plugin*/
        register_activation_hook( __FILE__,  [ $this, 'plugin_activate' ] );
        /*hook for deactivate plugin*/
        register_deactivation_hook(__FILE__,[$this,'plugin_deactivate']);
        /*hook for delete plugin*/
        register_uninstall_hook(__FILE__,array($this,'deletePlugin'));
        /*load asset*/
        add_action('wp_footer',array($this,'load_assets'));
        /*menu*/
        add_action('admin_menu', array($this, 'addMenu'));
        /*setting page*/
        add_action('admin_init', array($this, 'settings'));
        /*filter*/
        add_filter('the_content',array($this,'addButtonsInPost'));
        add_filter('the_content',array($this,'createLikeHtml'),10);
        /*
         * Fires non-authenticated Ajax actions for logged-out users.
         * do_action( "wp_ajax_nopriv_{$action}" )
         * */
        add_action('wp_ajax_nopriv_like_btn_ajax_handle',array($this,'like_btn_ajax_handle'));
        /*
         * Fires authenticated Ajax actions for logged-in users.
         * do_action( "wp_ajax_{$action}" )
         * */
        add_action('wp_ajax_like_btn_ajax_handle',array($this,'like_btn_ajax_handle'));

        add_action('wp_ajax_nopriv_dislike_btn_ajax_handle',array($this,'dislike_btn_ajax_handle'));
        add_action('wp_ajax_dislike_btn_ajax_handle',array($this,'dislike_btn_ajax_handle'));
    }

    function createLikeHtml($content)
    {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $post_id = get_the_ID();
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $this->tableName where post_id=$post_id and like_count=1" );

        $html='<div class="centered">This post has been liked <strong>'.$total.'</strong> time(s)</div><p>';
        return $content.$html;
    }

    public function dislike_btn_ajax_handle() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        if(isset($_POST['pid']) && $_POST['uid'])
        {
            $user_id = $_POST['uid'];
            $post_id = $_POST['pid'];
            $check = $wpdb->get_var( "SELECT COUNT(*) FROM $this->tableName where user_id=$user_id and post_id=$post_id and dislike_count=1" );
            if($check==0)
                {
                     $wpdb->insert($this->tableName,array(
                        'post_id'=>$_POST['pid'],
                        'user_id'=>$_POST['uid'],
                        'like_count'=>0,
                        'dislike_count'=>1,
                    ),array('%d','%d','%d','%d'));
                    if($wpdb->insert_id)
                        {
                            echo "Thank you for your response !";
                        }
                }
            else {
                echo "Sorry! you are already dislike this post !";
            }
            wp_die();
        }
    }

    public function like_btn_ajax_handle() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        if(isset($_POST['pid']) && $_POST['uid'])
        {
            $user_id = $_POST['uid'];
            $post_id = $_POST['pid'];
            $check = $wpdb->get_var( "SELECT COUNT(*) FROM $this->tableName where user_id=$user_id and post_id=$post_id and like_count=1" );
            if($check==0)
                {
                     $wpdb->insert($this->tableName,array(
                        'post_id'=>$_POST['pid'],
                        'user_id'=>$_POST['uid'],
                        'like_count'=>1,
                        'dislike_count'=>0,
                    ),array('%d','%d','%d','%d'));
                    if($wpdb->insert_id)
                        {
                            echo "Thank you for loving this post !";
                        }
                }
            else {
                echo "Sorry! you are already like this post !";
            }
            wp_die();
        }
    }

    public function addButtonsInPost($content) {
        /*current loggined user*/
        $user = get_current_user_id();
        /*current post id*/
        $post = get_the_ID();
        $likeLabel = get_option('post_like_button_label','Like');
        $dislikeLabel = get_option('post_dislike_button_label','Dislike');
        $wrap = "<div class='btn_container'>";
        $like_btn = "<a href='javascript:void(0);' onclick='like_btn_ajax(".$post.",".$user.")' class='wp_post_btn wp_post_like_btn'><i class='fa fa-thumbs-up'></i> $likeLabel</a>";
        $dis_like_btn = "<a href='javascript:void(0);' onclick='dis_like_btn_ajax(".$post.",".$user.")' class='wp_post_btn wp_post_dis_like_btn'>$dislikeLabel <i class='fa fa-thumbs-down'></i></a>";
        $wrap_end = "</div>";

        $postLikeAjax = '<div id="postLikeAjax" class="post-like-ajax-response"><span></span></div>';

        $content.=$wrap;
        $content.=$like_btn;
        $content.=$dis_like_btn;
        $content.=$wrap_end;

        $content.=$postLikeAjax;
        return $content;
    }

    /*
     * reference https://developer.wordpress.org/plugins/settings/
     * */
    public function settings()
    {
        /*
         * Adds a new section to a settings page.
         * reference https://developer.wordpress.org/reference/functions/add_settings_section/
         * add_settings_section( string $id, string $title, callable $callback, string $page )
         * */
        add_settings_section($this->firstSection,null,null,$this->pageSlug);

        /*
         * Registers a setting and its data.
         * reference https://developer.wordpress.org/reference/functions/register_setting/
         * register like button
         * */
        register_setting("like-settings",'post_like_button_label');
        register_setting("like-settings",'post_dislike_button_label');


        /*
         * Adds a new field to a section of a settings page.
         * reference https://developer.wordpress.org/reference/functions/add_settings_field/
         * add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = array() )
         * */
        add_settings_field('post_like_button_label','Like Button Label',array($this,'like_field_callback'),$this->pageSlug,$this->firstSection);
        add_settings_field('post_dislike_button_label','Dislike Button Label',array($this,'dislike_field_callback'),$this->pageSlug,$this->firstSection);

    }

    public function like_field_callback()
    {
        /*
         * Retrieves an option value from setting based on an option name.
         * */
        $setting = get_option('post_like_button_label'); ?>
        <input type="text" name="post_like_button_label" value="<?php echo isset($setting) ? esc_attr($setting):''?>">
    <?php
    }

    public function dislike_field_callback()
    {
        /*
         * Retrieves an option value from setting based on an option name.
         * */
        $setting = get_option('post_dislike_button_label'); ?>
        <input type="text" name="post_dislike_button_label" value="<?php echo isset($setting) ? esc_attr($setting):''?>">
    <?php
    }

    public function addMenu()
    {
        /*
         * add parent menu (you can add icon)
         * icon - https://developer.wordpress.org/resource/dashicons/
         * */
        add_menu_page('Post Like by Puja Sinha','Post Like Setting','manage_options',$this->pageSlug,array($this,'postSettingPageContent'),'dashicons-thumbs-up',5);
        /*add in setting sub menu*/
        //add_options_page( 'Post Like by Puja Sinha', 'Post Like Setting', 'manage_options', 'post-like-setting', array($this,'postSettingPageContent'),6 );
        /*add in theme sub menu*/
        //add_theme_page( 'Post Like by Puja Sinha', 'Post Like Setting', 'manage_options', 'post-like-setting', array($this,'postSettingPageContent'),6 );
    }

    /*
     * setting page html
     * */
    public function postSettingPageContent()
    { ?>
        <!--include html in php-->
        <div class="post_like_dislike_wrap">
            <h1>Post Like Dislike Setting</h1>
            <form action="options.php" method="POST">
                <?php
                /*
                 * reference https://developer.wordpress.org/reference/functions/settings_fields/
                 * settings_fields( string $option_group )
                 * Outputs nonce, action, and option_page fields for a settings page.
                 * */
                settings_fields("like-settings");
                /*
                 * Prints out all settings sections added to a particular settings page
                 * reference https://developer.wordpress.org/reference/functions/do_settings_sections/
                 * do_settings_sections( string $page )
                 * */
                do_settings_sections($this->pageSlug);
                submit_button();
                ?>
            </form>
        </div>
        <!--Prints out all settings sections added to a particular settings page-->
    <?php
    }

    /*
     * function will call when plugin activate
     * */
    function plugin_activate() {
        /*create table*/
        $this->createPostLikeTable();

    }

    public function load_assets()
    {
        wp_enqueue_style('post-like-style',plugins_url('assets/css/style.css',__FILE__),array(), '1.0.0');
        wp_register_script('post-like-script',plugins_url('assets/js/script.js',__FILE__),array('jquery'), '1.0.0',true);
        wp_enqueue_script('post-ajax-like-script',plugins_url('assets/js/ajax.js',__FILE__),array('jquery'), '1.0.0',true);
        wp_enqueue_script('post-like-script');
        /*
         * Localize a script.
         * reference https://developer.wordpress.org/reference/functions/wp_localize_script/
         * wp_localize_script( string $handle, string $object_name, array $l10n ): bool
         * */
        wp_localize_script('post-ajax-like-script','like_ajax_url',array(
                'ajax_url'=>admin_url('admin-ajax.php')
        ));
        wp_localize_script('post-ajax-like-script','dislike_ajax_url',array(
                'ajax_url'=>admin_url('admin-ajax.php')
        ));
    }

    function createPostLikeTable()
    {
        /*create table*/
        $sql = "CREATE TABLE if not exists $this->tableName(
            id bigint not null auto_increment,
            post_id bigint not null,
            user_id bigint null,
            like_count bigint not null default 0,
            dislike_count bigint not null default 0,
            created_at timestamp  DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            primary key(id)
        ) $this->dbCharSet";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        /*hook for create table*/
        dbDelta($sql);
    }

    /*
     * function will call when plugin deactivate
     * */
    function plugin_deactivate() {
        /*delete table*/
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS $this->tableName" );
    }

    /*
     * function will call when plugin delete
     * */
    function deletePlugin() {

    }
}

$postLikeDisLike = new PostLikeDisLike();
