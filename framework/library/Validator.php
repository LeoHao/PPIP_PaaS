<?php
/**
 * @Filename         : Validator.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2020-12-30 16:23
 * @Description      : 
**/

class Validator {

    /**
     * 检查Email格式是否有效。
     *
     * @param    string $email
     * @return    boolean
     */
    public static function is_email($email) {

        if (!$email) {
            return false;
        }

        if (!preg_match("/^[_\\.0-9a-z-]+@([0-9a-z][0-9a-z-]*\\.)+[a-z0-9]{2,}$/i",$email)) {
            return false;
        }

        list($handle, $domain) = explode('@', $email);

        if (!self::is_len_in_range($handle, 1, 50, Util_String::STRING_TYPE_CHARACTER)) {
            return false;
        }

        if (!self::is_len_in_range($domain, 1, 49, Util_String::STRING_TYPE_CHARACTER)) {
            return false;
        }

        return true;
    }

    /**
     * 检查QQ格式是否有效
     *
     * @param  string $qq
     * @return boolean
     */
    public static function is_qq($qq) {

        return $qq && preg_match("/^\d{5,10}$/", $qq);
    }

    /**
     * 检查Realname是否有效
     *
     * @param string $realname
     * @return boolean
     */
    public static function is_realname($realname) {

        return preg_match("/^[0-9a-zA-Z_\x7f-\xff]+$/", $realname);
    }

    /**
     * 检查密码格式是否有效。
     *  默认规则：长度为6-16，并且在可见的半角字符内(包含空格)
     *
     * @param  string  $password  密码
     * @param  integer $min_length 最小长度
     * @param  integer $max_length 最大长度
     * @return boolean
     */
    public static function is_password($password, $min_length = 6, $max_length = 30) {

        $string_length = strlen($password);

        return $string_length >= $min_length && $string_length <= $max_length && preg_match('/^[\x21-\x7e ]+$/', $password);
    }

