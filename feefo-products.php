<?php

class ss_feefo_products {

    function __construct() {
        add_action("wp_ajax_ss-feefo-products", array($this, 'func_ss_feefo_products'));
        add_action("wp_ajax_nopriv_ss-feefo-products", array($this, 'func_ss_feefo_products'));
    }

    function sh_the_content_by_id($post_id = 0, $more_link_text = null, $stripteaser = false) {
        global $post;
        $post = &get_post($post_id);
        setup_postdata($post, $more_link_text, $stripteaser);
        $content = get_the_content();
        wp_reset_postdata($post);
        return $content;
    }

    function get_color($id) {
        $color = get_post_meta($id, 'attribute_color', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_color', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_variations', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_varavil', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_var-luckies', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_var-monkey', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_var-peleg', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_pa_var-ototo', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_colour', true);
        if (!empty($color))
            return $color;

        $color = get_post_meta($id, 'attribute_colortype', true);
        if (!empty($color))
            return $color;
        $color = get_post_meta($id, 'attribute_product_colour', true);
        if (!empty($color))
            return $color;

        return '';
    }

    function get_woocommerce_product_list() {
        $full_product_list = array();
        $loop = new WP_Query(array('post_status' => 'publish', 'post_type' => array('product'), 'posts_per_page' => -1));

        while ($loop->have_posts()) : $loop->the_post();
            $theid = get_the_ID();
            $_pf = new WC_Product_Factory();
            $product = $_pf->get_product($theid);
            $brandname = '';
            $color = '';
            $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($theid), 'full');
            $thetitle = urldecode(get_the_title());
            $url = get_the_permalink($theid);
            $thecontent = urldecode(strip_tags(get_the_content($theid)));
            $brands = wp_get_post_terms($theid, 'product_brand', array("fields" => "all"));
            $brandname = '';
            if ($brands) {
                $brand = $brands[0];
                $brandname = $brand->name;
            }
            // its a variable product
            if ($product->get_type() == 'variable') {
                $available_variations = $product->get_available_variations();

                foreach ($available_variations as $prod_variation) {
                    $vid = $prod_variation['variation_id'];
                    $sku = $prod_variation['sku'];
                    $color = $this->get_color($vid);
                    $atts = $prod_variation['attributes'];
                    $image_link = $prod_variation['image_src'];
                    
                    if($image_link == '') {
                        if (count($thumbnail) > 0) {
                            $image_link = $thumbnail[0];
                        }
                    }
                    if ($prod_variation['variation_is_active'] == 1) {
                        // add product to array but don't add the parent of product variations
                        if (!empty($sku) && $sku != 'various' && $sku != 'various2' && $sku != 'VARIOUS' && $sku != 'variable')
                            $full_product_list[] = array($sku, $thetitle, $url, $thecontent, $brandname, $image_link, $color);
                    }
                }
            } else {
                $sku = get_post_meta($theid, '_sku', true);
                $image_link = '';
                if (count($thumbnail) > 0) {
                    $image_link = $thumbnail[0];
                }
                if (!empty($sku) && $sku != 'various' && $sku != 'various2' && $sku != 'VARIOUS' && $sku != 'variable')
                    $full_product_list[] = array($sku, $thetitle, $url, $thecontent, $brandname, $image_link, $color);
            }

        endwhile;
        wp_reset_query();
        // sort into alphabetical order, by title
        //sort($full_product_list);
        return $full_product_list;
    }

    function ss_feefo_export_data() {
        global $woocommerce, $wpdb;

        $products = $this->get_woocommerce_product_list();
        //  print_r($products);
        $columns = array(
            'search_code',
            'title',
            'url',
            'product_description',
            'merchant_identifier',
            'tags',
            'image_link'
        );

        $productdata = array();
        $merchant_identifier = get_option('merchant_identifier');
        foreach ($products as $product) {
            $data = array();
            $data['search_code'] = "<![CDATA[" . $product[0] . "]]>";
            $title = $product[1];
            $title = str_replace('&#038;', '&', $title);
            $title = str_replace('&#8217;', '\'', $title);
            $title = str_replace('&#8216;', '\'', $title);

            $data['title'] = "<![CDATA[" . $title . "]]>";
            $data['url'] = "<![CDATA[" . str_replace("https://", "http://", $product[2]) . "]]>";
            $replace_pattern = array('&#8220;', '&#8221;', '&#038;', '&#8216;', '&#8216;');

            $data['product_description'] = "<![CDATA[" . str_replace("https://", "http://", str_replace($replace_pattern, '""', $product[3])) . "]]>";
            $data['merchant_identifier'] = $merchant_identifier;
            $data['tags']  = '';
            if($product[4] != '') {
                $data['tags'] = 'brand=' . strtolower($product[4]);
            }
            if ($product[6] != '') {
                $data['tags'] .= ',color=' . strtolower($product[6]);
            }
            $data['image_link'] = "<![CDATA[" . str_replace("https://", "http://", $product[5]) . "]]>";
            $productdata[] = $data;
        }
        //print_r($productdata);
        $xml = '<items>';
        if (count($productdata) > 0) {
            foreach ($productdata as $order) {
                $xml .= '<item>';
                foreach ($columns as $column) {
                    $xml .= '<' . $column . '>' . $order[$column] . '</' . $column . '>' . "\n";
                }
                $xml .= "</item>\n";
            }
        }
        $xml .= '</items>';
        echo $xml;
    }

    function func_ss_feefo_products() {
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=ss_feefo_products_export.xml');
        header('Pragma: no-cache');
        header('Expires: 0');
        $this->ss_feefo_export_data();
        exit();
    }

}

$ss_products = new ss_feefo_products();
?>
