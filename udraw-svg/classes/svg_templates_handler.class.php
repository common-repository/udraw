<?php
if (!class_exists('SVG_templates_handler')) {
    class SVG_templates_handler extends uDrawAjaxBase {
        public function __construct() { }
        public function init_actions(){
            add_action('wp_ajax_udraw_svg_save_template', array(&$this, 'save_template'));
            add_action('wp_ajax_udraw_svg_delete_template', array(&$this, 'delete_template'));
            add_action('wp_ajax_udraw_svg_get_order_item_design', array(&$this, 'ajax_get_order_item_design'));
            add_action('wp_ajax_udraw_svg_build_pdf', array(&$this, 'build_pdf'));
            add_action('wp_ajax_udraw_svg_get_template', array(&$this, 'get_template'));
            add_action('wp_ajax_udraw_svg_pdf_ready', array(&$this, 'udraw_svg_pdf_ready'));
            add_action('wp_ajax_udraw_svg_check_pdf_download', array(&$this, 'udraw_svg_check_pdf_download'));
            add_action('wp_ajax_udraw_svg_upload_pdf_template', array(&$this, 'upload_pdf_template'));
            add_action('wp_ajax_udraw_svg_get_pngs', array(&$this, 'udraw_svg_get_pngs'));
            
            add_action('wp_ajax_nopriv_udraw_svg_get_order_item_design', array(&$this, 'ajax_get_order_item_design'));
            add_action('wp_ajax_nopriv_udraw_svg_build_pdf', array(&$this, 'build_pdf'));
            add_action('wp_ajax_nopriv_udraw_svg_get_template', array(&$this, 'get_template'));
            add_action('wp_ajax_nopriv_udraw_svg_pdf_ready', array(&$this, 'udraw_svg_pdf_ready'));
            add_action('wp_ajax_nopriv_udraw_svg_check_pdf_download', array(&$this, 'udraw_svg_check_pdf_download'));
            
            add_action('udraw_svg_build_pdf', array(&$this, 'build_pdf'));
        }
        public function save_template ($name = '', $design_path = '', $preview = '', $access_key = '', $design_width = '', $design_height = '') {
            //Check template count first
            $uDraw = new uDraw();
            $uDraw_SVG = new uDraw_SVG();
            $update_args = array();
            $format_args = array();
            $template_count = $uDraw_SVG->get_udraw_SVG_template_count();
            if ($template_count > 2 && $uDraw->is_udraw_okay() === false) {
                $response = array(
                    'success' => false,
                    'message' => __('Maximum number of templates reached', 'udraw_svg')
                );
                $this->sendResponse($response);
            }
            if (isset($_REQUEST['name'])) {
                $name = uDrawUtil::sanitize_variables($_REQUEST['name']);
                $update_args['name'] = $name;
                array_push($format_args, '%s');
            }
            if (isset($_REQUEST['design_path'])) {
                $design_path = uDrawUtil::sanitize_variables($_REQUEST['design_path']);
                if (substr($design_path, strlen($design_path) - 4) === 'json' && strrpos($design_path, '_templates_') === false) {
                    $response = array(
                        'success' => false,
                        'message' => __('Please re-upload the design file.', 'udraw_svg')
                    );
                    $this->sendResponse($response);
                }
                $update_args['design_path'] = $design_path;
                array_push($format_args, '%s');
            }
            if (isset($_REQUEST['preview'])) {
                $preview = uDrawUtil::sanitize_variables($_REQUEST['preview']);
                $update_args['preview'] = $preview;
                array_push($format_args, '%s');
            }
            if (isset($_REQUEST['access_key'])) {
                $access_key = uDrawUtil::sanitize_variables($_REQUEST['access_key']);
            }
            if (isset($_REQUEST['design_summary'])) {
                $design_summary = uDrawUtil::sanitize_variables($_REQUEST['design_summary']);
                $update_args['design_summary'] = serialize($design_summary);
                array_push($format_args, '%s');
            }
            
            global $wpdb;
            $type = 'added';
            $table_name = $wpdb->prefix . 'udraw_svg_templates';
            $user_id = get_current_user_id();
            $dt = new DateTime();
            $time = $dt->format('Y-m-d H:i:s');
            $update_args['date'] = $time;
            array_push($format_args, '%s');
            if (strlen($access_key) > 0) {
                $type = 'updated';
                $result = $wpdb->update($table_name,
                    $update_args,
                    array(
                        'access_key' => $access_key
                    ),
                    $format_args,
                    array('%s')
                );
            } else {
                $update_args['create_user_id'] = $user_id;
                $update_args['access_key'] = uniqid('udraw_SVG_');
                array_push($format_args, '%d');
                array_push($format_args, '%s');
                $result = $wpdb->insert($table_name,
                    $update_args,
                    $format_args
                );
            }
            $response = array(
                'success' => $result,
                'type' => $type,
                'message' => ''
            );
            $this->sendResponse($response);
        }
        public function delete_template($access_key = '') {
            global $wpdb;
            if (isset($_REQUEST['access_key'])) {
                $access_key = uDrawUtil::sanitize_variables($_REQUEST['access_key']);
            } else {
                $this->sendResponse(false);
            }
            $templates_table_name = $wpdb->prefix . 'udraw_svg_templates';
            $response = $wpdb->delete($templates_table_name, array('access_key' => $access_key));
            $this->sendResponse($response);
        }
        public function ajax_get_order_item_design ($order_item_id = '') {
            if (isset($_REQUEST['order_item_id'])) {
                $order_item_id = uDrawUtil::sanitize_variables($_REQUEST['order_item_id']);
            }
            $return_object = $this->get_order_item_design($order_item_id);
            $this->sendResponse($return_object);
        }
        public function get_order_item_design($order_item_id = '') {
            $data = wc_get_order_item_meta($order_item_id, 'udraw_SVG_data');
            $design_path = $data['udraw_SVG_design_data'];
            $return_object = array(
                'output_path' => pathinfo($design_path, PATHINFO_DIRNAME) . '/',
                'file_name' => pathinfo($design_path, PATHINFO_BASENAME)
            );
            return $return_object;
        }
        public function build_pdf ($order_item_id = '', $order_id = '') {
            if (isset($_REQUEST['order_item_id'])) {
                $order_item_id = uDrawUtil::sanitize_variables($_REQUEST['order_item_id']);
            }
            if (isset($_REQUEST['order_id'])) {
                $order_id = uDrawUtil::sanitize_variables($_REQUEST['order_id']);
            }
            $uDrawUtil = new uDrawUtil();
            
            $data = $this->get_order_item_design($order_item_id);
            $design_path = $data['output_path'] . $data['file_name'];
            
            $replace = wp_make_link_relative(UDRAW_STORAGE_URL);
            if (strpos($design_path, UDRAW_STORAGE_URL) !== false) {
                $replace = UDRAW_STORAGE_URL;
            }
            $design_dir = str_replace($replace, UDRAW_STORAGE_DIR, $design_path);
        
            $documents = array();
            $pages_info = array();
            $preview = '';
            $width = 0;
            $height = 0;
            
            if (substr($design_dir, strlen($design_dir) - 4) === 'json') {
                $pages = json_decode($uDrawUtil->get_web_contents($design_path))->pages;
                
                for ($i = 0; $i < count($pages); $i++) {
                    $design_data = file_get_contents($pages[$i]->design_file);
                    array_push($documents, $design_data);
                    $svg_object = simplexml_load_string($design_data);
                    $page_info_object = array(
                        'width' => 0,
                        'height' => 0,
                        'ratio' => 1
                    );
                    $attributes = $svg_object->Attributes();
                    $viewbox = explode(' ', $attributes['viewBox']);
                    //width and height are viewbox values
                    $width = ceil(floatval((string)$viewbox[2]));
                    $height = ceil(floatval((string)$viewbox[3]));
                    
                    $page_info_object['width'] = $width;
                    $page_info_object['height'] = $height;
                    array_push($pages_info, $page_info_object);
                }
            } else {
                $design_data = $uDrawUtil->get_web_contents($design_url);
                array_push($documents, $design_data);
                $svg_object = simplexml_load_string($design_data);
                $page_info_object = array(
                    'width' => 0,
                    'height' => 0,
                    'ratio' => 1
                );
                $attributes = $svg_object->Attributes();
                $page_info_object['width'] = $width = ceil(floatval((string)$attributes['width']) / 2);
                $page_info_object['height'] = $height = ceil(floatval((string)$attributes['height']) / 2);
                array_push($pages_info, $page_info_object);
            }
            $activationKey = base64_encode(uDraw::get_udraw_activation_key() .'%%'. str_replace('.', '-', $_SERVER['HTTP_HOST']));
            
            $SVG_settings = new uDraw_SVG_settings();
            $_settings = $SVG_settings->get_settings();
            $test = $_settings['udraw_svg_debug_pdf_production'];
            $async = (count($documents) === 1) && uDraw::is_udraw_okay() && ($_SERVER['HTTP_HOST'] !== 'localhost');
            
            $query_data = array(
                'document' => json_encode($documents),
                'width' => $width,
                'height' => $height,
                'action' => 'convert',
                'type' => 'udrawsvg',
                'display-json' => 't',
                'pagesInfo' => json_encode($pages_info),
                'ratio' => 1,
                'key' => $activationKey,
                'async' => $async,
                'test' => $test
            );
            $callback_query_data = array(
                'action'        => 'udraw_svg_pdf_ready',
                'order_item_id' => $order_item_id,
                'order_id'     => $order_id
            );
            $callback_url = admin_url('admin-ajax.php') . '?' . http_build_query($callback_query_data);
            
            $query_data['callback_url'] = $callback_url;
            $pdf_response = json_decode($uDrawUtil->get_web_contents(UDRAW_CONVERT_URL, http_build_query($query_data)));
                
            $preview_url = str_replace(wp_make_link_relative(UDRAW_STORAGE_URL), UDRAW_STORAGE_URL, $preview);
            wc_delete_order_item_meta($order_item_id, '_udraw_preview_xref');
            wc_add_order_item_meta($order_item_id, '_udraw_preview_xref', array($preview_url));
            wc_delete_order_item_meta($order_item_id, '_udraw_SVG_PDF');
            wc_delete_order_item_meta($order_item_id, '_udraw_pdf_xref');
            
            if (!$async) {
                if (isset($pdf_response->pdf)) {
                    $download_url = $pdf_response->pdf;
                    $pdf_file = 'uDraw-SVG-Order_' . $order_id . '-' . $order_item_id . '.pdf';
                    
                    $uDrawUtil->download_file($download_url, UDRAW_ORDERS_DIR . $pdf_file);
                    wc_add_order_item_meta($order_item_id, '_udraw_SVG_PDF', wp_make_link_relative(UDRAW_ORDERS_URL . $pdf_file));
                    wc_add_order_item_meta($order_item_id, '_udraw_pdf_xref', array(UDRAW_ORDERS_URL . $pdf_file));
                    
                    $this->sendResponse(array('url' => UDRAW_ORDERS_URL . $pdf_file));
                }
            }
        }
        
        public function udraw_svg_pdf_ready () {
            $uDrawUtil = new uDrawUtil();
            if (isset($_REQUEST['order_item_id']) && isset($_REQUEST['order_id'])) {
                $order_item_id = uDrawUtil::sanitize_variables($_REQUEST['order_item_id']);
                $order_id = uDrawUtil::sanitize_variables($_REQUEST['order_id']);
                if (isset($_REQUEST['download_url'])) {
                    $download_url = uDrawUtil::sanitize_variables($_REQUEST['download_url']);
                    $pdf_file = 'uDraw-SVG-Order_' . $order_id . '-' . $order_item_id . '.pdf';
                    
                    $uDrawUtil->download_file($download_url, UDRAW_ORDERS_DIR . $pdf_file);
                    wc_add_order_item_meta($order_item_id, '_udraw_SVG_PDF', wp_make_link_relative(UDRAW_ORDERS_URL . $pdf_file));
                    wc_add_order_item_meta($order_item_id, '_udraw_pdf_xref', array(UDRAW_ORDERS_URL . $pdf_file));
                }
            }
        }
        
        public function udraw_svg_check_pdf_download ($order_item_id = '') {
            if (isset($_REQUEST['order_item_id'])) {
                $order_item_id = uDrawUtil::sanitize_variables($_REQUEST['order_item_id']);
                $url = wc_get_order_item_meta($order_item_id, '_udraw_SVG_PDF');
                $this->sendResponse($url);
            }
        }
        
        public function get_template ($template_id = '', $clone = false, $_output_path = '') {
            if (isset($_REQUEST['template_id'])) {
                $template_id = uDrawUtil::sanitize_variables($_REQUEST['template_id']);
            }
            if (isset($_REQUEST['clone'])) {
                $clone = uDrawUtil::sanitize_variables($_REQUEST['clone']);
            }
            if (isset($_REQUEST['output_path'])) {
                $_output_path = uDrawUtil::sanitize_variables($_REQUEST['output_path']);
            }
            global $wpdb;
            $table_name = $wpdb->prefix . 'udraw_svg_templates';
            $result = $wpdb->get_row("SELECT * FROM $table_name WHERE ID=$template_id");
            if ($clone) {
                $design = $result->design_path;
                $result->design_path = $this->clone_json_file($_output_path, $design);
            }
            $result->design_summary = unserialize($result->design_summary);
            $this->sendResponse($result);
        }
		
        public function upload_pdf_template () {
            $uDraw = new uDraw();
            $uDrawUpload = new uDrawUpload();
            // Check file exstension
            $ext = strtolower(pathinfo($_FILES['files']['name'][0], PATHINFO_EXTENSION));
			$file_name = strtolower($_FILES['files']['name'][0]); 
			$new_file = rand(1, 32) .'_'. str_replace(' ','_', $file_name) . '.' . $ext;
            if (!file_exists(UDRAW_STORAGE_DIR . '/_templates_/')) {
                wp_mkdir_p(UDRAW_STORAGE_DIR . '/_templates_/');
                wp_mkdir_p(UDRAW_STORAGE_DIR . '/_templates_/output/');
                wp_mkdir_p(UDRAW_STORAGE_DIR . '/_templates_/assets/');
            }
			
            $rand_id = $this->make_uniqid_folder_id(UDRAW_STORAGE_DIR . '/_templates_/output/');
            $_dir = UDRAW_STORAGE_DIR . '/_templates_/output/' . $rand_id;
            $_url = UDRAW_STORAGE_URL . '/_templates_/output/' . $rand_id;
            wp_mkdir_p($_dir);
            if ($ext == 'pdf' || $ext == 'eps') {
				$uploaded_files = $uDrawUpload->handle_upload($_FILES['files'], UDRAW_STORAGE_DIR . '/_templates_/output/', UDRAW_STORAGE_URL . '/_templates_/output/', array('pdf' => 'application/pdf', 'eps' => 'application/postscript') );

				if ( !key_exists('error', $uploaded_files[0]) ) {
                    // Pass the PDF document to remote converting server and convert to SVG document.
                    $activationKey = uDraw::get_udraw_activation_key();
                    $uDrawUtil = new uDrawUtil();
					
					$new_file_array = array(); $url_array = array();

                    if ($ext == "eps") {
                        $data = array(
                            'epsDocument' => str_replace('localhost', 'udraw-wordpress.ngrok.io', $uploaded_files[0]['url']),
                            'key' => $activationKey
                        );    
                    } else {
                        $data = array(
                            'pdfDocument' => str_replace('localhost', 'udraw-wordpress.ngrok.io', $uploaded_files[0]['url']),
                            'key' => $activationKey
                        );
                    }

                    $endpoint_action = ($ext == "eps") ? "EPS2PNG" : "PDF2SVG";
                    $output_file_ext = ($ext == "eps") ? ".png" : ".svg";

                    $udraw_convert_response = json_decode($uDrawUtil->get_web_contents(UDRAW_CONVERT_SERVER_URL . '/' . $endpoint_action, http_build_query($data)));

					if ($udraw_convert_response->isSuccess) {
                        for ($i = 0; $i < count($udraw_convert_response->data); $i++) {
                            array_push($url_array, UDRAW_CONVERT_SERVER_URL . $udraw_convert_response->data[$i]);
                        }                                    
                    } else {
                        echo "false"; wp_die();
                    } 
					
					for ($i = 0; $i < count($url_array); $i++) {
                        $new_file_name = $new_file . '_page_' . ($i + 1) . $output_file_ext;
						$this->downloadFile($url_array[$i], $_dir . '/'. $new_file_name);
                        array_push($new_file_array, (object)array(
                            'design_file' => $_url . '/'. $new_file_name
                        ));
                    }
                            
					
					// Once new SVG document is saved, we can remove the uploaded PDF document.
                    //unlink($uploaded_files[0]['file']);
                            
                    //Create a JSON file with all the arrays
                    $json_object = array(
                        'session_id' => $rand_id,
                        'pages' => $new_file_array
                    );
					$json_file_dir = $_dir . '/'. $rand_id . '.json';
					$json_file_url = $_url . '/'. $rand_id . '.json';
                    file_put_contents($json_file_dir, json_encode($json_object));
                            
                    // return back the new SVG file name.
                    $this->sendResponse(array('design_file' => $json_file_url));
                }
            }
        }
        
        public function udraw_svg_get_pngs ($design_file = '') {
            if (isset($_REQUEST['design_file'])) {
                $design_file = uDrawUtil::sanitize_variables($_REQUEST['design_file']);
            } else {
                $this->sendResponse(false);
            }

			$activationKey = uDraw::get_udraw_activation_key();
            $uDrawUtil = new uDrawUtil();
            $uDraw = new uDraw();
            $file_dir = str_replace(UDRAW_STORAGE_URL, UDRAW_STORAGE_DIR, $design_file);
            $contents = json_decode(file_get_contents($file_dir));
            $pages = $contents->pages;
            $session_id = $contents->session_id;
            $session_dir = UDRAW_STORAGE_DIR . '/_templates_/output/' . $session_id;
            $session_url = UDRAW_STORAGE_URL . '/_templates_/output/' . $session_id;
            for ($i = 0; $i < count($pages); $i++){ 
                $svg = $pages[$i]->design_file;
				$svgfile = simplexml_load_file($svg);
				$attributes = $svgfile->Attributes();
                $target_width = ceil(floatval((string)$attributes['width']));
                $target_height = ceil(floatval((string)$attributes['height']));

				$data = array(
                    'svgDocument' => str_replace('localhost', 'udraw-wordpress.ngrok.io', $svg),
					'target_width' => $target_width,
					'target_height' => $target_height,
					'key' => $activationKey
                );   

				$endpoint_action =  'SVG2PNG';
				$png = json_decode($uDrawUtil->get_web_contents(UDRAW_CONVERT_SERVER_URL . '/' . $endpoint_action, http_build_query($data)));
				$pngFile = UDRAW_CONVERT_SERVER_URL . $png->data;

				if ($png->isSuccess) {
					$png_name = $session_id . '_page_' . ($i + 1) . '.png';
					$this->downloadFile($pngFile, $session_dir . '/'. $png_name);
					$pages[$i]->preview_url = $session_url . '/'. $png_name;            
                } else {
                    echo "false"; wp_die();
                } 
            }           
            file_put_contents($session_dir . '/'. $session_id . '.json', json_encode($contents));
            $this->sendResponse(array('design_file' => $design_file));
        }
        
        public function make_uniqid_folder_id ($base_dir) {
            $rand_id = uniqid();
            if (!file_exists($base_dir . $rand_id)) {
                return $rand_id;
            } else {
                return $this->make_uniqid_folder_id($base_dir);
            }
        }
        
        public function clone_json_file ($_output_path, $design_file) {
            if (substr($design_file, strlen($design_file) - 4) === 'json' && strrpos($design_file, '_templates_') !== false) {
                //Copy all the svg files inside the json file, and rename them
                $replace = wp_make_link_relative(UDRAW_STORAGE_URL);
                if (strpos($design_file, UDRAW_STORAGE_URL) !== false) {
                    $replace = UDRAW_STORAGE_URL;
                }
                $json_file_dir = str_replace($replace, UDRAW_STORAGE_DIR, $design_file);
                $contents = json_decode(file_get_contents($json_file_dir));

                $SVG_templates_handler = new SVG_templates_handler();
                
                $output_replace = wp_make_link_relative(UDRAW_STORAGE_URL);
                $_output_path_dir = str_replace($output_replace, UDRAW_STORAGE_DIR, $_output_path);
                $_output_path_url = str_replace(UDRAW_STORAGE_DIR, UDRAW_STORAGE_URL, $_output_path_dir);
                
                $rand_id = $SVG_templates_handler->make_uniqid_folder_id($_output_path_dir);
                $_dir = $_output_path_dir . $rand_id;
                $_url = $_output_path_url . $rand_id;
                wp_mkdir_p($_dir);

                for ($i = 0; $i < count($contents->pages); $i++) {
                    $_svg = $contents->pages[$i]->design_file;
                    $_preview = $contents->pages[$i]->preview_url;
                    $_replace = wp_make_link_relative(UDRAW_STORAGE_URL);
                    if (strpos($_svg, UDRAW_STORAGE_URL) !== false) {
                        $_replace = UDRAW_STORAGE_URL;
                    }
                    $_svg_dir = str_replace($_replace, UDRAW_STORAGE_DIR, $_svg);
                    $svg_contents = file_get_contents($_svg_dir);
                    $new_file = $rand_id . '_page_' . $i;
                    file_put_contents($_dir . '/' . $new_file . '.svg', $svg_contents);
                    //Preview image
                    $_preview_dir = str_replace($_replace, UDRAW_STORAGE_DIR, $_preview);
                    $preview_contents = file_get_contents($_preview_dir);
                    file_put_contents($_dir . '/'. $new_file . '.png', $preview_contents);

                    $contents->pages[$i]->design_file = $_url . '/' . $new_file . '.svg';
                    $contents->pages[$i]->preview_url = $_url . '/' . $new_file . '.png';
                }
                $contents->session_id = $rand_id;
                file_put_contents($_dir . '/'. $rand_id . '.json', json_encode($contents));

                $design_file = $_url . '/'. $rand_id . '.json';
                
                return $design_file;
            }
            return $design_file;
        }
    }
}
?>