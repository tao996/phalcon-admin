-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mysql
-- 生成日期： 2026-05-21 07:57:21
-- 服务器版本： 8.1.0
-- PHP 版本： 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `phalcon`
--

-- --------------------------------------------------------

--
-- 表的结构 `tao_app_info`
--

CREATE TABLE `tao_app_info` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tao_app_info`
--

INSERT INTO `tao_app_info` (`id`, `created_at`, `updated_at`, `deleted_at`, `tag`, `title`, `status`, `remark`) VALUES
(1, 1733495295, 1733495296, 1733495296, 'test', 'Test Title', 1, 'Just a test app');

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_ad`
--

CREATE TABLE `tao_cms_ad` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `begin_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '标题',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '图片',
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '内链/外链/ID',
  `kind` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `at_banner` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '横幅',
  `at_index` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '首页',
  `at_list` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '列表',
  `at_page` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '内页',
  `tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '组名',
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `gname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '组名',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='广告';

--
-- 转存表中的数据 `tao_cms_ad`
--

INSERT INTO `tao_cms_ad` (`id`, `created_at`, `updated_at`, `deleted_at`, `user_id`, `begin_at`, `end_at`, `title`, `cover`, `link`, `kind`, `at_banner`, `at_index`, `at_list`, `at_page`, `tag`, `sort`, `gname`, `status`, `remark`) VALUES
(1, 1733495298, 1733495299, 1733495299, 1, 0, 0, 'just a test ad', 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg', '/m/tao/link/index', 1, 1, 1, 0, 0, '直播', 0, 'test', 0, 'just a test');

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_album`
--

CREATE TABLE `tao_cms_album` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `image_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `sort` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tao_cms_album`
--

