<?php
/**
 * 车辆超类
 */

namespace app\pdo;

interface SuperCar
{
    /**
     * 入场
     * @param array $node 节点信息
     * @param array $paths 路径信息
     * @param array $carPaths car_path信息
     * @return array
     */
    public function entry (array $node, array $paths, array $carPaths);

    public function out ();

    public function mid ();
}
