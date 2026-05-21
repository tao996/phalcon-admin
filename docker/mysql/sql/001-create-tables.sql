SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `demo_article` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_article` (`id`, `user_id`, `title`) VALUES
(1, 1, '你好 PHP'),
(2, 1, '你好 ThinkPHP'),
(3, 1, 'Bi1'),
(4, 1, 'Bi2'),
(5, 2, '你好 laravel'),
(6, 2, '你好 tailwindcss'),
(7, 2, '你好 golang'),
(8, 3, '你好 Net'),
(9, 3, '你好 ZhiHu'),
(10, 3, '你好 Miscrsoft'),
(11, 2, '你好 Google'),
(12, 2, '你好 Taobao'),
(13, 2, '你好 layui');

CREATE TABLE `demo_article_migrate` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_article_migrate` (`id`, `user_id`, `title`) VALUES
(1, 1, 'test for db.migrate 中文');

CREATE TABLE `demo_cat` (
  `id` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `age` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_cat` (`id`, `created_at`, `updated_at`, `deleted_at`, `name`, `title`, `age`) VALUES
(1, '2023-10-01 09:54:26', '2024-10-31 16:09:22', NULL, 'gray', '小灰', 324),
(2, '2023-10-01 09:56:12', '2024-10-31 16:09:22', NULL, 'gray', '小灰', 208),
(3, '2023-10-01 09:57:35', '2023-10-01 09:59:08', '2023-10-01 09:59:08', 'gray', '小灰', 70),
(4, '2023-10-18 08:56:45', '2023-10-18 08:56:45', NULL, 'gray', '小灰', 69),
(5, '2024-10-02 11:36:28', '2024-10-02 11:36:28', NULL, 'gray', '小灰', 8),
(6, '2024-10-02 11:39:53', '2024-10-02 11:39:53', NULL, 'gray', '小灰', 48),
(7, '2024-10-02 11:44:21', '2024-10-02 11:44:21', NULL, '', '红K', 0),
(8, '2024-11-01 01:04:04', '2024-11-01 01:04:04', NULL, 'gray', '小灰', 10);

CREATE TABLE `demo_profile` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `age` int UNSIGNED NOT NULL,
  `remark` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_profile` (`id`, `user_id`, `age`, `remark`) VALUES
(1, 1, 15, 'demo test'),
(2, 2, 35, ''),
(3, 4, 56, '');

CREATE TABLE `demo_role` (
  `id` int NOT NULL,
  `title` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_role` (`id`, `title`) VALUES
(1, '测试管理员'),
(2, '管理员'),
(3, '会员'),
(4, '商户'),
(5, '普通会员'),
(6, '测试管理员1'),
(7, '测试管理员2');

CREATE TABLE `demo_user` (
  `id` int NOT NULL,
  `title` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_user` (`id`, `title`, `email`) VALUES
(1, '小高', '123@qq.com'),
(2, 'a', 'a@dd.com'),
(3, 'b', 'b@dd.com');

CREATE TABLE `demo_user_role` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `demo_user_role` (`user_id`, `role_id`) VALUES
(2, 2),
(3, 2),
(1, 1),
(1, 2),
(1, 3),
(1, 4);

CREATE TABLE `test_user` (
  `id` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` int UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` int UNSIGNED DEFAULT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `age` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `demo_article`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `demo_article_migrate`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `demo_cat`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `demo_profile`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `demo_role`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `demo_user`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `test_user`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `demo_article`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `demo_article_migrate`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `demo_cat`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `demo_profile`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `demo_role`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `demo_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `test_user`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