INSERT INTO `tao_cms_album` (`id`, `created_at`, `updated_at`, `deleted_at`, `user_id`, `title`, `cover`, `summary`, `tag`, `image_ids`, `status`, `sort`) VALUES
(1, 1733495300, 1733495302, 1733495302, 1, 'just a test album', 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg', 'this is a test', 'test', '', 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_article`
--

CREATE TABLE `tao_cms_article` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创作者',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT 'IP 地址',
  `cate_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目',
  `kind` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
  `keywords` varchar(60) NOT NULL DEFAULT '' COMMENT '关键词',
  `summary` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `cstatus` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核状态',
  `cuser_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核员 ID',
  `cmessage` varchar(255) NOT NULL DEFAULT '' COMMENT '留言',
  `sort` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `image_ids` varchar(255) NOT NULL DEFAULT '',
  `content_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '正文 ID',
  `hot` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '热门',
  `top` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '置顶',
  `hits` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_category`
--

CREATE TABLE `tao_cms_category` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `kind` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型',
  `pid` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '父级 ID',
  `pids` varchar(255) NOT NULL DEFAULT '' COMMENT '父级 ID 链',
  `title` varchar(50) NOT NULL COMMENT '标题',
  `name` varchar(50) NOT NULL DEFAULT '',
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '封面',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `tpl` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT '分组标签',
  `navbar` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `sort` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `image_ids` varchar(255) NOT NULL DEFAULT '',
  `content_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '内容 ID',
  `other` varchar(255) NOT NULL DEFAULT '' COMMENT '其它内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='栏目分类';

--
-- 转存表中的数据 `tao_cms_category`
--

INSERT INTO `tao_cms_category` (`id`, `created_at`, `updated_at`, `deleted_at`, `kind`, `pid`, `pids`, `title`, `name`, `cover`, `summary`, `tpl`, `tag`, `navbar`, `sort`, `status`, `image_ids`, `content_id`, `other`) VALUES
(1, 1733495305, 1733495307, 1733495307, 1, 0, '', 'MyTest', 'mytest', 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg', 'this is a test article', '', 'test', 0, 0, 0, '', 4, '');

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_content`
--

CREATE TABLE `tao_cms_content` (
  `id` int UNSIGNED NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `tao_cms_content`
--

INSERT INTO `tao_cms_content` (`id`, `content`) VALUES
(1, 'content 1 here'),
(2, 'content 2 here'),
(3, 'content 3 here'),
(4, '1733495306'),
(5, 'GGG');

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_link`
--

CREATE TABLE `tao_cms_link` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL,
  `href` varchar(255) NOT NULL,
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `tag` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_cms_page`
--

CREATE TABLE `tao_cms_page` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_at` int UNSIGNED DEFAULT NULL COMMENT '删除时间',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '标签',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '英文名称',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '中文标题',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `content_id` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='单页';

--
-- 转存表中的数据 `tao_cms_page`
--

INSERT INTO `tao_cms_page` (`id`, `created_at`, `updated_at`, `deleted_at`, `status`, `tag`, `name`, `title`, `sort`, `content_id`) VALUES
(1, 1730964623, 1730964632, NULL, 1, 'boyu', 'terms', '隐私政策', 0, 1),
(2, 1730964675, 1730981817, NULL, 1, 'boyu', 'contact', '联系方式', 0, 2),
(3, 1730964701, 1730981818, NULL, 1, 'boyu', 'us', '关于我们', 0, 3),
(4, 1733495311, 1733495312, 1733495312, 0, 'test', 'test.1733495311', '测试单页', 0, 5);

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_app`
--

CREATE TABLE `tao_open_app` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '名称',
  `platform` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '平台',
  `kind` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '类型',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'appID或agentID',
  `secret` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '密钥',
  `crop_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '企业微信',
  `token` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '令牌',
  `enc_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '加密方式',
  `aes_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '消息加密密钥',
  `online` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '线上',
  `public_key` varchar(255) NOT NULL DEFAULT '' COMMENT '平台公钥',
  `pi0` int UNSIGNED NOT NULL DEFAULT '0',
  `rsa_public_key` varchar(255) NOT NULL DEFAULT '' COMMENT '应用公钥',
  `pi1` int UNSIGNED NOT NULL DEFAULT '0',
  `rsa_private_key` varchar(255) NOT NULL DEFAULT '' COMMENT '应用私钥',
  `pi2` int UNSIGNED NOT NULL DEFAULT '0',
  `done` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '证书完整',
  `sandbox` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '沙盒',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态',
  `remark` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `tao_open_app`
--

INSERT INTO `tao_open_app` (`id`, `created_at`, `updated_at`, `deleted_at`, `sort`, `title`, `platform`, `kind`, `appid`, `secret`, `crop_id`, `token`, `enc_method`, `aes_key`, `online`, `public_key`, `pi0`, `rsa_public_key`, `pi1`, `rsa_private_key`, `pi2`, `done`, `sandbox`, `status`, `remark`) VALUES
(1, 1733495314, 1733495315, 1733495315, 0, 'myTest:1733495314', 1, 'mini', 'wx1733495314', '456789', 'crop123', '123', 'aes', '123456', 1, '', 0, '', 0, '', 0, 0, 1, 1, 'ad test');

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_config`
--

CREATE TABLE `tao_open_config` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `tao_open_config`
--

INSERT INTO `tao_open_config` (`id`, `name`, `value`, `remark`) VALUES
(1, 'proxy_origin', '', '内网穿透域名'),
(2, 'gzh_appid', '', '默认授权公众号 appid'),
(3, 'web_appid', '', '默认网页授权 appid'),
(4, 'pay_mchid', '', '默认微信支付商户号 mchid');

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_mch`
--

CREATE TABLE `tao_open_mch` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `done` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '资料是否完整',
  `mchid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '商户 ID',
  `private_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'clientKey 路径',
  `certificate` varchar(255) NOT NULL DEFAULT '' COMMENT 'clientCert 路径',
  `secret_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'v3 api 秘钥',
  `v2_secret_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'v2 api 秘钥',
  `platform_cert` varchar(255) NOT NULL DEFAULT '' COMMENT '平台证书',
  `pubkey_id` varchar(255) NOT NULL DEFAULT '' COMMENT '公钥ID',
  `pubkey` varchar(255) NOT NULL DEFAULT '' COMMENT '公钥路径',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_order`
--

CREATE TABLE `tao_open_order` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `app` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '来源应用',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户',
  `channel` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '渠道',
  `trade_type` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '场景',
  `rndcode` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '随机字符串',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '公众号 ID',
  `mchid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '商户号 ID',
  `openid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户标识',
  `amount` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '金额/分',
  `currency` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '货币单位',
  `metadata` json DEFAULT NULL COMMENT '下单数据',
  `response` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '订单创建响应数据',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '订单状态',
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '提示信息',
  `transaction_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '交易单号',
  `success_time` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '交易时间',
  `refund_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '申请退款时间',
  `refund_amt` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '申请退款金额',
  `refund_status` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '退款状态',
  `refund_id` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '退款单号',
  `refund_amount` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '实际退款金额',
  `refund_time` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '实际退款时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `tao_open_order`
--

INSERT INTO `tao_open_order` (`id`, `created_at`, `updated_at`, `app`, `user_id`, `channel`, `trade_type`, `rndcode`, `appid`, `mchid`, `openid`, `amount`, `currency`, `metadata`, `response`, `status`, `message`, `transaction_id`, `success_time`, `refund_at`, `refund_amt`, `refund_status`, `refund_id`, `refund_amount`, `refund_time`) VALUES
(1, 1733495320, 1733495320, 0, 0, 1, 2, 'LJvYl', 'wx1234567890', '1234567890', 'op123456', 7076, 1, '{\"description\": \"my test 1\"}', '{\"prepay_id\":\"test:1733495319\"}', 20, '', 'xxx123', 1724946038, 0, 0, 0, '', 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_user_openid`
--

CREATE TABLE `tao_open_user_openid` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `platform` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `openid` varchar(50) NOT NULL,
  `unionid` varchar(50) NOT NULL DEFAULT '',
  `session_key` varchar(255) NOT NULL DEFAULT '',
  `nickname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `avatar_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `gender` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '性别',
  `language` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `province` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `sub` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '关注公众号',
  `sub_at` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_open_user_unionid`
--

CREATE TABLE `tao_open_user_unionid` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `platform` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `unionid` varchar(50) NOT NULL DEFAULT '',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_config`
--

CREATE TABLE `tao_system_config` (
  `id` int UNSIGNED NOT NULL,
  `gname` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '分组',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '变量名',
  `value` text COMMENT '变量值',
  `remark` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注信息',
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统配置表';

--
-- 转存表中的数据 `tao_system_config`
--

INSERT INTO `tao_system_config` (`id`, `gname`, `name`, `value`, `remark`, `sort`, `created_at`, `updated_at`) VALUES
(1, 'upload', 'upload_type', 'def', '当前上传方式 （local,alioss,qnoss,txoss）', 0, 0, 1696640770),
(2, 'upload', 'upload_allow_type', 'local,alioss,qnoss,txcos', '可用的上传文件方式', 0, 0, 0),
(3, 'upload', 'upload_allow_size', '5', '允许上传的大小(M)', 0, 0, 0),
(4, 'upload', 'upload_allow_mime', 'image/gif,image/jpeg,video/x-msvideo,text/plain,image/png', '允许上传的文件mime', 0, 0, 0),
(5, 'upload', 'upload_allow_ext', 'doc,gif,ico,icon,jpg,mp3,mp4,p12,pem,png,rar,jpeg', '允许上传的文件类型', 0, 0, 0),
(6, 'upload', 'oss_dir', 'app', '文件在云上保存的子目录', 0, 0, 0),
(7, 'upload', 'oss_mimeLimit', 'image/*', '文件类型限制', 0, 0, 0),
(8, 'upload', 'oss_frontend', '1', '支持前端上传', 0, 0, 0),
(9, 'upload', 'oss_seconds', '3600', 'web 直传有效期', 0, 0, 0),
(10, 'upload', 'oss_size', '5', 'web 直传文件大小，单位 mb', 0, 0, 0),
(11, 'upload', 'txcos_secret_key', '', '腾讯云cos私钥', 0, 0, 0),
(12, 'upload', 'txcos_secret_id', '', '腾讯云cos密钥', 0, 0, 0),
(13, 'upload', 'txcos_region', '', '存储桶地域', 0, 0, 0),
(14, 'upload', 'txcos_bucket', '', '存储桶名称', 0, 0, 0),
(15, 'upload', 'qnoss_secret_key', '', '七牛安全密钥', 0, 0, 0),
(16, 'upload', 'qnoss_domain', '', '七牛访问域名', 0, 0, 0),
(17, 'upload', 'qnoss_bucket', '', '七牛存储空间', 0, 0, 0),
(18, 'upload', 'qnoss_access_key', '', '七牛访问密钥', 0, 0, 0),
(19, 'upload', 'alioss_endpoint', '', '阿里云oss数据中心', 0, 0, 0),
(20, 'upload', 'alioss_domain', '', '阿里云oss访问域名', 0, 0, 0),
(21, 'upload', 'alioss_bucket', '', '阿里云oss空间名称', 0, 0, 0),
(22, 'upload', 'alioss_access_key_secret', '', '阿里云oss私钥', 0, 0, 0),
(23, 'upload', 'alioss_access_key_id', '', '阿里云oss公钥', 0, 0, 0),
(24, 'translate', 'baidu_secret', '', '百度翻译 secret', 0, 0, 0),
(25, 'translate', 'baidu_appid', '', '百度翻译 appid', 0, 0, 0),
(26, 'sms', 'sms_type', 'mock', '短信类型', 0, 0, 0),
(27, 'sms', 'sms_mock_result', '1', '假性发送结果:0随机;1成功;2失败', 0, 0, 0),
(28, 'sms', 'sms_mock', '1', '是否启用 Mock 功能', 0, 0, 0),
(29, 'sms', 'alisms_signname', '博与科技', '阿里短信签名', 0, 0, 0),
(30, 'sms', 'alisms_num', '0', '阿里云短信发送条数', 0, 0, 0),
(31, 'sms', 'alisms_access_secret', '', '阿里大鱼私钥', 0, 0, 0),
(32, 'sms', 'alisms_access_key', '', '阿里大于公钥', 0, 0, 0),
(33, 'sms', 'alisms', '0', '是否启用阿里云短信功能', 0, 0, 0),
(34, 'sms', 'aliemail_fromalias', '', '阿里邮件发件人名称', 0, 0, 0),
(35, 'sms', 'aliemail_account', '', '阿里邮件发送账号', 0, 0, 0),
(36, 'sms', 'aliemail', '0', '是否启用阿里云邮件功能', 0, 0, 0),
(37, 'site', 'site_version', '1.0.1', '版本信息', 0, 0, 0),
(38, 'site', 'site_name', '博与科技', '站点名称', 0, 0, 0),
(39, 'site', 'site_maintain_time', '', '站点维护时间', 0, 0, 0),
(40, 'site', 'site_maintain', '站点维护中，请稍候再试..', '站点维护公告信息', 0, 0, 0),
(41, 'site', 'site_logo', '', '站点logo图片', 0, 0, 0),
(42, 'site', 'site_ico', '', '浏览器图标', 0, 0, 0),
(43, 'site', 'site_copyright', '©2023 版權所有', '版权信息', 0, 0, 0),
(44, 'site', 'site_beian', '', '备案信息', 0, 0, 0),
(45, 'site', 'seo_keywords', '', 'LOGO标题', 0, 0, 0),
(46, 'site', 'seo_description', '', '站点描述', 0, 0, 0),
(47, 'oauth', 'wechat_work', '', '企业微信 appid', 0, 0, 0),
(48, 'oauth', 'wechat_web_appid', '', 'web 授权 appid', 0, 0, 0),
(49, 'oauth', 'wechat_mini_appid', '', '小程序 appid', 0, 0, 0),
(50, 'oauth', 'wechat_gzh_appid', '', '公众号授权 appid', 0, 0, 0),
(51, 'oauth', 'register', '1', '开启注册', 0, 0, 0),
(52, 'oauth', 'login', '1', '开启普通会员登录', 0, 0, 0),
(53, 'oauth', 'google_redirect_domain', '', '谷歌授权回调域名', 0, 0, 0),
(54, 'oauth', 'google_oauth', '1', '是否启用谷歌授权登录', 0, 0, 0),
(55, 'oauth', 'google_client_secret', '', '谷歌授权 client secrent', 0, 0, 0),
(56, 'oauth', 'google_client_id', '', '谷歌授权 client id', 0, 0, 0),
(57, 'oauth', 'email', '1', '邮箱注册/登录', 0, 0, 0),
(58, 'oauth', 'code_login', '1', '是否支持验证码登录', 0, 0, 0),
(59, 'oauth', 'cn_phone', '1', '+86 中国大陆手机号注册', 0, 0, 0),
(60, 'map', 'gmap_proxy', '', '谷歌地址代理地址', 0, 0, 0),
(61, 'map', 'gmap_key', '', '谷歌地址 key', 0, 0, 0),
(62, 'map', 'gaode_key', '', '高德地图 key', 0, 0, 0),
(63, 'html', 'header', '', '页头共享代码', 0, 0, 0),
(64, 'html', 'footer', 'HELLO', '页底共享代码', 0, 0, 0),
(65, 'contact', 'whatsapp', '', 'whatsapp 账号', 0, 0, 0),
(66, 'contact', 'wework_qrcode', '', '微信企业号客服二维码', 0, 0, 0),
(67, 'contact', 'wework_link', '', '微信企业号客服链接', 0, 0, 0),
(68, 'contact', 'wechat_qrcode', '', '微信公众号二维码', 0, 0, 0),
(69, 'contact', 'wechat_name', '', '微信公众号名称', 0, 0, 0),
(70, 'contact', 'facebook', '', 'facebook 账号', 0, 0, 0),
(71, 'contact', 'contact_tel', '', '联系电话', 0, 0, 0),
(72, 'contact', 'contact_fax', '', '传真号码', 0, 0, 0),
(73, 'contact', 'contact_email', '', '公司电子邮箱', 0, 0, 0),
(74, 'contact', 'contact_address', '潮州市古巷镇', '公司联系地址', 0, 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_log`
--

CREATE TABLE `tao_system_log` (
  `id` int UNSIGNED NOT NULL COMMENT 'ID',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
  `url` varchar(1500) NOT NULL DEFAULT '' COMMENT '操作页面',
  `method` varchar(50) NOT NULL DEFAULT '' COMMENT '请求方法',
  `action` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '操作',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '日志标题',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) NOT NULL DEFAULT '' COMMENT 'User-Agent',
  `created_at` int NOT NULL DEFAULT '0' COMMENT '操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='后台操作日志表';

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_menu`
--

CREATE TABLE `tao_system_menu` (
  `id` bigint UNSIGNED NOT NULL,
  `href` varchar(100) NOT NULL DEFAULT '' COMMENT '链接',
  `params` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '链接参数',
  `sort` int NOT NULL DEFAULT '0' COMMENT '菜单排序',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `type` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '多模块',
  `roles` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '指定访问角色',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `pid` bigint UNSIGNED NOT NULL DEFAULT '0' COMMENT '父id',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '名称',
  `created_at` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_at` int DEFAULT NULL COMMENT '删除时间',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '菜单图标'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统菜单表';

--
-- 转存表中的数据 `tao_system_menu`
--

INSERT INTO `tao_system_menu` (`id`, `href`, `params`, `sort`, `status`, `type`, `roles`, `remark`, `pid`, `title`, `created_at`, `updated_at`, `deleted_at`, `icon`) VALUES
(1, 'tao/index/welcome', '', 0, 1, 2, '', '', 99999999, '后台首页', 1573120497, 1717921623, NULL, 'fa fa-home'),
(2, '', '', 0, 1, 0, '', '', 0, '系统管理', 1588999529, 1717921336, NULL, 'fa fa-cog'),
(3, 'tao/admin.menu', '', 10, 1, 2, '', '', 2, '菜单管理', 1588228555, 1703753213, NULL, 'fa fa-tree'),
(4, 'tao/admin.user', '', 12, 1, 2, '', '', 2, '用户管理', 1573185011, 1588228573, NULL, 'fa fa-user-circle-o '),
(5, 'tao/admin.role', '', 11, 1, 2, '', '', 2, '角色管理', 1573435877, 1703753764, NULL, 'fa fa-users'),
(6, 'tao/admin.node', '', 9, 1, 2, '', '', 2, '节点管理', 1573435919, 1588228648, NULL, 'fa fa-code-fork'),
(7, 'tao/admin.config', '', 8, 1, 2, '', '', 2, '配置管理', 1573457448, 1588228566, NULL, 'fa fa-cogs'),
(8, 'tao.cms/admin.page', '', 0, 1, 2, '', '', 22, '单页管理', 1699707373, 1704786313, NULL, 'fa fa-file-text-o'),
(9, '', '', 0, 1, 0, 'user', '', 0, '会员中心', 1699706505, 1717641997, NULL, 'fa fa-list'),
(10, 'tao/user.uploadfile', '', 0, 1, 2, '', '', 9, '文件管理', 1573542953, 1699779118, NULL, 'fa fa-file-text-o'),
(11, 'tao/user.quick', '', 0, 1, 2, '', '', 9, '快捷入口', 1589623683, 1699779163, NULL, 'layui-icon layui-icon-link'),
(12, 'tao/user.log', '', 0, 1, 2, '', '', 9, '日志管理', 1589623684, 1699779143, NULL, 'layui-icon layui-icon-date'),
(13, '', '', 0, 1, 0, '', '', 0, '公共模块', 1589439884, 1717921377, NULL, 'fa fa-list'),
(14, '', '', 0, 1, 2, '', '', 13, '应用辅助', 1702438745, 1728629302, NULL, 'fa fa-list'),
(15, '', '', 1, 1, 0, '', '', 13, '开放平台', 1702438953, 1728525961, NULL, 'fa fa-list'),
(16, 'tao.app/admin.info', '', 1, 1, 2, '', '', 14, '基本信息', 1697337860, 1728629396, NULL, 'fa fa-info'),
(17, 'tao.open/admin.mch', '', 0, 1, 2, '', '', 15, '商户应用', 1589439910, 1728569073, NULL, 'fa fa-calendar-check-o'),
(18, 'tao.open/admin.order', '', 0, 1, 2, '', '', 15, '订单管理', 1589439931, 1717308620, NULL, 'fa fa-money'),
(19, 'tao.open/admin.app', '', 0, 1, 2, '', '', 15, '应用管理', 1699969090, 1717244870, NULL, 'fa fa-list'),
(20, 'tao.app/admin.feedback', '', 0, 2, 2, '', '', 14, '建议反馈', 1703680828, 1730964523, NULL, 'fa fa-commenting-o'),
(21, 'tao.open/admin.config', '', 0, 1, 2, '', '', 15, '配置管理', 1703747995, 1717245062, NULL, 'fa fa-list'),
(22, '', '', 0, 1, 0, '', '', 13, 'CMS', 1704786287, 1717249421, NULL, 'fa fa-list'),
(23, 'tao.cms/admin.ad', '', 0, 1, 2, 'superAdmin', '', 22, '广告管理', 1704786342, 1704786342, NULL, 'fa fa-list'),
(24, 'tao.cms/admin.album', '', 0, 1, 2, 'superAdmin', '', 22, '图集管理', 1704786367, 1704786551, NULL, 'layui-icon layui-icon-carousel'),
(25, 'tao.cms/admin.article', '', 0, 1, 2, '', '', 22, '文章管理', 1704786540, 1704786582, NULL, 'layui-icon layui-icon-file'),
(26, 'tao.cms/admin.category', '', 0, 1, 2, '', '', 22, '栏目管理', 1704786671, 1704786671, NULL, 'layui-icon layui-icon-template-1'),
(27, 'tao.cms/admin.link', '', 0, 1, 2, '', '', 22, '链接管理', 1704786768, 1704786768, NULL, 'layui-icon layui-icon-link'),
(28, 'tao/a/b/c/d', '', 15, 1, 2, 'superAdmin', '', 0, 'test1733495328', 1733495327, 1733495328, 1733495328, 'fa fa-list');

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_migration`
--

CREATE TABLE `tao_system_migration` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `version` varchar(100) NOT NULL COMMENT '版本',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '升级内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_node`
--

CREATE TABLE `tao_system_node` (
  `id` int UNSIGNED NOT NULL,
  `kind` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '所属模块',
  `node` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '节点代码',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '节点标题',
  `type` tinyint UNSIGNED NOT NULL DEFAULT '3' COMMENT '节点类型',
  `ac` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '变化类型',
  `is_auth` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否启动RBAC权限控制'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统节点表';

--
-- 转存表中的数据 `tao_system_node`
--

INSERT INTO `tao_system_node` (`id`, `kind`, `module`, `node`, `title`, `type`, `ac`, `is_auth`) VALUES
(1, 2, 'tao', 'tao', '系统管理模块', 1, 2, 1),
(2, 2, 'tao', 'tao/admin.config', '配置管理', 2, 1, 1),
(3, 2, 'tao', 'tao/admin.config/save', '保存配置', 3, 1, 1),
(4, 2, 'tao', 'tao/admin.config/reload', '重载缓存', 3, 1, 1),
(5, 2, 'tao', 'tao/admin.config/index', '数据列表', 3, 1, 1),
(6, 2, 'tao', 'tao/admin.menu', '菜单管理', 2, 1, 1),
(7, 2, 'tao', 'tao/admin.menu/add', '添加菜单', 3, 1, 1),
(8, 2, 'tao', 'tao/admin.menu/edit', '编辑菜单', 3, 1, 1),
(9, 2, 'tao', 'tao/admin.menu/index', '数据列表', 3, 1, 1),
(10, 2, 'tao', 'tao/admin.menu/modify', '属性快捷修改', 3, 1, 1),
(11, 2, 'tao', 'tao/admin.menu/delete', '删除记录', 3, 1, 1),
(12, 2, 'tao', 'tao/admin.node', '节点管理', 2, 1, 1),
(13, 2, 'tao', 'tao/admin.node/reload', '更新节点', 3, 1, 1),
(14, 2, 'tao', 'tao/admin.node/index', '数据列表', 3, 1, 1),
(15, 2, 'tao', 'tao/admin.node/modify', '属性快捷修改', 3, 1, 1),
(16, 2, 'tao', 'tao/admin.role', '角色管理', 2, 1, 1),
(17, 2, 'tao', 'tao/admin.role/edit', '编辑角色', 3, 1, 1),
(18, 2, 'tao', 'tao/admin.role/add', '添加角色', 3, 1, 1),
(19, 2, 'tao', 'tao/admin.role/authorize', '角色授权', 3, 1, 1),
(20, 2, 'tao', 'tao/admin.role/index', '数据列表', 3, 1, 1),
(21, 2, 'tao', 'tao/admin.role/modify', '属性快捷修改', 3, 1, 1),
(22, 2, 'tao', 'tao/admin.role/delete', '删除记录', 3, 1, 1),
(23, 2, 'tao', 'tao/admin.user', '用户管理', 2, 1, 1),
(24, 2, 'tao', 'tao/admin.user/add', '添加用户', 3, 1, 1),
(25, 2, 'tao', 'tao/admin.user/edit', '编辑用户', 3, 1, 1),
(26, 2, 'tao', 'tao/admin.user/password', '修改用户密码', 3, 1, 1),
(27, 2, 'tao', 'tao/admin.user/index', '数据列表', 3, 1, 1),
(28, 2, 'tao', 'tao/admin.user/modify', '属性快捷修改', 3, 1, 1),
(29, 2, 'tao', 'tao/admin.user/delete', '删除记录', 3, 1, 1),
(92, 2, 'tao', 'tao.cms/admin.ad', '广告管理', 2, 1, 1),
(93, 2, 'tao', 'tao.cms/admin.ad/add', '添加广告', 3, 1, 1),
(94, 2, 'tao', 'tao.cms/admin.ad/edit', 'edit', 3, 1, 1),
(95, 2, 'tao', 'tao.cms/admin.ad/index', '数据列表', 3, 1, 1),
(96, 2, 'tao', 'tao.cms/admin.ad/modify', '属性快捷修改', 3, 1, 1),
(97, 2, 'tao', 'tao.cms/admin.ad/delete', '删除记录', 3, 1, 1),
(98, 2, 'tao', 'tao.cms/admin.album', '图集管理', 2, 1, 1),
(99, 2, 'tao', 'tao.cms/admin.album/edit', '修改图集', 3, 1, 1),
(100, 2, 'tao', 'tao.cms/admin.album/preview', '图集预览', 3, 1, 1),
(101, 2, 'tao', 'tao.cms/admin.album/index', '数据列表', 3, 1, 1),
(102, 2, 'tao', 'tao.cms/admin.album/add', '添加记录', 3, 1, 1),
(103, 2, 'tao', 'tao.cms/admin.album/modify', '属性快捷修改', 3, 1, 1),
(104, 2, 'tao', 'tao.cms/admin.album/delete', '删除记录', 3, 1, 1),
(105, 2, 'tao', 'tao.cms/admin.article', '文章管理', 2, 1, 1),
(106, 2, 'tao', 'tao.cms/admin.article/index', '文章列表', 3, 1, 1),
(107, 2, 'tao', 'tao.cms/admin.article/add', '添加文章', 3, 1, 1),
(108, 2, 'tao', 'tao.cms/admin.article/edit', '编辑文章', 3, 1, 1),
(109, 2, 'tao', 'tao.cms/admin.article/cstatus', '文章审核', 3, 1, 1),
(110, 2, 'tao', 'tao.cms/admin.article/preview', '文章预览', 3, 1, 1),
(111, 2, 'tao', 'tao.cms/admin.article/modify', '属性快捷修改', 3, 1, 1),
(112, 2, 'tao', 'tao.cms/admin.article/delete', '删除记录', 3, 1, 1),
(113, 2, 'tao', 'tao.cms/admin.category', '栏目管理', 2, 1, 1),
(114, 2, 'tao', 'tao.cms/admin.category/add', '添加栏目', 3, 1, 1),
(115, 2, 'tao', 'tao.cms/admin.category/edit', '修改栏目', 3, 1, 1),
(116, 2, 'tao', 'tao.cms/admin.category/index', '数据列表', 3, 1, 1),
(117, 2, 'tao', 'tao.cms/admin.category/modify', '属性快捷修改', 3, 1, 1),
(118, 2, 'tao', 'tao.cms/admin.category/delete', '删除记录', 3, 1, 1),
(119, 2, 'tao', 'tao.cms/admin.link', '链接管理', 2, 1, 1),
(120, 2, 'tao', 'tao.cms/admin.link/index', '数据列表', 3, 1, 1),
(121, 2, 'tao', 'tao.cms/admin.link/add', '添加记录', 3, 1, 1),
(122, 2, 'tao', 'tao.cms/admin.link/edit', '编辑记录', 3, 1, 1),
(123, 2, 'tao', 'tao.cms/admin.link/modify', '属性快捷修改', 3, 1, 1),
(124, 2, 'tao', 'tao.cms/admin.link/delete', '删除记录', 3, 1, 1),
(125, 2, 'tao', 'tao.cms/admin.page', '单页管理', 2, 1, 1),
(126, 2, 'tao', 'tao.cms/admin.page/add', '添加单页', 3, 1, 1),
(127, 2, 'tao', 'tao.cms/admin.page/edit', '编辑单页', 3, 1, 1),
(128, 2, 'tao', 'tao.cms/admin.page/index', '数据列表', 3, 1, 1),
(129, 2, 'tao', 'tao.cms/admin.page/modify', '属性快捷修改', 3, 1, 1),
(130, 2, 'tao', 'tao.cms/admin.page/delete', '删除记录', 3, 1, 1),
(131, 2, 'tao', 'tao.open/admin.app', '开放平台应用管理', 2, 1, 1),
(132, 2, 'tao', 'tao.open/admin.app/cert', '修改证书', 3, 1, 1),
(133, 2, 'tao', 'tao.open/admin.app/index', '数据列表', 3, 1, 1),
(134, 2, 'tao', 'tao.open/admin.app/add', '添加记录', 3, 1, 1),
(135, 2, 'tao', 'tao.open/admin.app/edit', '编辑记录', 3, 1, 1),
(136, 2, 'tao', 'tao.open/admin.app/modify', '属性快捷修改', 3, 1, 1),
(137, 2, 'tao', 'tao.open/admin.app/delete', '删除记录', 3, 1, 1),
(138, 2, 'tao', 'tao.open/admin.config', '开放平台配置', 2, 1, 1),
(139, 2, 'tao', 'tao.open/admin.config/index', '公共配置', 3, 1, 1),
(140, 2, 'tao', 'tao.open/admin.config/add', '添加记录', 3, 1, 1),
(141, 2, 'tao', 'tao.open/admin.config/edit', '编辑记录', 3, 1, 1),
(142, 2, 'tao', 'tao.open/admin.config/modify', '属性快捷修改', 3, 1, 1),
(143, 2, 'tao', 'tao.open/admin.config/delete', '删除记录', 3, 1, 1),
(144, 2, 'tao', 'tao.open/admin.mch', '商户应用', 2, 1, 1),
(145, 2, 'tao', 'tao.open/admin.mch/cert', '上传证书', 3, 1, 1),
(146, 2, 'tao', 'tao.open/admin.mch/index', '数据列表', 3, 1, 1),
(147, 2, 'tao', 'tao.open/admin.mch/add', '添加记录', 3, 1, 1),
(148, 2, 'tao', 'tao.open/admin.mch/edit', '编辑记录', 3, 1, 1),
(149, 2, 'tao', 'tao.open/admin.mch/modify', '属性快捷修改', 3, 1, 1),
(150, 2, 'tao', 'tao.open/admin.mch/delete', '删除记录', 3, 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_quick`
--

CREATE TABLE `tao_system_quick` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '快捷入口名称',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `href` varchar(255) NOT NULL DEFAULT '' COMMENT '快捷链接',
  `sort` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态(0禁用,1启用)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注说明',
  `created_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_at` int DEFAULT NULL COMMENT '删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统快捷入口表';

--
-- 转存表中的数据 `tao_system_quick`
--

INSERT INTO `tao_system_quick` (`id`, `user_id`, `title`, `icon`, `href`, `sort`, `status`, `remark`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '测试快捷链接', '', '/m/tao/user.quick/edit', 1, 1, 'time.1733495345', 1733495344, 1733495346, 1733495346);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_role`
--

CREATE TABLE `tao_system_role` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(15) NOT NULL DEFAULT '' COMMENT '名称',
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '权限名称',
  `sort` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注说明',
  `created_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_at` int DEFAULT NULL COMMENT '删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统权限表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `tao_system_role`
--

INSERT INTO `tao_system_role` (`id`, `name`, `title`, `sort`, `status`, `remark`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'test1733495332', '测试.1733495332', 0, 1, '123', 1733495332, 1733495335, 1733495335);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_role_node`
--

CREATE TABLE `tao_system_role_node` (
  `id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL DEFAULT '0' COMMENT '角色ID',
  `node_id` bigint NOT NULL DEFAULT '0' COMMENT '节点ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='角色与节点关系表';

--
-- 转存表中的数据 `tao_system_role_node`
--

INSERT INTO `tao_system_role_node` (`id`, `role_id`, `node_id`) VALUES
(1, 1, 2),
(2, 1, 3),
(3, 1, 6),
(4, 1, 7);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_sms_code`
--

CREATE TABLE `tao_system_sms_code` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户 ID',
  `kind` varchar(20) NOT NULL DEFAULT '' COMMENT '短信/邮件类型',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '校验状态',
  `num` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '比较次数',
  `send_engine` varchar(15) NOT NULL DEFAULT '' COMMENT '发送引擎',
  `send_status` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '0待发送1成功2失败',
  `send_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '发送时间',
  `receiver` varchar(30) NOT NULL DEFAULT '' COMMENT '接收账号',
  `receiver_kind` tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT '1手机2邮箱',
  `code` varchar(10) NOT NULL DEFAULT '' COMMENT '验证码',
  `data` varchar(150) NOT NULL DEFAULT '' COMMENT '额外数据',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='验证码';

--
-- 转存表中的数据 `tao_system_sms_code`
--

INSERT INTO `tao_system_sms_code` (`id`, `user_id`, `kind`, `status`, `num`, `send_engine`, `send_status`, `send_at`, `receiver`, `receiver_kind`, `code`, `data`, `ip`, `created_at`, `updated_at`) VALUES
(1, 1, 'forgot', 1, 0, 'mock_email', 1, 1733495322, 'admin@test.com', 2, '3435', '', '192.168.128.2', 1733495322, 1733495322);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_uploadfile`
--

CREATE TABLE `tao_system_uploadfile` (
  `id` int UNSIGNED NOT NULL COMMENT 'ID',
  `user_id` int UNSIGNED NOT NULL DEFAULT '0',
  `upload_type` varchar(20) NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '文件原名',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '路径',
  `width` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '宽度',
  `height` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '高度',
  `frames` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `mime_type` varchar(100) NOT NULL DEFAULT '' COMMENT 'mime类型',
  `file_size` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '文件大小',
  `file_ext` varchar(100) NOT NULL DEFAULT '',
  `sha1` varchar(40) NOT NULL DEFAULT '' COMMENT '文件 sha1编码',
  `created_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建日期',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='上传文件表';

--
-- 转存表中的数据 `tao_system_uploadfile`
--

INSERT INTO `tao_system_uploadfile` (`id`, `user_id`, `upload_type`, `summary`, `url`, `width`, `height`, `frames`, `mime_type`, `file_size`, `file_ext`, `sha1`, `created_at`, `updated_at`) VALUES
(1, 1, 'qnoss', 'a0.jpg', 'http://assets.emm365.com/app/29092e50ef94c9fcf26a060819311abd.jpg', 300, 300, 0, 'image/jpeg', 30569, 'jpg', '29092e50ef94c9fcf26a060819311abd', 1733473867, 1733473867);

-- --------------------------------------------------------

--
-- 表的结构 `tao_system_user`
--

CREATE TABLE `tao_system_user` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` int NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_at` int NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deleted_at` int DEFAULT NULL COMMENT '删除时间',
  `status` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用,)',
  `role_ids` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '角色权限ID',
  `seed` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '随机数',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户登录密码',
  `email` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `email_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '邮箱修改时间',
  `email_valid` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `phone` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '联系手机号',
  `phone_at` int NOT NULL DEFAULT '0' COMMENT '手机号修改时间',
  `phone_valid` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `nickname` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `head_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '头像',
  `signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '签名',
  `binds` varchar(255) NOT NULL DEFAULT '[]' COMMENT '绑定账号',
  `puid` char(30) NOT NULL DEFAULT '' COMMENT '多平台 UID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统用户表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `tao_system_user`
--

INSERT INTO `tao_system_user` (`id`, `created_at`, `updated_at`, `deleted_at`, `status`, `role_ids`, `seed`, `password`, `email`, `email_at`, `email_valid`, `phone`, `phone_at`, `phone_valid`, `nickname`, `head_img`, `signature`, `binds`, `puid`) VALUES
(1, 1627357963, 1733495342, NULL, 1, '', '670039', '$2y$10$HC8xtGpZ61JJcvu/jCnjku2wEzHmDgKO5/LQ.SP.dxhFhMbktjCKe', 'admin@test.com', 0, 1, '13445678901', 1726839409, 1, 'admin996', '', 'HELLO WORLD', '[]', 'ec081a98dca9ee0396ba00c676131f'),
(1000, 1733495319, 1733495319, NULL, 1, '', '123456', '$2y$10$f/RevlQzln1D6vkQZvMI2.DEQEXKj1.LG0D8hlL2IWJzCxRIfgl3y', 'phax-1000@unit.test', 0, 1, '17334953190', 0, 1, '', '', 'unit test account', '[]', '87e1026f3d69798ea69c22788d11ed'),
(1001, 1733495337, 1733495339, 1733495339, 1, '', 'nFu1aDaQ', '$2y$10$6cEW.57mj3i8czSuor63/.TDuddoFx0//nmd79/kBp8.XEdYUHvMG', '1733495336@test.com', 0, 1, '', 0, 0, 'test-1733495336', '', 'mysign.1733495337', '[]', '290a49db6422a3c39df8ecfcc68fb2');

-- --------------------------------------------------------

--
-- 表的结构 `tao_wechat_menu`
--

CREATE TABLE `tao_wechat_menu` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `sync` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '是否需要同步',
  `sync_at` int UNSIGNED NOT NULL DEFAULT '0' COMMENT '同步时间',
  `appid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '微信 appID',
  `content` text NOT NULL COMMENT '菜单内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `tao_app_info`
--
ALTER TABLE `tao_app_info`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_ad`
--
ALTER TABLE `tao_cms_ad`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_album`
--
ALTER TABLE `tao_cms_album`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_article`
--
ALTER TABLE `tao_cms_article`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_category`
--
ALTER TABLE `tao_cms_category`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_content`
--
ALTER TABLE `tao_cms_content`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_link`
--
ALTER TABLE `tao_cms_link`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_cms_page`
--
ALTER TABLE `tao_cms_page`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_open_app`
--
ALTER TABLE `tao_open_app`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_open_config`
--
ALTER TABLE `tao_open_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 表的索引 `tao_open_mch`
--
ALTER TABLE `tao_open_mch`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_open_order`
--
ALTER TABLE `tao_open_order`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_open_user_openid`
--
ALTER TABLE `tao_open_user_openid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_id` (`appid`,`openid`);

--
-- 表的索引 `tao_open_user_unionid`
--
ALTER TABLE `tao_open_user_unionid`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unionid` (`unionid`);

--
-- 表的索引 `tao_system_config`
--
ALTER TABLE `tao_system_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_system_log`
--
ALTER TABLE `tao_system_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tao_system_menu`
--
ALTER TABLE `tao_system_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `title` (`title`),
  ADD KEY `href` (`href`);

--
-- 表的索引 `tao_system_migration`
--
ALTER TABLE `tao_system_migration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `version` (`version`);

--
-- 表的索引 `tao_system_node`
--
ALTER TABLE `tao_system_node`
  ADD PRIMARY KEY (`id`),
  ADD KEY `node` (`node`) USING BTREE;

--
-- 表的索引 `tao_system_quick`
--
ALTER TABLE `tao_system_quick`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `tao_system_role`
--
ALTER TABLE `tao_system_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`) USING BTREE;

--
-- 表的索引 `tao_system_role_node`
--
ALTER TABLE `tao_system_role_node`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_system_auth_auth` (`role_id`) USING BTREE,
  ADD KEY `index_system_auth_node` (`node_id`) USING BTREE;

--
-- 表的索引 `tao_system_sms_code`
--
ALTER TABLE `tao_system_sms_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recever` (`kind`,`receiver`);

--
-- 表的索引 `tao_system_uploadfile`
--
ALTER TABLE `tao_system_uploadfile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `tao_system_user`
--
ALTER TABLE `tao_system_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`),
  ADD KEY `email` (`email`);

--
-- 表的索引 `tao_wechat_menu`
--
ALTER TABLE `tao_wechat_menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_id` (`appid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `tao_app_info`
--
ALTER TABLE `tao_app_info`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_cms_ad`
--
ALTER TABLE `tao_cms_ad`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_cms_album`
--
ALTER TABLE `tao_cms_album`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_cms_article`
--
ALTER TABLE `tao_cms_article`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tao_cms_category`
--
ALTER TABLE `tao_cms_category`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_cms_content`
--
ALTER TABLE `tao_cms_content`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `tao_cms_link`
--
ALTER TABLE `tao_cms_link`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_cms_page`
--
ALTER TABLE `tao_cms_page`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `tao_open_app`
--
ALTER TABLE `tao_open_app`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_open_config`
--
ALTER TABLE `tao_open_config`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `tao_open_mch`
--
ALTER TABLE `tao_open_mch`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_open_order`
--
ALTER TABLE `tao_open_order`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_open_user_openid`
--
ALTER TABLE `tao_open_user_openid`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tao_open_user_unionid`
--
ALTER TABLE `tao_open_user_unionid`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tao_system_config`
--
ALTER TABLE `tao_system_config`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- 使用表AUTO_INCREMENT `tao_system_log`
--
ALTER TABLE `tao_system_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `tao_system_menu`
--
ALTER TABLE `tao_system_menu`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用表AUTO_INCREMENT `tao_system_migration`
--
ALTER TABLE `tao_system_migration`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tao_system_node`
--
ALTER TABLE `tao_system_node`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- 使用表AUTO_INCREMENT `tao_system_quick`
--
ALTER TABLE `tao_system_quick`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_system_role`
--
ALTER TABLE `tao_system_role`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_system_role_node`
--
ALTER TABLE `tao_system_role_node`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `tao_system_sms_code`
--
ALTER TABLE `tao_system_sms_code`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_system_uploadfile`
--
ALTER TABLE `tao_system_uploadfile`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `tao_system_user`
--
ALTER TABLE `tao_system_user`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1002;

--
-- 使用表AUTO_INCREMENT `tao_wechat_menu`
--
ALTER TABLE `tao_wechat_menu`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
