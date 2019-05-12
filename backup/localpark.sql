/*
 Navicat Premium Data Transfer

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 80015
 Source Host           : localhost:3306
 Source Schema         : localpark

 Target Server Type    : MySQL
 Target Server Version : 80015
 File Encoding         : 65001

 Date: 12/05/2019 17:57:52
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for chemi_abnormal_car
-- ----------------------------
DROP TABLE IF EXISTS `chemi_abnormal_car`;
CREATE TABLE `chemi_abnormal_car`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `node_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '节点ID',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '收费金额 (分)',
  `correction_record_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车牌纠正记录ID',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `car_number`(`car_number`) USING BTREE,
  INDEX `node_id`(`node_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '异常车' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for chemi_area
-- ----------------------------
DROP TABLE IF EXISTS `chemi_area`;
CREATE TABLE `chemi_area`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '区域名',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '区域' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_area
-- ----------------------------
INSERT INTO `chemi_area` VALUES (1, 'A区', '2019-04-07 18:09:59');

-- ----------------------------
-- Table structure for chemi_car
-- ----------------------------
DROP TABLE IF EXISTS `chemi_car`;
CREATE TABLE `chemi_car`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `area_id` json NULL COMMENT '区域ID',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `is_entry` tinyint(1) NULL DEFAULT 0 COMMENT '是否在场 1是 0否',
  `out_time` datetime(0) NULL DEFAULT NULL COMMENT '最后出场时间',
  `car_type` tinyint(3) NULL DEFAULT 0 COMMENT '会员车类型 2月卡 3贵宾 4固定车 5储值卡 10普通车',
  `start_time` datetime(0) NULL DEFAULT NULL COMMENT '有效期开始时间',
  `end_time` datetime(0) NULL DEFAULT NULL COMMENT '有效期结束时间',
  `balance` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '余额 (分)',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `car_number`(`car_number`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '会员车' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_car
-- ----------------------------
INSERT INTO `chemi_car` VALUES (1, '[1]', '贵A111111', 0, NULL, 0, NULL, NULL, 0, 1, '2019-04-07 18:07:25', '2019-04-07 18:07:29');

-- ----------------------------
-- Table structure for chemi_car_path
-- ----------------------------
DROP TABLE IF EXISTS `chemi_car_path`;
CREATE TABLE `chemi_car_path`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '车辆ID',
  `path_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '路径ID',
  `car_number` json NULL COMMENT '车牌号',
  `place_count` mediumint(5) UNSIGNED NULL DEFAULT 1 COMMENT '车位数',
  `place_left` mediumint(5) UNSIGNED NULL DEFAULT 1 COMMENT '剩余车位数',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1启用 0禁用',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`car_id`, `path_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '会员车路径关联' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_car_path
-- ----------------------------
INSERT INTO `chemi_car_path` VALUES (1, 1, 2, '[\"贵A111111\", \"贵A123456\"]', 1, 1, 1);

-- ----------------------------
-- Table structure for chemi_correction_record
-- ----------------------------
DROP TABLE IF EXISTS `chemi_correction_record`;
CREATE TABLE `chemi_correction_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `node_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '节点ID',
  `original_car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原始车牌号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '新车牌号',
  `error_scene` json NULL COMMENT '错误场景',
  `error_count` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '纠正次数',
  `message` json NULL COMMENT '消息记录',
  `scene_result` json NULL COMMENT '错误场景校正记录',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '修改时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `node_id`(`node_id`) USING BTREE,
  INDEX `original_car_number`(`original_car_number`) USING BTREE,
  INDEX `car_number`(`car_number`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '车牌纠正记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for chemi_entry
-- ----------------------------
DROP TABLE IF EXISTS `chemi_entry`;
CREATE TABLE `chemi_entry`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_type` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '车辆类型',
  `entry_car_type` tinyint(3) NULL DEFAULT NULL COMMENT '入场车类型',
  `out_car_type` tinyint(3) NULL DEFAULT NULL COMMENT '出场车类型',
  `original_car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原始车牌号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '应收金额 (分)',
  `real_money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '实收金额 (分)',
  `paths` json NULL COMMENT '当前路径',
  `current_node_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '当前节点ID',
  `last_nodes` json NULL COMMENT '节点记录 [{\"node_id\":node_id,\"time\":time}]',
  `correction_record` json NULL COMMENT '车牌纠正记录',
  `signal_type` tinyint(1) NULL DEFAULT 0 COMMENT '信号发送类型',
  `broadcast` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '播报内容',
  `version_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '版本计数',
  `pass_type` tinyint(1) NULL DEFAULT 0 COMMENT '通行方式 0等待通行 1正常通行 2异常放行',
  `onduty_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '值班员ID',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `car_number`(`car_number`) USING BTREE,
  INDEX `current_node_id`(`current_node_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '入场车辆' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for chemi_node
-- ----------------------------
DROP TABLE IF EXISTS `chemi_node`;
CREATE TABLE `chemi_node`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `area_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '区域ID',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '名称',
  `abnormal_car_pass_way` tinyint(1) NULL DEFAULT 1 COMMENT '异常车通行方式 1自动起竿放行 2收费 3手动起竿放行',
  `abnormal_car_charge` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '异常车收费金额 (分)',
  `temp_car_count` mediumint(5) UNSIGNED NULL DEFAULT 0 COMMENT '临时车车位数 (0不限制)',
  `temp_car_left` mediumint(5) UNSIGNED NULL DEFAULT 0 COMMENT '临时车剩余车位数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '节点' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_node
-- ----------------------------
INSERT INTO `chemi_node` VALUES (1, 1, '进口A', 0, 0, 0, 0);
INSERT INTO `chemi_node` VALUES (2, 1, '出口A', 0, 0, 0, 0);

-- ----------------------------
-- Table structure for chemi_out
-- ----------------------------
DROP TABLE IF EXISTS `chemi_out`;
CREATE TABLE `chemi_out`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_type` tinyint(3) UNSIGNED NULL DEFAULT NULL COMMENT '车辆类型',
  `entry_car_type` tinyint(3) NULL DEFAULT NULL COMMENT '入场车类型',
  `out_car_type` tinyint(3) NULL DEFAULT NULL COMMENT '出场车类型',
  `original_car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原始车牌号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '应收金额 (分)',
  `real_money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '实收金额 (分)',
  `paths` json NULL COMMENT '当前路径',
  `current_node_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '当前节点ID',
  `last_nodes` json NULL COMMENT '节点记录 [{\"node_id\":node_id,\"time\":time}]',
  `correction_record` json NULL COMMENT '车牌纠正记录',
  `signal_type` tinyint(1) NULL DEFAULT 0 COMMENT '信号发送类型',
  `broadcast` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '播报内容',
  `version_count` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '版本计数',
  `pass_type` tinyint(1) NULL DEFAULT 0 COMMENT '通行方式 0等待通行 1正常通行 2异常放行',
  `onduty_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '值班员ID',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `log_time` datetime(0) NULL DEFAULT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `car_number`(`car_number`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '出场车辆' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for chemi_path
-- ----------------------------
DROP TABLE IF EXISTS `chemi_path`;
CREATE TABLE `chemi_path`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '名称',
  `start_node` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '起点',
  `end_node` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '终点',
  `nodes` json NULL COMMENT '路径节点列',
  `allow_temp_car` tinyint(1) NULL DEFAULT 0 COMMENT '临时车是否允许入场 1允许 0不允许',
  `allow_invalid_car` tinyint(1) NULL DEFAULT 0 COMMENT '会员车失效后是否允许入场 (月卡过期、余额不足)  1允许 0不允许',
  `allow_child_car` tinyint(1) NULL DEFAULT 0 COMMENT '附属车位满后是否允许入场 (子母车位) 1允许 0不允许',
  `calculation_code` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '计费逻辑',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '路径' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_path
-- ----------------------------
INSERT INTO `chemi_path` VALUES (1, '临时车专用通道', 1, 2, '[1, 2]', 1, 0, 0, '$total_time = ${\"节点1_2停留时间\"};\r\nif ($total_time / 60 <= 30) {\r\n    return 0;\r\n}\r\n$money = round($total_time / 3600 * 3, 2);\r\nreturn $money > 25 ? 25 : $money;', '2019-04-07 18:00:34', '2019-04-07 18:00:37');
INSERT INTO `chemi_path` VALUES (2, '会员车专用通道', 1, 2, '[1, 2]', 0, 0, 0, 'if ($available) {\r\n    return 0;\r\n}\r\n$total_time = ${\"节点1_2停留时间\"};\r\nif ($total_time / 60 <= 30) {\r\n    return 0;\r\n}\r\n$money = round($total_time / 3600 * 3, 2);\r\nreturn $money > 25 ? 25 : $money;', '2019-04-07 18:00:34', '2019-04-07 18:00:37');

-- ----------------------------
-- Table structure for pro_config
-- ----------------------------
DROP TABLE IF EXISTS `pro_config`;
CREATE TABLE `pro_config`  (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `app` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '数据源',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '配置名',
  `type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '数据类型',
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '配置值',
  `min` int(11) NULL DEFAULT NULL COMMENT '最小值',
  `max` int(11) NULL DEFAULT NULL COMMENT '最大值',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL COMMENT '说明',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '系统配置项' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pro_hashcheck
-- ----------------------------
DROP TABLE IF EXISTS `pro_hashcheck`;
CREATE TABLE `pro_hashcheck`  (
  `hash` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一标识',
  `dateline` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`hash`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '验证唯一性记录表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pro_ratelimit
-- ----------------------------
DROP TABLE IF EXISTS `pro_ratelimit`;
CREATE TABLE `pro_ratelimit`  (
  `skey` char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一key',
  `min_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '分钟访问次数',
  `hour_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '小时访问次数',
  `day_num` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '天访问次数',
  `time` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '访问时间',
  `microtime` mediumint(3) UNSIGNED NULL DEFAULT NULL COMMENT '毫秒',
  `version` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '版本号',
  PRIMARY KEY (`skey`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '访问限流控制' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of pro_ratelimit
-- ----------------------------
INSERT INTO `pro_ratelimit` VALUES ('6eb826a1e03978c0197b1e0f6506ad3b', 1, 1, 40, 1557559317, 875, 39);

-- ----------------------------
-- Table structure for pro_session
-- ----------------------------
DROP TABLE IF EXISTS `pro_session`;
CREATE TABLE `pro_session`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `scode` char(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clienttype` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `clientapp` char(7) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `stoken` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `clientinfo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `online` tinyint(1) NULL DEFAULT 1,
  `loginip` char(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `u`(`userid`, `clienttype`) USING BTREE,
  INDEX `u1`(`userid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
