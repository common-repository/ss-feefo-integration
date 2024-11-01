<?php
/*
  Plugin Name: SS Feefo Integration
  Plugin URI: http://www.singsons.com/plugins/feefo
  Description: Plugin for Feefo Integration.
  Author: Virendra Singh
  Version: 1.0
  Author URI: http://www.singsons.com/
 */

class ss_feefo {

    private $errors,$notices;
    private $log = array();
    
    function __construct() {
        $this->log['error'] = array();
        $this->log['notice'] = array();
    }
    
    function form() {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->post();
        }
        // form HTML {{{
        //Get the values
        $merchant_identifier = get_option('merchant_identifier');
        $this->print_messages();
        ?>

        <div class="wrap">
            <h2>Feefo Admin</h2>
            <b>Using this screen you can export the data that is required by  <br> <a href="http://www.feefo.com/"> Feefo <div style="height: 54px;width: 149px;background-image: url('https://www.feefo.com/web/images/web/normal-sprite-sec4d641097.png');background-repeat: no-repeat;background-position: -784px 0;"></div></a></b>
            <form id="feefo-export-form" method="post">
                <div id="poststuff" class="info export-products-info">
                    <div class="postbox">
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <td><label for="startdate"><?php _e('Merchant Identifier', 'ss-feefo'); ?></label> (*)</td>
                                    <td>
                                        <input required="" type="text" id="merchant_identifier" value="<?php echo $merchant_identifier; ?>" name="merchant_identifier" class="merchant-identifier" />
                                    </td>
                                    <td>You can get the merchant identifier from your Feefo account.</td>
                                </tr>
                            </table>
                        </div>
                        <p class="submit">
                            <input id="export-submit" type="submit" value="<?php _e('Save Changes', 'ss-feefo'); ?>" class="button button-primary btn btn-primary" />
                        </p>
                    </div>
                </div>
            </form> <br>

            <form id="feefo-export-form" method="post">
                <div id="poststuff" class="info export-products-info">
                    <div class="postbox">
                        <div class="inside">
                            <?php _e('Export Products button creates a XML file which can be used to import the products in Feefo account.', 'ss-feefo'); ?>
                        </div>
                        <p class="submit">
                            <input type="hidden" name="action" value="ss-feefo-export-products"/>
                            <input id="export-submit" type="submit" value="<?php _e('Export Products', 'ss-feefo'); ?> &raquo;" class="button button-primary" />
                        </p> 
                    </div>
                </div>
            </form> <br>
            <form id="feefo-export-form" method="post">
                <div id="poststuff" class="info export-orders-info">
                    <div class="postbox">
                        <h3 class="hndle"><?php _e('Date range for orders', 'ss-feefo'); ?></h3>
                        <div class="inside">
                            <?php _e('If you don\'t select any dates all orders will be exported. Export Sales button creates a XML file which can be used to import the sales in Feefo account.', 'ss-feefo'); ?>
                            <table class="form-table">
                                <tr>
                                    <td style="width: 30%;">
                                        <label for="startdate"><?php _e('Start date', 'ss-feefo'); ?></label><br />
                                        <input type="text" id="datepicker-field-start" value="<?php echo $startdate; ?>" name="startdate" readonly="true" class="export-date" />
                                    </td>
                                    <td>
                                        <label for="enddate"><?php _e('End date', 'ss-feefo'); ?></label><br />
                                        <input type="text" id="datepicker-field-end" value="<?php echo $enddate; ?>" name="enddate" readonly="true" class="export-date" />
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p class="submit">
                            <input type="hidden" name="action" value="ss-feefo-export-orders"/>
                            <input id="export-submit" type="submit" value="<?php _e('Export Orders', 'ss-feefo'); ?> &raquo;" class="button button-primary" />
                        </p>
                    </div>
                </div>

            </form>    

        </div><!-- end wrap -->
        <?php
        // end form HTML }}}
    }

    function post() {
        
        if (isset($_POST['merchant_identifier'])) {
            $merchant_identifier = $_POST['merchant_identifier'];
            update_option('merchant_identifier', $merchant_identifier);
        }
        if(isset($_POST['action']) && ($_POST['action'] == 'ss-feefo-export-products' || $_POST['action'] == 'ss-feefo-export-orders')){
            $merchant_identifier = get_option('merchant_identifier');
            if(empty($merchant_identifier)){
                $this->log['error'][] = "Please add the merchant Identifier first to export the data.";
            }
        }
    }
    
    function print_messages() {
        if (!empty($this->log)) {

            // messages HTML {{{
            ?>

            <div class="wrap">
                <?php if (!empty($this->log['error'])): ?>

                    <div class="error">

                        <?php foreach ($this->log['error'] as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <?php if (!empty($this->log['notice'])): ?>

                    <div class="updated fade">

                        <?php foreach ($this->log['notice'] as $notice): ?>
                            <p><?php echo $notice; ?></p>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>
            </div><!-- end wrap -->

            <?php
            // end messages HTML }}}

            $this->log = array();
        }
    }

}

function ss_feefo_enqueue_scripts() {
    global $woocommerce, $wp_scripts;

    // Datepicker
    wp_enqueue_script('jquery-ui-datepicker');
    $jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
    wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css');

    // Register and include all needed scripts
    wp_enqueue_script('sku-export-js', plugins_url('/js/ss_feefo.js', __FILE__));
    wp_enqueue_style('sku-export-css', plugins_url('/css/ss_feefo.css', __FILE__));
}

function ss_feefo_admin_menu() {
    $plugin = new ss_feefo();
    $page = add_menu_page(__('Feefo Admin', 'ss-feefo'), __('Feefo Admin', 'ss-feefo'), 'manage_options', 'feefo-sales-export', array($plugin, 'form'));
    add_action('admin_print_styles-' . $page, 'ss_feefo_enqueue_scripts');
}

add_action('admin_menu', 'ss_feefo_admin_menu');

function ss_feefo_admin_init() {
    if (isset($_POST['action'])) {
        $merchant_identifier = get_option('merchant_identifier');
        if (!empty($merchant_identifier)) {
            if ($_POST['action'] == 'ss-feefo-export-products') {
                $ss_products = new ss_feefo_products();
                $ss_products->func_ss_feefo_products();
            }
        }
        if (!empty($merchant_identifier)) {
            if ($_POST['action'] == 'ss-feefo-export-orders') {
                $ss_products = new ss_feefo_sales();
                $ss_products->func_ss_feefo_sales();
            }
        }
    }
}

add_action('admin_init', 'ss_feefo_admin_init');



include_once 'feefo-sales.php';
include_once 'feefo-products.php';
?>