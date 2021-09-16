<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

$com_info = [
             'menu_name'   => lang('Roskassa', 'payment_method_roskassa'), // Menu name
             'description' => lang('Метод оплаты Roskassa', 'payment_method_roskassa'), // Module Description
             'admin_type'  => 'window', // Open admin class in new window or not. Possible values window/inside
             'window_type' => 'xhr', // Load method. Possible values xhr/iframe
             'w'           => 600, // Window width
             'h'           => 550, // Window height
             'version'     => '1.0', // Module version
             'author'      => 'cmsmodulsdever@gmail.com', // Author info
             'icon_class'  => 'icon-barcode',
            ];

/* End of file module_info.php */