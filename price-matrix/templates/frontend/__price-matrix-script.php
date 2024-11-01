<script type="text/javascript">
    var json, bs, selectedDefault, selectedByUser, eFileName = "";
    var selectedSaved = [];
    var selectedOutput = '';
    var selectedPrice = '';
    var loadedFromCart = false;
    var loadedfromSavedDesign = false;
    var priceMatrixInit = true;
    var measurement_unit_label = '<?php echo htmlspecialchars($_measurement_unit, ENT_NOQUOTES); ?>';
    var priceMatrixObj;
</script>

<?php
global $wpdb;
$udrawSettings = new uDrawSettings();
$_udraw_settings = $udrawSettings->get_settings();
$uDrawUtil = new uDrawUtil();
    
$_cart_item_key = '';
// uDraw param for cart item key value
if (isset($_GET['cart_item_key'])) { $_cart_item_key = $uDrawUtil->sanitize_variables($_GET['cart_item_key']); }
// support for other plugin that uses diff. name than uDraw
if (isset($_GET['tm_cart_item_key'])) { $_cart_item_key = $uDrawUtil->sanitize_variables($_GET['tm_cart_item_key']); }

$is_upload_product_update = false;
$is_design_product_update = false;
$is_converted_pdf_product = false;

// Attempt to load in design from cart.
if( strlen($_cart_item_key) > 0 ) {
    //load from cart item
    $cart = $woocommerce->cart->get_cart();
    $cart_item = $cart[$_cart_item_key];
    if($cart_item) {
        if( isset($cart_item['udraw_data']) ) {
            if (strlen($cart_item['udraw_data']['udraw_options_uploaded_files']) > 0) {
                $is_upload_product_update = true;
                if ($cart_item['udraw_data']['udraw_options_converted_pdf']) {
                    $is_converted_pdf_product = true;
                }
            } else {
                if (isset($cart_item['udraw_data']['udraw_product_data']) && strlen($cart_item['udraw_data']['udraw_product_data']) > 0) {
                    $is_design_product_update = true;
                }
            }
            if( isset($cart_item['udraw_data']['udraw_price_matrix_selected_options_idx']) ) {
            ?>
                <script type="text/javascript">
                    selectedOutput = '<?php echo htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_selected_options'], ENT_NOQUOTES); ?>';
                    selectedPMOptions = '<?php echo htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_selected_options_object'], ENT_NOQUOTES); ?>';
                    var _idx = '<?php echo htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_selected_options_idx'], ENT_NOQUOTES); ?>';
                    var projected_pricing = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_projected_pricing'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_projected_pricing'], ENT_NOQUOTES) : ''; ?>';
                    var selected_quantity = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_qty'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_qty'], ENT_NOQUOTES) : 1; ?>';
                    var selected_pages = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_records'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_records'], ENT_NOQUOTES) : 1; ?>';
                    var selected_width = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_width'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_width'], ENT_NOQUOTES) : 1; ?>';
                    var selected_height = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_height'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_height'], ENT_NOQUOTES) : 1; ?>';
                    var selected_length = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_length'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_length'], ENT_NOQUOTES) : 1; ?>';
                    var selected_contingency = '<?php echo (isset($cart_item['udraw_data']['udraw_price_matrix_contingency'])) ? htmlspecialchars($cart_item['udraw_data']['udraw_price_matrix_contingency'], ENT_NOQUOTES) : 1; ?>';
                    if (typeof JSON.parse(selectedOutput).Width == "object") { selected_width = JSON.parse(selectedOutput).Width[0]; }
                    if (typeof JSON.parse(selectedOutput).Height == "object") { selected_height = JSON.parse(selectedOutput).Height[0]; }
                    if (typeof JSON.parse(selectedOutput).Length == "object") { selected_length = JSON.parse(selectedOutput).Length[0]; }
                    if (typeof JSON.parse(selectedOutput).Contingency == "object") { selected_contingency = JSON.parse(selectedOutput).Contingency[0]; }
                    selectedSaved = _idx.split(',');
                    selectedDefault = _idx.split(',');
                    selectedByUser = _idx.split(',');
                    loadedFromCart = true;
                </script>
            <?php
            }
        }
    }
} else if (isset($_GET['udraw_access_key'])) {
    //Or if it has an access key (saved customer design)
    $access_key = $uDrawUtil->sanitize_variables($_GET['udraw_access_key']);
    $saved_designs_table = $uDrawUtil->sanitize_variables($_udraw_settings['udraw_db_udraw_customer_designs']);
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $saved_designs_table WHERE access_key=%s", $access_key), ARRAY_A);
    if ($results[0]['price_matrix_options'] !== NULL) {
        $saved_values = json_decode(stripslashes($results[0]['price_matrix_options']));
        ?>
        <script>
            selectedOutput = '<?php echo stripcslashes(htmlspecialchars_decode(uDrawUtil::esc_js($saved_values->selectedOutput))); ?>';
            selectedPMOptions = '<?php echo stripcslashes(htmlspecialchars_decode(uDrawUtil::esc_js($saved_values->selectedOptions))); ?>';
            var _idx = '<?php echo stripcslashes(htmlspecialchars_decode(uDrawUtil::esc_js(implode(',', $saved_values->options)))); ?>';
            selectedSaved = _idx.split(',');
            selectedDefault = _idx.split(',');
            selectedByUser = _idx.split(',');
            var selected_quantity = '<?php echo (isset($saved_values->quantity)) ? htmlspecialchars($saved_values->quantity, ENT_NOQUOTES) : 1; ?>';
            var selected_width = '<?php echo (isset($saved_values->dimensions->width)) ? htmlspecialchars($saved_values->dimensions->width, ENT_NOQUOTES) : 1; ?>';
            var selected_height = '<?php echo (isset($saved_values->dimensions->height)) ? htmlspecialchars($saved_values->dimensions->height, ENT_NOQUOTES) : 1; ?>';
            var selected_length = '<?php echo (isset($saved_values->dimensions->length)) ? htmlspecialchars($saved_values->dimensions->length, ENT_NOQUOTES) : 1; ?>';
            loadedfromSavedDesign = true;
        </script>
        <?php
    }
}
?>

