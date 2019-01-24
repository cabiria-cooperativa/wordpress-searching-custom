<?php
/**
 * Plugin Name: Cabiria Plugin Searchin'custom
 * Plugin URI: https://www.cabiria.net
 * Description: Plugin per la ricerca in custom post / custom field.
 * Version: 1.0.0
 * Author: Cabiria
 * Author URI: https://www.cabiria.net
 * Text Domain: cabi
 */

class CabiSearchingCustom {

    private $data = array();
    private $compares = array();
    
    function __construct() {
        
        add_action('wp_enqueue_scripts', array($this, 'init'));
        add_shortcode('cabi_searching_custom_form', array($this, 'render_form'));
        add_shortcode('cabi_searching_custom_results', array($this, 'render_results'));

        /* azioni ajax */
        add_action('wp_ajax_nopriv_hello_world_ajax', array($this, 'hello_world_ajax'));
        add_action('wp_ajax_hello_world_ajax', array($this, 'hello_world_ajax'));

        /* attivazione e disattivazione plugin */
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook( __FILE__, array($this, 'deactivation'));   
    }

    function activation(){}

    function deactivation(){}

    function init() {
        wp_enqueue_style( 'cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' , array(), '1');
        wp_enqueue_script('cabi_plugin', plugin_dir_url( __FILE__ ) . 'assets/js/cabi-searching-custom.js',array('jquery'),'1',true);
        wp_localize_script('init', 'init_ajax', array('url' => admin_url( 'admin-ajax.php' )));
    }

    private function get_custom_fields($post_type, $fields_list) {
        $data = array();
        if (post_type_exists($post_type)) {
            /* recupero i gruppi (di campi) ACF */
            $data = acf_get_field_groups(array('post_type' => $post_type));
            $groups = array();
            $fields = array();
            for ($i = 0; $i < count($data); $i++) {
                $groups[$i]['key'] = $data[$i]['key'];
                $groups[$i]['title'] = $data[$i]['title'];
                /* recupero tutti i campi di un gruppo ACF */
                $fields = acf_get_fields($groups[$i]['key']);
                $k = 0;
                for ($j = 0; $j < count($fields); $j++) {
                    /* se il campo è da mostrare ... */
                    if (in_array($fields[$j]['name'], $fields_list)) {
                        // salvo il dato
                        $this->data[$k]['label'] = $fields[$j]['label'];
                        $this->data[$k]['name'] = $fields[$j]['name'];
                        $this->data[$k]['id'] = $fields[$j]['key'];
                        $this->data[$k]['type'] = $fields[$j]['type'];
                        $this->data[$k]['choices'] = $fields[$j]['choices'];
                        $k++;
                    }
                }
            }
        }
    }

    function render_form($atts, $content = null) {
		$this->data = array();
        extract(shortcode_atts(array(
            'post_type' => 'post',
            'fields' => '',
            'landing' => 'searching-custom'
            ), $atts,  'render_form'));
        $fields = preg_replace('/\s+/', '', $fields);    
        $fields_list = explode(',', $fields);
        $this->get_custom_fields($post_type, $fields_list);
        ob_start();
		?>
        <form role="search" method="post" class="cabi_searching_custom" action="<?php echo get_site_url() . '/' . $landing ?>">
            <input type="hidden" name="cabi_searching_custom_post_type" value="<?php echo $post_type ?>">
            <?php
            for ($i = 0; $i < count($this->data); $i++) {
                switch($this->data[$i]['type']) {
                    case 'text':
                    case 'number':
                        ?>
                        <input type="text" value="<?php echo $_POST[$this->data[$i]['name']] ?>" class="cabi_searching_custom__field cabi_searching_custom_<?php echo $this->data[$i]['name'] ?>" name="<?php echo $this->data[$i]['name'] ?>" placeholder="<?php echo strip_tags($this->data[$i]['label']) ?>" id="<?php echo $this->data[$i]['id'] ?>">
                        <?php
                        break;
                    case 'select':
                        ?>
                        <select class="cabi_searching_custom__field cabi_searching_custom_<?php echo $this->data[$i]['name'] ?>" name="<?php echo $this->data[$i]['name'] ?>">
                            <option value=""><?php echo $this->data[$i]['label'] ?>...</option>
                            <?php
                            $choices = $this->data[$i]['choices'];
                            foreach($choices as $key => $value) {
                                ?><option value="<?php echo $key ?>"><?php echo $value ?></option><?php
                            }
                            ?>
                        </select>
                        <?php
                        break;
                }
            }
            wp_nonce_field('cabi_search_custom_form');
            ?>
            <input class="cabi_searching_custom__field cabi_searching_custom__submit" type="submit" value="<?php _e('Search', 'cabi') ?>" />
        </form>
        <?php
        return ob_get_clean();
    }

