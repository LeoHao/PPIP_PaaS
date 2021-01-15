<?php
/**
 * @Filename         : ServerAction.php
 * @Author           : LeoHao
 * @Email            : blueseamyheart@hotmail.com
 * @Last modified    : 2021-1-12 01:21
 * @Description      : auth action function
 **/

class ServerAction {

    /**
     * client init
     * @param $data
     * @param $db
     */
    public static function clientInit($data)
    {
        $device_info = Devices::find_by_mac($data['CpeMac']);
        if (!empty($device_info)) {
            $update_data = array();
            $update_data['ip'] = $data['CpeIp'];
            $update_data['status'] = ($data['CpeStatus'] == 'online') ? 1 : 0;
            if (Devices::update_by_mac($update_data, $data['CpeMac'])) {
                Logger::trace("CPE update info data:" . json_encode($data), 'swoole');
            }
        }
    }

    public static function clientGetOwnPlugins()
    {

    }

    public static function clientGetOwnWebside()
    {

    }

    public static function clientGetOwnNode()
    {

    }

    public static function pluginsNetworkSpecialOpen()
    {

    }

}