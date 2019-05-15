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
     * 中场
     * @param array $node 节点信息
     * @return array
     */
    public function mid (array $node)
    {
        return error('未知中场');
    }

    /**
     * 正常通行
     * @param array $entry 入场信息
     * @return array
     */
    public function normalPass (array $entry)
    {
        return error('不能正常通行');
    }

    /**
     * 计算计费金额
     * @param $parameter
     * @param $code
     * @return int (分)
     */
    protected function calculationCode (array $parameter, $code)
    {
        $list = $parameter;
        extract($parameter);
        $cost = @eval($code);
        if (null === $cost || false === $cost) {
            return false;
        }
        foreach ($list as $k => $v) {
            $code = str_replace(['${"' . $k . '"}', '${\'' . $k . '\'}', '$' . $k], '{' . $v . '}', $code);
        }
        unset($list, $parameter);
        $cost = intval($cost);
        $cost = $cost < 0 ? 0 : $cost;
        return [
            'cost' => $cost, 'code' => $code
        ];
    }

}
