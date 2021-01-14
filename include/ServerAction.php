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
     * @return array
     */
    public static function clientInit($data)
    {

        Devices::find_by_mac($data['CpeMac']);

        return array();
    }

    public static function getOwnPlugins()
    {

    }

    public static function getOwnWebside()
    {

    }

    public static function getOwnNode()
    {

    }

    public static function pluginsNetworkSpecialadd()
    {

    }

}
?>