    /**
     * 检查用户名是否有效。
     *
     * @param string $username
     * @return boolean
     */
    public static function is_username($username) {

        if (preg_match("/^[0-9a-zA-Z_\.-]+$/", $username)) {
            if (strlen($username) <= 50) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否为整数。
     *
     * @param int $int
     * @return boolean
     */
    public static function is_int($int) {

        return preg_match('/^\d+$/', $int);
    }

    /**
     * 检查图片文件名
     *
     * @param string $filename
     * @return boolean
     */
    public static function is_image($filename) {

        switch(strtolower(substr(strrchr($filename, '.'), 1))) {
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'bmp':
            case 'heic':
                return true;
            default:
                return false;
        }
    }

    /**
     * 检查电话号码格式是否正确
     *
     * @param string $str
     * @return boolean
     */
    public static function is_phone($str) {

        return preg_match("/^(((0\d{2,3}-)?\d{7,8}(-\d{1,5})?))$/", $str);
    }

    /**
     * 手机号码是否有效
     *
     * @param string $mobile
     * @return boolean
     */
    public static function is_mobile($mobile) {

        return preg_match("/^(1\d{10})$/", $mobile);
    }

    /**
     * 电话号码是否为400电话
     *
     * @param string $str
     * @return boolean
     */
    public static function is400_phone($str) {

        return preg_match("/^400\d{7}$/", $str);
    }

    /**
     * 电话号码是否为800电话
     *
     * @param string $str
     * @return boolean
     */
    public static function is800_phone($str) {

        return preg_match("/^800\d{7}$/", $str);
    }

    /**
     * 检查住址
     *
     * @param string $str
     * @return boolean
     */
    public static function is_addr($str) {

        $string_length = strlen($str);

        return $string_length > 7 && htmlspecialchars($str) == $str;
    }

    /**
     * 检查邮政编码格式是否正确
     *
     * @param string $str
     * @return boolean
     */
    public static function is_postalcode($str) {

        return preg_match("/^\d{6}$/", $str);
    }

    /**
     * 检查一个数值是否在两个数值之间
     *
     * @param mixed $x
     * @param mixed $min
     * @param mixed $max
     * @return boolean
     */
    public static function is_in_range($x, $min, $max) {

        return is_numeric($x) && $x >= $min && $x <= $max;
    }

    /**
     * 判别是否相等
     *
     * @param mixed $a
     * @param mixed $b
     * @return boolean
     */
    public static function is_equal($a, $b = null) {

        return $a == $b;
    }

    /**
     * is_forbidden_word  判断是否含有违禁词
     *
     * @param  mixed $content        检查内容
     * @param  array $forbidden_word　违禁词列表
     * @param  bool $is_strict        是否严格匹配
     * @return void
     */
    public static function is_forbidden_word($content, $forbidden_word = array(), $is_strict = false) {

        $len = strlen($content);

        foreach ($forbidden_word as $word) {

            // 严格匹配
            if ($is_strict) {

                if ($content == $word) {
                    return true;
                }
            } else {

                $new_content = strtolower($content);
                $word2 = strtolower($word);
                $tmp_len = strlen(str_replace($word2, '', $new_content));

                if ($tmp_len != $len) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 判断字符串长度是否在两个数值之间
     *
     * @param string $str 字符串
     * @param integer $min 最小长度
     * @param integer $max 最大长度
     * @param string $encoding 编码
     * @return boolean
     */
    public static function is_len_in_range($str, $min, $max, $length_type = Util_String::STRING_TYPE_WIDTH, $encoding = 'UTF-8') {

        $string_length = Util_String::strlen($str, $length_type, $encoding);

        return $string_length >= $min && $string_length <= $max;
    }

    /**
     * 判断数组长度是否在两个数值之间
     *
     * @param array $array 数组
     * @param integer $min 最小长度
     * @param integer $max 最大长度
     * @return boolean
     */
    public static function is_array_len_in_range($array, $min, $max) {

        if (!is_array($array)) {
            return false;
        }

        $len = count($array);

        return $min <= $len && $len <= $max;
    }

    /*
     * 判断一个数组中的所有值是否为整数。
     *
     * @param array $arr
     * @return boolean
     */
    public static function is_int_array($arr) {

        if (!is_array($arr)) {
            return false;
        }

        foreach ($arr as $v) {
            if(!Validator::is_int($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * is_not_in
     * 判别传入的第一个参数是否不在剩下的几个参数中
     *
     * @return boolean
     */
    public static function is_not_in() {

        $params = func_get_args();
        $key = array_shift($params);
        if (is_array($params[0])) {

            return !in_array($key, $params[0]);
        } else {

            return !in_array($key, $params);
        }
    }

    /**
     * is_in
     * 判别传入的第一个参数是否在剩下的几个参数中
     *
     * @return void
     */
    public static function is_in() {

        $params = func_get_args();
        $key = array_shift($params);
        if (is_array($params[0])) {

            return in_array($key, $params[0]);
        } else {

            return in_array($key, $params);
        }
    }

    /**
     * is_id_card
     * 验证身份证的合法性
     *
     * @param mixed $id
     * @return boolean
     */
    public static function is_id_card($id) {

        $len = strlen($id);

        if ($len == 15 && preg_match("/^[0-9]{15}$/", $id)) {

            return true;
        } elseif ($len != 18 || !preg_match("/^[0-9]{17}[0-9Xx]{1}$/", $id)) {

            return false;
        }

        $id = strtoupper($id);
        $i_s = 0;
        $i_w = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        $VerCode = '10X98765432';

        for($i = 0; $i < 17; $i++) {
            $i_s += intval($id[$i]) * $i_w[$i];
        }

        $i_y = $i_s % 11;

        if($id[17] == $VerCode[$i_y]) {

            return true;
        }

        return false;
    }

    /**
     * is_ip
     * 是否为IP地址
     *
     * @param string $ip
     * @return boolean
     */
    public static function is_ip($ip) {
        if ($ip && preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $ip)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * is_url
     *
     * @param mixed $url
     * @return boolean
     */
    public static function is_url($url) {

        if (!$url) {
            return false;
        }

        // return strlen($url) < 256 && preg_match("/^http:\/\/[a-zA-z0-9-.]_\/(/i", $url);
        return strlen($url) < 4096 && preg_match("/(https?:\/\/)?[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/", $url);  //by zhuchenguang
    }

    /**
     * is_required
     * 是否是必须的
     *
     * @param  mixed $data
     * @return boolean
     */
    public static function is_required($data) {

        if ($data) {
            return true;
        }

        return false;
    }

    /**
     * is_exists
     * 是否是存在的
     *
     * @param  mixed $data
     * @return boolean
     */
    public static function is_exists($data) {

        if ($data === null || $data === false || $data === '' || $data === array()) {
            return false;
        }

        return true;
    }

    /**
     * is_date
     *
     * @param  mixed $date_time
     * @access public
     * @return void
     */
    public static function is_date($date_time, $format = 'Y-m-d H:i:s') {

        $time = strtotime($date_time);
        if (strcmp(date($format, $time), $date_time) === 0) {
            return true;
        }

        return false;
    }

    /**
     * is_lanip
     * 是否是局域网IP
     *
     * @param  mixed $ip
     * @return void
     */
    public function is_lanip($ip) {

        if ($ip >= ip2long('10.0.0.0') && $ip <= ip2long('10.255.255.255')) {
            return true;
        }

        if ($ip >= ip2long('127.0.0.0') && $ip <= ip2long('127.255.255.255')) {
            return true;
        }

        if ($ip >= ip2long('172.16.0.0') && $ip <= ip2long('172.16.255.255')) {
            return true;
        }

        if ($ip >= ip2long('192.168.0.0') && $ip <= ip2long('192.168.255.255')) {
            return true;
        }

        if ($ip >= ip2long('244.0.0.0') && $ip <= ip2long('244.255.255.255')) {
            return true;
        }

        if ($ip == ip2long('255.255.255.255')) {
            return true;
        }

        return false;

    }

    /**
     * verify
     *
     * @param  mixed $options
     *  + fieldNmae(post Field name)
     *    + rules
     *      + required => true
     *      + email => true
     *      + realname => true
     *      + password => true
     *      + userName => true
     *      + int => true
     *      + image => true
     *      + phone => true
     *      + mobile => true
     *      + addr => true
     *      + postalcode => true
     *      + equal => 21
     *      + lenInRange => array(min, max)
     *      + inRange => array(min, max)
     *      + idCard => true
     *      + ip => true
     *      + url => true
     *  + fieldNmae(post Field name)
     *    + messages => msg
     * @param  mixed $result
     * @return array $error
     * <code>
     * $options = array();
     * $options['uEmail']['required']['rule'] = true;
     * $options['uEmail']['required']['message'] = 'email 不能为空';
     * $options['uPassword']['required']['rule'] = true;
     * $options['uPassword']['required']['message'] = '密码不能为空';
     * $options['uPassword']['password']['rule'] = true;
     * $options['uPassword']['password']['message'] = '密码必须6个字符以上';
     * $options['uAge']['int']['rule'] = true;
     * $options['uAge']['int']['message'] = 'int';
     * $options['uAge']['inRange']['rule'] = array(10, 100);
     * $options['uAge']['inRange']['message'] = '10-100 ';
     *
     * $options['uUrl']['urlReturn']['rule'] = array('ValidatorTest::urlReturn');
     * $options['uUrl']['urlReturn']['message'] = 'url地址不正确';
     *
     * $data    = array();
     * $data['uEmail'] = 'snowrui@yeah.net';
     * $data['uPassword'] = '1et';
     * $data['uAge'] = '1';
     * $error  = Validator::verify($data, $options);
     * </code>
     */
    public static function verify($data, $options) {

        if (!$data || !is_array($data) || !is_array($options)) {
            return false;
        }

        $errors = array();
        // 不需要做验证的规则
        $excluded_rules = array('ajax'); // ajax验证是只有js前端会用到的，PHP不会用到

        foreach ($options as $field_name => $rules) {

            // 如果没有指定$field_name为require, 或require值为false
            // 则对此字段的其他验证(比如is_email等)都是针对有值的情况下, 没值的话不做验证
            $is_required = true;
            if ((!array_key_exists('required', $rules) && !array_key_exists('exists', $rules)) || !$rules['required']['rule']) {
                $is_required = false;
                unset($rules['required']);
            }

            foreach ($rules as $method => $value) {

                // 不需要做验证的规则跳过
                if (in_array($method, $excluded_rules)) {
                    continue;
                }

                $m = 'is' . ucwords($method);
                settype($value['rule'], 'array');

                // 判断是否自定义函数
                if (method_exists('Validator', $m)) {
                    array_unshift($value['rule'], $data[$field_name]);
                    $m = array('Validator', $m);
                } else {
                    $m = array_shift($value['rule']);
                    array_unshift($value['rule'], $data[$field_name]);
                }

                // 如果是required的, 那么必须验证且不通过要报error
                // 如果不是required的, 只有在$data[$field_name]有值时才去验证和报error, 否则不验证
                if ($is_required || (!$is_required && $data[$field_name])) {
                    if (!call_user_func_array($m, $value['rule'])) {
                        $errors[$field_name] = $options[$field_name][$method]['message'];
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * 检查文件名是否合法（不包含非法字符）
     *
     * @param $filename
     *
     * @returns
     */
    public static function is_file_name($file_name) {

        if (preg_match('/([\*\/\\<>:\?\|])|(\.\.)/is', $file_name)) {
            return false;
        }

        return true;
    }

    /**
     * is_allowed_chars
     * 检查指定字符串是否仅为允许的指定字符
     *
     * @param  string $str 需要检查的字符串
     * @param  array  $allowed 允许的字符数组，数组每项的值是一个或多个允许的字符，允许写部分常见的正则范围
     *  notice: 为避免php字符串转义，应使用单引号
     *  array(
     *        '\d',
     *        '0-9',
     *        'a-z',
     *        'A-Z',
     *        '\w',
     *        '\s',
     *        ',.[];',
     *        'def'
     *       )
     * @return boolean
     */
    public static function is_allowed_chars($str, $allowed_char_list) {

        if (!$str) {
            return false;
        }

        if (!$allowed_char_list || !is_array($allowed_char_list)) {
            throw new Exception('验证规则配置错误');
        }

        $pattern_char_list = array();

        foreach ($allowed_char_list as $allowed_char) {
            switch ($allowed_char) {
                case '\d':
                case '0-9':
                case 'a-z':
                case 'A-Z':
                case '\w':
                case '\s':
                    $pattern_char_list[] = $allowed_char;
                    break;
                default:
                    $pattern_char_list[] = preg_quote($allowed_char, '/');
                    break;
            }
        }

        $pattern = '/^[' . implode('', $pattern_char_list) . ']+$/';

        if (preg_match($pattern, $str)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * check_required
     *
     * @param  array $config
     * @return boolean
     */
    public static function check_required($config) {
        foreach ($config as $key => $value) {
            $data[$key] = $value['value'];
            $options[$key]['required']['rule'] = true;
            $options[$key]['required']['message'] = $value['message'];
        }

        return self::verify($data, $options);
    }

    /**
     * 判断ip是否为真实的公网ip
     * check ip is true wan
     * @param $ip
     * @return bool
     */
    public static function check_ip_true_wan($ip)
    {
        $telnet = new Telnet($ip);
        if (!($telnet)) {
            return false;
        }
        return true;
    }
}

