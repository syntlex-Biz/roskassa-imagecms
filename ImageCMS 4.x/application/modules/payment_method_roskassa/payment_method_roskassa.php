<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */



class Payment_method_roskassa extends MY_Controller
{

    public $paymentMethod;
    public $moduleName = 'payment_method_roskassa';

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('payment_method_roskassa');
    }

    public function index() {
        lang('roskassa', 'payment_method_roskassa');
    }

    /**
     * Вытягивает данные способа оплаты
     * @param str $key
     * @return array
     */
    private function getPaymentSettings($key) {
        $ci = &get_instance();
        $value = $ci->db->where('name', $key)
        ->get('shop_settings');
        if ($value) {
            $value = $value->row()->value;
        } else {
            show_error($ci->db->_error_message());
        }
        return unserialize($value);
    }

    /**
     * Вызывается при редактировании способов оплатыв админке
     * @param integer $id ид метода оплаты
     * @param string $payName название payment_method_roskassa
     * @return string
     */
    public function getAdminForm($id, $payName = null) {
        if (!$this->dx_auth->is_admin()) {
            redirect('/');
            exit;
        }

        $nameMethod = $payName ? $payName : $this->paymentMethod->getPaymentSystemName();
        $key = $id . '_' . $nameMethod;
        $data = $this->getPaymentSettings($key);

        $codeTpl = \CMSFactory\assetManager::create()
        ->setData('data', $data)
        ->fetchTemplate('adminForm');

        return $codeTpl;
    }

    //Конвертация в другую валюту

    public function convert($price, $currencyId) {
        if ($currencyId == \Currency\Currency::create()->getMainCurrency()->getId()) {
            $return['price'] = $price;
            $return['code'] = \Currency\Currency::create()->getMainCurrency()->getCode();
            return $return;
        } else {
            $return['price'] = \Currency\Currency::create()->convert($price, $currencyId);
            $return['code'] = \Currency\Currency::create()->getCodeById($currencyId);
            return $return;
        }
    }

    //Наценка

    public function markup($price, $percent) {
        $price = (float) $price;
        $percent = (float) $percent;
        $factor = $percent / 100;
        $residue = $price * $factor;
        return $price + $residue;
    }

    /**
     * Формирование кнопки оплаты
     * @param obj $param Данные о заказе
     * @return str
     */
    public function getForm($param) {
        $payment_method_id = $param->getPaymentMethod();
        $key = $payment_method_id . '_' . $this->moduleName;
        $paySettings = $this->getPaymentSettings($key);

        $mrh_login = $paySettings['login'];
        $mrh_pass = $paySettings['password'];
        $mrh_test = $paySettings['test'];
        $inv_id = $param->getId();

        //        // номер заказа
        $out_summ = $price = $param->getDeliveryPrice() ? ($param->getTotalPrice() + $param->getDeliveryPrice()) : $param->getTotalPrice();

        $products = $param->getOrderProducts();
        $data = '';
        $i = 0;

        foreach ($products as $key => $value) {

            $arrPrice = $this->convert($value->price, $paySettings['merchant_currency']);
            $price = $arrPrice['price'];

            $data .= '<input type="hidden" name="receipt[items]['.$i.'][name]" value="'.$value->product_name.'">';
            $data .= '<input type="hidden" name="receipt[items]['.$i.'][count]" value="'.$value->quantity.'">';
            $data .= '<input type="hidden" name="receipt[items]['.$i.'][price]" value="'.$price.'">';

            $i++;

        }

        if ($param->delivery_price > 1) {
            $arrPrice = $this->convert($param->delivery_price, $paySettings['merchant_currency']);
            $price = $arrPrice['price'];

            $data .= '<input type="hidden" name="receipt[items]['.$i.'][name]" value="Доставка">';
            $data .= '<input type="hidden" name="receipt[items]['.$i.'][count]" value="1">';
            $data .= '<input type="hidden" name="receipt[items]['.$i.'][price]" value="'.$price.'">';
        }

        if ($paySettings['merchant_currency']) {
            $arrPriceCode = $this->convert($out_summ, $paySettings['merchant_currency']);
            $out_summ = $arrPriceCode['price'];
            $code = $arrPriceCode['code'];
        }

        if ($paySettings['merchant_markup']) {
            $out_summ = $this->markup($price, $paySettings['merchant_markup']);
        }

        //        // формирование подписи

        $sign_arr = array(
            'shop_id' => $mrh_login,
            'amount' => $out_summ,
            'currency' => $code,
            'order_id' => $inv_id,
        );

        $test = '';
        if ($mrh_test == 1) {
            $sign_arr['test'] = 1;
            $test = '<input type="hidden" name="test" value="1" />';
        }

        ksort($sign_arr);
        $str = http_build_query($sign_arr);
        $sign = md5($str . $mrh_pass);

        return '<form method="GET" action="//pay.roskassa.net">
        <input type="hidden" name="shop_id" value="' . $mrh_login . '" />
        '.$data.'
        <input type="hidden" name="amount" value="' . $out_summ . '" />
        <input type="hidden" name="order_id" value="' . $inv_id . '" />
        <input type="hidden" name="currency" value="' . $code . '" />
        '.$test.'
        <input type="hidden" name="sign" value="' . $sign . '" />
        <div class="btn-cart btn-cart-p">
        <input type="submit" value="Оплатить" />
        </div>
        </form>';
    }

    /**
     * Метод куда система шлет статус заказа
     */
    public function callback() {
        if ($_REQUEST) {
            $this->checkPaid($_REQUEST);
        }
    }

    /**
     * Метов обработке статуса заказа
     * @param array $param пост от метода callback
     */
    private function checkPaid($param) {
        $ci = &get_instance();

        $order_id = $param['order_id'];
        $userOrder = $ci->db->where('id', $order_id)
        ->get('shop_orders');
        if ($userOrder) {
            $userOrder = $userOrder->row();
        } else {
            show_error($ci->db->_error_message());
        }

        $key = $userOrder->payment_method . '_' . $this->moduleName;
        $paySettings = $this->getPaymentSettings($key);
        $mrh_pass = $paySettings['password'];
        $out_summ = $_REQUEST['amount'];
        $m_id = $_REQUEST['shop_id'];
        $inv_id = $_REQUEST['order_id'];
        $sign = $_REQUEST['sign'];

        $data = $_POST;
        unset($data['sign']);
        ksort($data);
        $str = http_build_query($data);
        $my_sign = md5($str . $mrh_pass);

        // Check sign
        if ($my_sign != $sign) {
            echo 'NO SIGN';
            return false;
        }

        // Set order paid
        $this->successPaid($order_id, $userOrder);
        echo 'YES';
        return true;
    }

    /**
     * Save settings
     *
     * @return bool|string
     */
    public function saveSettings(SPaymentMethods $paymentMethod) {
        $saveKey = $paymentMethod->getId() . '_' . $this->moduleName;
        \ShopCore::app()->SSettings->set($saveKey, serialize($_REQUEST['payment_method_roskassa']));

        return true;
    }

    /**
     * Переводит статус заказа в оплачено, и прибавляет пользователю
     * оплеченную сумму к акаунту
     * @param integer $order_id ид заказа который обрабатывается
     * @param obj $userOrder данные заказа
     */
    private function successPaid($order_id, $userOrder) {
        $ci = &get_instance();
        $amount = $ci->db->select('amout')
        ->get_where('users', ['id' => $userOrder->user_id]);

        if ($amount) {
            $amount = $amount->row()->amout;
        } else {
            show_error($ci->db->_error_message());
        }
        $amount += $userOrder->total_price;

        $result = $ci->db->where('id', $order_id)
        ->update('shop_orders', ['paid' => '1', 'date_updated' => time()]);
        if (!$result) {
            show_error($ci->db->_error_message());
        }

        \CMSFactory\Events::create()->registerEvent(['system' => __CLASS__, 'order_id' => $order_id], 'PaimentSystem:successPaid');
        \CMSFactory\Events::runFactory();

        $result = $ci->db
        ->where('id', $userOrder->user_id)
        ->limit(1)
        ->update(
            'users',
            [
               'amout' => str_replace(',', '.', $amount),
           ]
       );
        if (!$result) {
            show_error($ci->db->_error_message());
        }
    }

    public function autoload() {

    }

    public function _install() {
        $ci = &get_instance();

        $result = $ci->db->where('name', $this->moduleName)
        ->update('components', ['enabled' => '1']);
        if (!$result) {
            show_error($ci->db->_error_message());
        }
    }

    public function _deinstall() {
        $ci = &get_instance();

        $result = $ci->db->where('payment_system_name', $this->moduleName)
        ->update(
            'shop_payment_methods',
            [
               'active'              => '0',
               'payment_system_name' => '0',
           ]
       );
        if (!$result) {
            show_error($ci->db->_error_message());
        }

        $result = $ci->db->like('name', $this->moduleName)
        ->delete('shop_settings');
        if (!$result) {
            show_error($ci->db->_error_message());
        }
    }

}

/* End of file sample_module.php */