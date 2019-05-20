<?php
/**
 * 车辆超类
 */

namespace app\pdo;

abstract class SuperCar
{
    /**
     * 入场
     * @param array $post post信息
     * @param array $node 节点信息
     * @param array $paths 路径信息
     * @param array $carPaths car_path信息
     * @return array
     */
    public function entry (array $post, array $node, array $paths, array $carPaths)
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
     * @param array $entry 入场信息
     * @param array $node 节点信息
     * @return array
     */
    public function mid (array $entry, array $node)
    {
        return error('未知中场');
    }

    /**
     * 正常放行
     * @param array $entry 入场信息
     * @return array
     */
    public function normalPass (array $entry)
    {
        return error('不能正常放行');
    }

    /**
     * 异常放行
     * @param $node_id 节点ID
     * @return array
     */
    public function abnormalPass ($node_id)
    {
        return error('不能异常放行');
    }

    /**
     * 撤销放行
     * @param array $entry 入场信息
     * @param $node_id 节点ID
     * @return array
     */
    public function revokePass (array $entry, $node_id)
    {
        return error('不能撤销放行');
    }

    /**
     * 计算计费金额
     * @param $parameter
     * @param $code
     * @return array {"cost":分,"code":计算过程}
     */
    protected function calculationCode (array $parameter, $code)
    {
        if (empty($code)) {
            return false;
        }
        extract($parameter);
        $cost = @eval($code);
        if (null === $cost || false === $cost) {
            return false;
        }
        foreach ($parameter as $k => $v) {
            $code = str_replace(['${"' . $k . '"}', '${\'' . $k . '\'}', '$' . $k], '{' . $v . '}', $code);
        }
        unset($parameter);
        $cost = intval($cost);
        $cost = $cost < 0 ? 0 : $cost;
        return [
            'cost' => $cost, 'code' => $code
        ];
    }

}