    private function create_query($compares_list) {
        $meta_query = "array('relation' => 'AND',";
        $i = 0;
        foreach ($_POST as $key => $value) {
            $pos = strpos($key, $_POST['cabi_searching_custom_post_type']);
            if ($value && $pos !== false) {
                //echo "{$i} &gt; {$key} ({$compares_list[$i]}): {$value}<br>";
                switch ($compares_list[$i]) {
                    case 'lk':
                        $compare = "'compare' => 'LIKE',";
                    case 'eq':
                        $compare = "'compare' => '=',";
                        break;
                    case 'gt':
                        $compare = "'type' => 'NUMERIC', 'compare' => '>',";
                        break;
                    case 'ge':
                        $compare = "'type' => 'NUMERIC', 'compare' => '>=',";
                        break;
                    case 'lt':
                        $compare = "'type' => 'NUMERIC', 'compare' => '<',";
                        break;
                    case 'le':
                        $compare = "'type' => 'NUMERIC', 'compare' => '<=',";
                        break;
                }
                $meta_query .= "
                    array(
                    'key' => '{$key}',
                    'value' => '{$value}',
                    {$compare}
                    ),";
            }
            if ($pos !== false) $i++;
        }
        $meta_query .= ")";
        eval("\$args = array(
            'nopaging' => 'true',
            'posts_per_page' => -1,
            'post_type' => \$_POST['cabi_searching_custom_post_type'],
            'meta_query' => " . $meta_query . ");");
        //var_dump($args);
        return $args;
    }

    function render_results($atts, $content = null) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'cabi_search_custom_form')) exit();
		$this->data = array();
        extract(shortcode_atts(array(
            'template' => 'default',
            'compares' => '',
            'container_class' => ''
            ), $atts,  'render_results'));  
        $compares = preg_replace('/\s+/', '', $compares);
        $compares_list = explode(',', $compares);
        $this->get_data($this->create_query($compares_list));
        if (!empty($this->data)) {
            $tmpl_url = plugin_dir_url( __FILE__ ) . 'assets/templates/' . $template.'.html';
            $template_content = @file_get_contents($tmpl_url);
            $html = '';
            for ($i = 0; $i < count($this->data); $i++) {
                $row = $template_content;
                foreach ($this->data[$i] as $key => $value) {
                    $row = str_replace("[+".$key."+]", $value, $row);
                }
                $html .= $row;
            }
            ob_start();
            if ($container_class) echo '<div class="' . $container_class . '">' . $html . '</div>';
            else echo $html;
            return ob_get_clean();
        }
    }

    private function get_data($args, $debug = 0) {
        $loop = new WP_Query($args);
        if ($loop->have_posts()) {
            $i = 0;
            while ($loop->have_posts()) {
                $loop->the_post();
                $id = get_the_ID();
                $this->data[$i]['permalink'] = get_the_permalink($id);
                $this->data[$i]['title'] = get_the_title();
                $this->data[$i]['content'] = $this->get_content();
                $this->data[$i]['excerpt'] = nl2br(get_the_excerpt());
                $this->data[$i]['content_notag'] = get_the_content();
                /* immagini */
                if (function_exists('has_post_thumbnail') && has_post_thumbnail($id)) {
				    $dummy = wp_get_attachment_image_src(get_post_thumbnail_id($id),'thumbnail');
				    $this->data[$i]['thumbnail'] = $dummy[0];
                    $dummy = wp_get_attachment_image_src(get_post_thumbnail_id($id),'medium');
				    $this->data[$i]['medium'] = $dummy[0];
                    $dummy = wp_get_attachment_image_src(get_post_thumbnail_id($id),'large');
				    $this->data[$i]['large'] = $dummy[0];
                    $dummy = wp_get_attachment_image_src(get_post_thumbnail_id($id),'full');
				    $this->data[$i]['full'] = $dummy[0];
                }
                /* advanced custom fields */
                if (function_exists('get_fields')) {
                    $fields = get_fields($id);
                    if ($fields) {
                        foreach( $fields as $name => $value ) {
                            $this->data[$i][$name] = $value;
                        }
                    }
                }
                $i++;
            }
        }
        wp_reset_postdata();
    }

    private function get_content($more_link_text = '(more...)', $stripteaser = 0, $more_file = '') {
    	$content = get_the_content($more_link_text, $stripteaser, $more_file);
    	$content = apply_filters('the_content', $content);
    	$content = str_replace(']]>', ']]&gt;', $content);
    	return $content;
    }

}

new CabiSearchingCustom();