<?php

/*
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace yiiTools\services;

use Yii;
use yii\base\InvalidConfigException;

/**
 * 此对象就是Yii::$service,通过魔术方法__get ， 得到服务对象，服务对象是单例模式。
 * @see http://www.fecshop.com/doc/fecshop-guide/develop/cn-1.0/guide-fecshop-service-abc.html
 *
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class Application
{
    /**
     * @var array 服务的配置数组
     */
    public $childService;

    /**
     * @var array 实例化过的服务数组
     */
    public $_childService;

    /**
     * @var array $config 注入的配置数组
     * 在 @app/web/index.php 入口文件处。会调用 new fecshop\services\Application($config['services']);
     * Yii::$service 就是该类实例化的对象，注入的配置保存到 $this->childService 中
     */
    public function __construct(&$config = [])
    {
        Yii::$service = $this;
        $this->initRewriteMap($config);
        $this->childService = $config['services'];
        unset($config['services']);
//        $beforeEventName = 'event_service_application_init';
//        Yii::$service->event->trigger($beforeEventName, $config);
    }
    // init yiiClassMap and fecRewriteMap
    public function initRewriteMap(&$config)
    {
        /**
         * yii class Map Custom
         */
        $yiiClassMap = isset($config['yiiClassMap']) ? $config['yiiClassMap'] : '';
        if(is_array($yiiClassMap) && !empty($yiiClassMap)){
            foreach($yiiClassMap as $namespace => $filePath){
                Yii::$classMap[$namespace] = $filePath;
            }
        }
        unset($config['yiiClassMap']);
        /**
         * Yii 重写block controller model等
         * 也就是说：除了compoent 和services，其他的用RewriteMap的方式来实现重写
         * 重写的类可以集成被重写的类
         */
        $fecRewriteMap = isset($config['fecRewriteMap']) ? $config['fecRewriteMap'] : '';
        if(is_array($fecRewriteMap) && !empty($fecRewriteMap)){
            Yii::$rewriteMap = $fecRewriteMap;
        }
        unset($config['fecRewriteMap']);
    }
    /**
     * 根据服务名字获取服务实例
     * Get service instance by service name.
     *
     * 用类似于 Yii2 的 component 原理，采用单例模式实现的服务功能，
     * 服务的配置文件位于 config/services 目录
     *
     * @var string $childServiceName
     * @return \yiiTools\services\Service
     * @throws \yii\base\InvalidConfigException if the service is not found or the service is disabled
     */
    public function getChildService($childServiceName)
    {
        if (!isset($this->_childService[$childServiceName]) || !$this->_childService[$childServiceName]) {
            $childService = $this->childService;
            if (isset($childService[$childServiceName])) {
                $service = $childService[$childServiceName];
                if (!isset($service['enableService']) || $service['enableService']) {
                    $this->_childService[$childServiceName] = Yii::createObject($service);
                } else {

                    throw new InvalidConfigException('Child Service ['.$childServiceName.'] is disabled in '.get_called_class().', you must enable it! ');
                }
            } else {

                throw new InvalidConfigException('Child Service ['.$childServiceName.'] does not exist in '.get_called_class().', you must config it! ');
            }
        }

        return isset($this->_childService[$childServiceName]) ? $this->_childService[$childServiceName] : null;
    }

    /**
     * 魔术方法，当调用一个属性，对象不存在的时候就会执行该方法，然后
     * 根据构造方法注入的配置，实例化service对象。
     * @var string $serviceName service name
     * @return \yiiTools\services\Service
     * @throws \yii\base\InvalidConfigException if the service does not exist or the service is disabled
     */
    public function __get($serviceName)
    {
        return $this->getChildService($serviceName);
    }
}
