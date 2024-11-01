<?php

if (!class_exists('uDrawUtil')) {
    
    class uDrawUtil extends uDrawAjaxBase {
        function __construct() {
        }
        
        function init_actions() {
            add_action( 'wp_ajax_udraw_get_customers', array(&$this, 'get_customers') );
        }
        
        public function get_customers ($page = 1, $per_page = 500) {
            if (isset($_REQUEST['page'])) {
                $page = $this->sanitize_variables($_REQUEST['page']);
            }
            if (isset($_REQUEST['per_page'])) {
                $per_page = $this->sanitize_variables($_REQUEST['per_page']);
            }
            $return_array = array();
            $get_next_page = true;
            $get_users_args = array(
                'role__in'  => [ 'customer', 'subscriber'],
                'number'    => $per_page,
                'paged'     => $page
            );
            $customers = get_users( $get_users_args );
            foreach($customers as $customer) {
                $return_obj = array(
                    'ID' => $customer->ID,
                    'display_name' => $customer->display_name,
                    'email' => $customer->user_email
                );
                array_push($return_array, $return_obj);  
            }
            if (count($return_array) < $per_page) {
                $get_next_page = false;
            }
            $return = array(
                'customers' => $return_array,
                'get_next_page' => $get_next_page
            );
            $this->sendResponse($return);
        }

        function get_web_contents($url, $postData="", $headers=array()) {
            if (substr( $url, 0, 4 ) != "http") { return ""; }

            $timeout = ini_get( 'max_execution_time' );
			$args = array(
				'body' => $postData,
                'timeout' => $timeout,
                'headers' => $headers,
                'sslverify' => true
            );
            
            if ($postData !== "") {
                $request = wp_remote_post( $url , $args );
            } else {
                $request = wp_remote_get( $url , $args );
            }
            $response = wp_remote_retrieve_body( $request );
            return $response;
        }

        public function url_exists($url): bool
        {
            if (substr( $url, 0, 4 ) != "http") { return false; }

            $timeout = ini_get( 'max_execution_time' );
            $args = array(
                'timeout' => $timeout,
                'sslverify' => true
            );

            $response = wp_remote_get( $url , $args);
            $response_code = wp_remote_retrieve_response_code( $response );

            return ($response_code == '200');  
        }

        public function is_xml_valid($xmlString) {
            $xmlDoc = @simplexml_load_string($xmlString);
            if ($xmlDoc) {
                return true;
            } else {
                return false;
            }
        }
        
        function is_dir_empty($dir) {
            if (!is_readable($dir)) return NULL; 
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    return FALSE;
                }
            }
            return TRUE;
        }
        
        function download_file($url, $path) {
            //Remove any spaces that may be present
            $url = preg_replace('/\s/', '%20', $url);
            $timeout = ini_get( 'max_execution_time' );
            $args = array (
                'filename' => $path,
                'stream' => true,
                'timeout' => $timeout
            );

            $response = wp_remote_get( $url, $args );
            if ( is_array( $response ) && ! is_wp_error( $response ) ) {
                return true;
            } else {
                return false;
            }
        }
        
        function startsWith($haystack, $needle) {
            // search backwards starting from haystack length characters from the end
            return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
        }
        
        function download_file_with_pointer($url, $fp) {
            if (function_exists('curl_version')) {
                $file = fopen('php://temp', 'w+');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_FILE, $file);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSLVERSION, 6);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                if(curl_exec($ch) === false) {
                    error_log('Curl error: ' . curl_error($ch));
                }
                
                curl_close($ch);

                rewind($file);
            } else {
                $file = fopen ($url, "rb");
            }
            
            if ($file) {
                while(!feof($file)) {
                    fwrite($fp, fread($file, 1024 * 8 ), 1024 * 8 );
                }
                fclose($file);
            }

            return $fp;
        }
        
        function empty_target_folder ($folder_dir) {
            $files = glob($folder_dir.'/*'); // get all file names
            foreach($files as $file){ // iterate files
                if(is_file($file))
                error_log('FILE WAS DELETED - EMPTY TARGET FOLDER');
                error_log(print_r($file, true));
                unlink($file); // delete file
            }
        }
        
        function create_zip_file ($data_array = '', $destination = '', $overwrite = false) {
            //$data_array will contain the array of objects containing the url and intented name of the file to be zipped
            if(file_exists($destination) && !$overwrite) { return false; }
            //Create array to hold pdf files in
            $file_array = array();
            for ($i = 0; $i < count($data_array); $i++) {
                //if current entry in data array has pdf entry and the pdf exists, push into file array
                $data = (object)$data_array[$i];
                if (isset($data->directory) && file_exists($data->directory)) {
                    array_push($file_array, $data);
                }
            }
            if(count($file_array) > 0) {
                //create the archive
                touch($destination);
                $zip = new ZipArchive();
                if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                    return false;
                }
                //add the files
                foreach($file_array as $file) {
                    $zip->addFile($file->directory,$file->name);
                }
                //close the zip -- done!
                $zip->close();
                
                //check to make sure the file exists
                return file_exists($destination);
            } else {
                return false;
            }
        }

        public static function sanitize_variables ($var_to_sanitize) {
            if (is_array($var_to_sanitize)) {
                $value = array_map('sanitize_text_field', $var_to_sanitize);
            } else {
                $var_to_sanitize = sanitize_text_field($var_to_sanitize);
            } 
            return $var_to_sanitize;
        }

        public static function esc_js ($value) {
            return esc_js($value);
        }

        public static function esc_string ($value) {
            return esc_attr($value);
        }

        public static function esc_url ($value) {
            return esc_url($value);
        }
    }
   
}

?>