<script>
    var pm_original_preview_image = '';
    var pm_upload_preview_image = '';
    
    function udraw_post_file_upload(uploadedFiles) {
        var response = false;
        var measurement_unit_label = '<?php echo htmlspecialchars($_measurement_unit, ENT_NOQUOTES); ?>';
        //Check if document size is defined in Price Matrix Options meta - This will handle upload for size check for preset sizes. 
        //Use Keywords "FinalDocWidth and FinalDocHeight" to define [Not case sensitive].
        var finalDocWidth = 0; var finalDocHeight = 0;
        if (selectedPMOptions) {
            if (selectedPMOptions.length > 0) {
                for (var i = 0; i < selectedPMOptions.length; i++) {
                    if (selectedPMOptions[i].meta) {
                        if (selectedPMOptions[i].meta.length > 0) {
                            for (var j = 0; j < selectedPMOptions[i].meta.length; j++) {
                                if (selectedPMOptions[i].meta[j].key === 'finaldocwidth') { finalDocWidth = parseFloat(selectedPMOptions[i].meta[j].value); }
                                if (selectedPMOptions[i].meta[j].key === 'finaldocheight') { finalDocHeight = parseFloat(selectedPMOptions[i].meta[j].value); }
                            }
                        }
                    }
                }
            }
        }

        jQuery('div.upload_err').remove();
        var key = '<?php echo base64_encode(uDraw::get_udraw_activation_key() .'%%'. str_replace('.', '-', $_SERVER['HTTP_HOST'])); ?>';
        if (bs.ShowSize || (canvasWidth > 0 && canvasHeight > 0) || (finalDocWidth > 0 && finalDocHeight > 0)) {
            if (measurement_unit_label == 'ft' || measurement_unit_label == 'in' || measurement_unit_label == 'mm') {
                var _extension = uploadedFiles[0].url.toLowerCase();
				if (_extension.endsWith('pdf')) {
                    response = true;
					var pdfInfo = '<?php echo uDrawUtil::esc_string(UDRAW_CONVERT_SERVER_URL) ?>/PDFInfo';
                    jQuery("<span>Please Wait, Verifying File...<span><i class='fa fa-2x fa-spinner fa-pulse'></i>").insertBefore('.udraw-progress-bar');
                    if (bs.ShowSize || (finalDocWidth > 0 && finalDocHeight > 0)) {
                        jQuery.ajax({
                            method: "POST",
                            crossdomain: true,
							url: pdfInfo,
							data: {
								pdfDocument: uploadedFiles[0].url,
                                key: '<?php echo uDraw::get_udraw_activation_key() ?>'
							},
                            success: function (response) {
								if(response.isSuccess) {
									var previewURL = '<?php echo uDrawUtil::esc_string(UDRAW_CONVERT_SERVER_URL) ?>' + response.data.previewURL;
									var result = response.data;
									__validate_pm_area_image(uploadedFiles[0].url, parseInt(result.pageWidth), parseInt(result.pageHeight), finalDocWidth, finalDocHeight, measurement_unit_label, function (is_valid) {
										if (is_valid) {
											pm_upload_preview_image = previewURL;
											jQuery('input[name="udraw_options_uploaded_files_preview"]').val(previewURL);
										} else {
											jQuery('#udraw-options-submit-form-btn').hide();
										}
									});
								}
                            }
                        });
                    }
                    if (canvasWidth > 0 && canvasHeight > 0) {
                        var targetWidth = canvasWidth;
                        var targetHeight = canvasHeight;

                        if (measurement_unit_label == 'ft') {
                            targetWidth = targetWidth * 12;
                            targetHeight = targetHeight * 12;
                        }

                        if (measurement_unit_label == 'mm') { //Convert to inches
                            targetWidth = targetWidth / 25.4;
                            targetHeight = targetHeight / 25.4;
                        }

                        jQuery.ajax({
                            method: "POST",
                            crossdomain: true,
                            url: pdfInfo,
							data: {
								pdfDocument: uploadedFiles[0].url,
                                key: '<?php echo uDraw::get_udraw_activation_key() ?>'
							},
                            success: function (response) {
								if(response.isSuccess) {
									var previewURL = '<?php echo uDrawUtil::esc_string(UDRAW_CONVERT_SERVER_URL) ?>' + response.data.previewURL;
									var result = response.data;
									jQuery('.price-matrix-upload-error').remove();
									if (typeof result.imageInfo[0] !== 'undefined') {
										__validate_next_image(result.imageInfo, 0, targetWidth, targetHeight, targetDpi, function(){
											jQuery('#udraw-options-file-upload-progress span').empty();
											var _html = "<label style=\"font-size:15px; line-height: 2\"><i class=\"fa fa-check-circle\" style=\"color: green; transform: scale(1.5,1.5); margin-right: 15px;\"></i><strong>Uploaded</strong>:&nbsp;" + uploadedFiles[0].name + "</label>";
											_html += "<a href=\"#\" onclick=\"javascript: removeUploadedFile('"+ uploadedFiles[0].name +"'); return false; \">&nbsp;<i class=\"fa fa-trash\" style=\"color: red; transform: scale(1.5,1.5); margin-left:10px\"></i></a><br/>";
											jQuery('.udraw-uploaded-files-list').append(_html);
											jQuery('#udraw-options-submit-form-btn').show();
										});
									}
								}
                            }
                        });
                    }
                } else if (_extension.endsWith('png') || _extension.endsWith('jpg') || _extension.endsWith('jpeg') || _extension.endsWith('jpe') ||
                    _extension.endsWith('gif') || _extension.endsWith('tiff') || _extension.endsWith('tif')) {
                    response = true;
                    var img = new Image();
                    img.onload = function () {
                        if (bs.ShowSize || (finalDocWidth > 0 && finalDocHeight > 0)) {
                            __validate_pm_area_image(uploadedFiles[0].url, this.width, this.height, finalDocWidth, finalDocHeight, measurement_unit_label);
                        } else {
                            var targetWidth = canvasWidth;
                            var targetHeight = canvasHeight;

                            if (measurement_unit_label == 'ft') {
                                targetWidth = targetWidth * 12;
                                targetHeight = targetHeight * 12;
                            } 

                            __validate_image_size(this.width, this.height, targetWidth, targetHeight, targetDpi, function () {
                                jQuery('#udraw-options-file-upload-progress span').empty();
                                var _html = "<label style=\"font-size:15px; line-height: 2\"><i class=\"fa fa-check-circle\" style=\"color: green; transform: scale(1.5,1.5); margin-right: 15px;\"></i><strong>Uploaded</strong>:&nbsp;" + uploadedFiles[0].name + "</label>";
                                _html += "<a href=\"#\" onclick=\"javascript: removeUploadedFile('" + uploadedFiles[0].name + "'); return false; \">&nbsp;<i class=\"fa fa-trash\" style=\"color: red; transform: scale(1.5,1.5); margin-left:10px\"></i></a><br/>";
                                jQuery('.udraw-uploaded-files-list').append(_html);
                                jQuery('#udraw-options-submit-form-btn').show();
                            });
                        }
                    }
                    jQuery("<span>Please Wait, Verifying File...<span><i class='fa fa-2x fa-spinner fa-pulse'></i>").insertBefore('.udraw-progress-bar');
                    img.src = uploadedFiles[0].url;

                }
            }
        }

        return response;
    }
</script>
