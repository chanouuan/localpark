<?php
/**
 * 车辆超类
 */

namespace app\pdo;

abstract class SuperCar
{
    /**
     * 入场
     * @param array $node 节点信息
     * @param array $paths 路径信息
     * @param array $carPaths car_path信息
     * @return array
     */
    public function entry (array $node, array $paths, array $carPaths)
    {
        return error('未知入场');
    }

    /**
     * 出场
     * @param array $entry 入场信息
     * @param array $parameter 计算变量
     * @param array $paths 路径信息
     * @param array $carPaths car_path信息
     * @return array
     */
    public function out (array $entry, array $parameter, array $paths, array $carPaths)
    {
        return error('未知出场');
    }

    /**
     * 入场
     * @param array $node 节点信息
     * @return array
     */
    public function mid (array $node)
    {
        return error('未知中场');
    }

    /**
     * 计算计费金额
     * @param $parameter
     * @param $code
     * @return int (分)
     */
    protected function calculationCode (array $parameter, $code)
    {
        extract($parameter);
        $result = @eval($code);
        if (null === $result || false === $result) {
            return false;
        }
        $result = intval($result);
        return $result < 0 ? 0 : $result;
    }

}
