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

 Date: 06/05/2019 18:12:19
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
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态 1正常 0禁用',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `car_number`(`car_number`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '会员车' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_car
-- ----------------------------
INSERT INTO `chemi_car` VALUES (1, '[1]', '贵A111111', 0, '2019-04-07 18:07:25', '2019-04-07 18:07:29', 1);

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
) ENGINE = MyISAM CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '车牌纠正记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for chemi_entry
-- ----------------------------
DROP TABLE IF EXISTS `chemi_entry`;
CREATE TABLE `chemi_entry`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_type` tinyint(4) NULL DEFAULT NULL COMMENT '车辆类型',
  `original_car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '原始车牌号',
  `car_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '车牌号',
  `money` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '收费金额 (分)',
  `paths` json NULL COMMENT '当前路径',
  `current_node_id` mediumint(8) UNSIGNED NULL DEFAULT NULL COMMENT '当前节点ID',
  `last_nodes` json NULL COMMENT '节点记录 [{\"node_id\":node_id,\"time\":time}]',
  `correction_record_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '车牌纠正记录ID',
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
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '名称',
  `abnormal_car_pass_way` tinyint(1) NULL DEFAULT 1 COMMENT '异常车通行方式 1自动起竿放行 2收费 3手动起竿放行',
  `abnormal_car_charge` mediumint(8) UNSIGNED NULL DEFAULT 0 COMMENT '异常车收费金额 (分)',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `index`(`area_id`, `name`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '节点' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_node
-- ----------------------------
INSERT INTO `chemi_node` VALUES (1, 1, '进口A', 0, 0);
INSERT INTO `chemi_node` VALUES (2, 1, '出口A', 0, 0);

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
  `temp_car_count` mediumint(5) UNSIGNED NULL DEFAULT NULL COMMENT '临时车车位数',
  `temp_car_left` mediumint(5) UNSIGNED NULL DEFAULT NULL COMMENT '临时车剩余车位数',
  `allow_invalid_car` tinyint(1) NULL DEFAULT 0 COMMENT '会员车失效后是否允许入场 (月卡过期、余额不足)  1允许 0不允许',
  `allow_child_car` tinyint(1) NULL DEFAULT 0 COMMENT '附属车位满后是否允许入场 (子母车位) 1允许 0不允许',
  `update_time` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `create_time` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '路径' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of chemi_path
-- ----------------------------
INSERT INTO `chemi_path` VALUES (1, '临时车专用通道', 1, 2, '[1, 2]', 1, 10, 10, 0, 0, '2019-04-07 18:00:34', '2019-04-07 18:00:37');
INSERT INTO `chemi_path` VALUES (2, '会员车专用通道', 1, 2, '[1, 2]', 0, NULL, NULL, 0, 0, '2019-04-07 18:00:34', '2019-04-07 18:00:37');

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
-- Table structure for pro_session
-- ----------------------------
DROP TABLE IF EXISTS `pro_session`;
CREATE TABLE `pro_session`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` mediumint(8) UNSIGNED NULL DEFAULT NULL,
  `scode` char(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `clienttype` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `clientapp` char(7) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `stoken` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `clientinfo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `online` tinyint(1) NULL DEFAULT 1,
  `loginip` char(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `u`(`userid`, `clienttype`) USING BTREE,
  INDEX `u1`(`userid`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '会话表' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pro_smscode
-- ----------------------------
DROP TABLE IF EXISTS `pro_smscode`;
CREATE TABLE `pro_smscode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tel` char(11) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '手机号',
  `code` char(6) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '验证码',
  `sendtime` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '发送时间',
  `errorcount` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '错误次数',
  `hour_fc` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '时级限制',
  `day_fc` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '天级限制',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `tel`(`tel`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '短信验证码' ROW_FORMAT = Compact;

SET FOREIGN_KEY_CHECKS = 1;
