-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2025 at 11:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `applies_to_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--

INSERT INTO `addons` (`id`, `name`, `description`, `price`, `applies_to_category`) VALUES
(1, 'Mee / Mee Hoon / Kuey Teow', '', 2.00, 'Signature Soups'),
(2, 'Nasi Putih + Telur Dadar + Sambal Belacan + Ulam', '', 5.00, 'Signature Soups'),
(3, 'Roti Francis / Gardenia', '', 2.50, 'Signature Soups');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` varchar(128) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@suptulangzz.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Restaurant Admin', 'admin', 1, NULL, '2025-06-26 12:29:29', '2025-06-26 12:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category`, `image_url`) VALUES
(1, 'Sup Gearbox Kambing', '', 19.00, 'Sup ZZ', 'http://localhost/restaurant-order-system/menu_images/Sup_kepala_kambing.jpg'),
(2, 'Sup Kambing', '', 20.00, 'Sup ZZ', 'http://localhost/restaurant-order-system/menu_images/Sup_kepala_kambing.jpg'),
(3, 'Sup Daging', '', 8.00, 'Sup ZZ', 'http://localhost/restaurant-order-system/menu_images/supdaging.jpg'),
(4, 'Sup Ayam', '', 7.00, 'Sup ZZ', 'http://localhost/restaurant-order-system/menu_images/Sup_Ayam_Sedap_Web.jpg'),
(8, 'Mee Rebus Gearbox Kambing', '', 20.00, 'Mee Rebus ZZ', 'http://localhost/restaurant-order-system/menu_images/maxresdefault.jpg'),
(9, 'Mee Rebus Daging', '', 9.50, 'Mee Rebus ZZ', 'http://localhost/restaurant-order-system/menu_images/mrd.jpg'),
(10, 'Mee Rebus Ayam', '', 9.00, 'Mee Rebus ZZ', 'http://localhost/restaurant-order-system/menu_images/sddefault.jpg'),
(11, 'Roti Bakar', '', 2.50, 'Roti Bakar', 'http://localhost/restaurant-order-system/menu_images/cropped-Roti-Bakar.jpg'),
(12, 'Roti Kaya', '', 3.50, 'Roti Bakar', 'http://localhost/restaurant-order-system/menu_images/gif4-listing.jpg'),
(13, 'Roti Garlic', '', 3.50, 'Roti Bakar', 'http://localhost/restaurant-order-system/menu_images/p2-scaled.jpg'),
(14, 'Add-On Telur 1/2 Masak', '', 3.50, 'Roti Bakar', 'http://localhost/restaurant-order-system/menu_images/vtzmfMrM-digitorial-29.jpg'),
(15, 'Lontong Kuah', '', 7.50, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/kuah-lontong-sayur-resipi-foto-utama.jpg'),
(16, 'Lontong Kering (Ayam/Daging)', '', 9.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/10879595845_0ac6cae6b5_b.jpg'),
(17, 'Nasi Lemak Basmathi (Telur / Ayam)', '', 6.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/owws-listing.jpg'),
(18, 'Nasi Lemak Rendang (Ayam/Daging)', '', 8.50, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/sddefault (1).jpg'),
(19, 'Nasi Ayam Basmathi', '', 12.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/186511822_2904419763208637_4516486710897739432_n.jpg'),
(20, 'Nasi Ambang', '', 9.50, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/Nasi_Ambeng.jpg'),
(21, 'Bubur Nasi', '', 7.50, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/Meaty-sweet-potato-porridge-2.jpg'),
(22, 'Bubur Ayam', '', 7.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/Meaty-sweet-potato-porridge-2.jpg'),
(23, 'Laksa (Johor / Penang)', '', 8.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/12725108_150308635354687_532477720_n.jpg'),
(24, 'Bakso', '', 7.50, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/Bakso-.jpg'),
(25, 'Soto', '', 8.00, 'Sarapan (Breakfast)', 'http://localhost/restaurant-order-system/menu_images/soto-ayam-thumb.jpg'),
(26, 'Roti Kosong', '', 1.50, 'Roti Canai', 'http://localhost/restaurant-order-system/menu_images/Roti-Canai-3.jpg'),
(27, 'Roti Kosong Bawang', '', 2.00, 'Roti Canai', 'http://localhost/restaurant-order-system/menu_images/aa877ab3.jpg'),
(28, 'Roti Tampal', '', 2.80, 'Roti Canai', 'http://localhost/restaurant-order-system/menu_images/16-2.jpg'),
(29, 'Roti Telur', '', 2.80, 'Roti Canai', NULL),
(30, 'Roti Telur Bawang', '', 3.50, 'Roti Canai', NULL),
(31, 'Roti Telur Double Jantan', '', 5.50, 'Roti Canai', NULL),
(32, 'Roti Pisang', '', 4.50, 'Roti Canai', NULL),
(33, 'Roti Sardin', '', 6.00, 'Roti Canai', NULL),
(34, 'Roti Bom', '', 2.50, 'Roti Canai', NULL),
(35, 'Roti Planta', '', 3.00, 'Roti Canai', NULL),
(36, 'Roti Sarang Burung Daging', '', 8.00, 'Roti Canai', NULL),
(37, 'Nasi Bawal Goreng Berlado', '', 9.00, 'Set Nasi & Lauk', NULL),
(38, 'Nasi Siakap Goreng Berlado', '', 15.00, 'Set Nasi & Lauk', NULL),
(39, 'Nasi Keli Goreng Berlado', '', 10.90, 'Set Nasi & Lauk', NULL),
(40, 'Nasi Ayam Goreng Berlado', '', 8.50, 'Set Nasi & Lauk', NULL),
(41, 'Siakap Bakar', '', 35.00, 'Ikan Siakap & Bakar-Bakar', NULL),
(42, 'Caru Bakar', '', 8.00, 'Ikan Siakap & Bakar-Bakar', NULL),
(43, 'Kerang Bakar', '', 15.00, 'Ikan Siakap & Bakar-Bakar', NULL),
(44, 'Sotong Bakar', '', 15.00, 'Ikan Siakap & Bakar-Bakar', NULL),
(45, 'Tiga Rasa', '', 35.00, 'Menu Ikan', NULL),
(46, 'Masam Manis', '', 35.00, 'Menu Ikan', NULL),
(47, 'Steam Lemon', '', 35.00, 'Menu Ikan', NULL),
(48, 'Laprik', '', 35.00, 'Menu Ikan', NULL),
(49, 'Goreng Kunyit', '', 35.00, 'Menu Ikan', NULL),
(50, 'Kailan (Biasa / Ikan Masin)', '', 7.00, 'Sayur', NULL),
(51, 'Kangkung (Biasa / Belacan)', '', 7.00, 'Sayur', NULL),
(52, 'Taugeh (Biasa / Ikan Masin)', '', 7.00, 'Sayur', NULL),
(53, 'Sawi (Biasa / Ikan Masin)', '', 7.00, 'Sayur', NULL),
(54, 'Cendawan Goreng Biasa', '', 7.00, 'Sayur', NULL),
(55, 'Aneka Lauk Thai', 'Includes variations of: Ayam, Daging, Sotong in Black Pepper / Sambal / Merah / Paprik / Pha Khra Phao / Kunyit', 7.50, 'Aneka Lauk Thai', NULL),
(56, 'Udang Kunyit', '', 9.50, 'Aneka Lauk Thai', NULL),
(57, 'Add-On Nasi Putih', '', 2.00, 'Aneka Lauk Thai', NULL),
(58, 'Add-On Nasi Goreng', '', 3.00, 'Aneka Lauk Thai', NULL),
(59, 'Sotong', '', 10.50, 'Goreng Tepung', NULL),
(60, 'Udang', '', 10.50, 'Goreng Tepung', NULL),
(61, 'Cendawan', '', 7.00, 'Goreng Tepung', NULL),
(62, 'Inokki', '', 7.00, 'Goreng Tepung', NULL),
(63, 'Sup Ayam Ala Thai', '', 8.00, 'Sup Ala Thai', NULL),
(64, 'Sup Daging Ala Thai', '', 9.00, 'Sup Ala Thai', NULL),
(65, 'Add-On Mee/Mee Hoon/Kuey Teow', '', 2.00, 'Sup Ala Thai', NULL),
(66, 'Bandung', '', 10.50, 'Mee Kuah', NULL),
(67, 'Hong Kong', '', 10.50, 'Mee Kuah', NULL),
(68, 'Hailam', '', 10.50, 'Mee Kuah', NULL),
(69, 'Kung Fu', '', 10.50, 'Mee Kuah', NULL),
(70, 'Tomyam Ayam', '', 8.00, 'Tomyam', NULL),
(71, 'Tomyam Daging', '', 9.00, 'Tomyam', NULL),
(72, 'Tomyam Ayam + Daging', '', 12.00, 'Tomyam', NULL),
(73, 'Tomyam Seafood', '', 13.00, 'Tomyam', NULL),
(74, 'Tomyam Campur', '', 13.00, 'Tomyam', NULL),
(75, 'Tomyam Sayur / Cendawan', '', 8.00, 'Tomyam', NULL),
(76, 'Add-On Mee/Mee Hoon/Kuey Teow', '', 2.00, 'Tomyam', NULL),
(77, 'Chicken Chop (Fried / Grill)', '', 18.50, 'Western Food', NULL),
(78, 'Fish N Chips', '', 16.50, 'Western Food', NULL),
(79, 'Lamb Chop', '', 30.90, 'Western Food', NULL),
(80, 'Aglio Olio (Seafood)', '', 17.00, 'Spaghetti', NULL),
(81, 'Aglio Olio (Beef Bacon)', '', 15.00, 'Spaghetti', NULL),
(82, 'Aglio Olio (Chicken)', '', 13.00, 'Spaghetti', NULL),
(83, 'Carbonara (Seafood)', '', 18.00, 'Spaghetti', NULL),
(84, 'Carbonara (Beef Bacon)', '', 16.00, 'Spaghetti', NULL),
(85, 'Carbonara (Chicken)', '', 14.00, 'Spaghetti', NULL),
(86, 'Bolognese', '', 15.00, 'Spaghetti', NULL),
(87, 'Mac & Cheese', '', 0.00, 'Spaghetti', NULL),
(88, 'Smash Beef (Single)', '', 8.00, 'Burger', NULL),
(89, 'Smash Beef (Double)', '', 10.00, 'Burger', NULL),
(90, 'Crispy Chicken Burger', '', 7.50, 'Burger', NULL),
(91, 'Add-On Fries', '', 2.00, 'Burger', NULL),
(92, 'Fries', '', 7.50, 'Sides', NULL),
(93, 'Nugget (8 pcs)', '', 8.00, 'Sides', NULL),
(94, 'Cheesy Wedges', '', 8.50, 'Sides', NULL),
(95, 'Nasi Goreng Biasa', '', 7.50, 'Goreng-Goreng', NULL),
(96, 'Nasi Goreng Kampung', '', 8.00, 'Goreng-Goreng', NULL),
(97, 'Nasi Goreng Cina', '', 7.50, 'Goreng-Goreng', NULL),
(98, 'Nasi Goreng Ikan Masin', '', 8.50, 'Goreng-Goreng', NULL),
(99, 'Nasi Goreng Cili Padi', '', 8.50, 'Goreng-Goreng', NULL),
(100, 'Nasi Goreng Pattaya', '', 8.50, 'Goreng-Goreng', NULL),
(101, 'Nasi Goreng Tomyam', '', 9.00, 'Goreng-Goreng', NULL),
(102, 'Nasi Goreng Belacan', '', 12.00, 'Goreng-Goreng', NULL),
(103, 'Mee Goreng', '', 7.50, 'Goreng-Goreng', NULL),
(104, 'Mee Hoon Goreng Singapore', '', 7.50, 'Goreng-Goreng', NULL),
(105, 'Char Kuey Teow', '', 8.00, 'Goreng-Goreng', NULL),
(106, 'Teh O’ Hot', '', 2.30, 'Drinks', NULL),
(107, 'Teh O’ Cold', '', 2.50, 'Drinks', NULL),
(108, 'Teh Tarik Hot', '', 2.50, 'Drinks', NULL),
(109, 'Teh Tarik Cold', '', 3.00, 'Drinks', NULL),
(110, 'Teh Halia Hot', '', 3.50, 'Drinks', NULL),
(111, 'Teh Halia Cold', '', 4.00, 'Drinks', NULL),
(112, 'Teh Sarbat, Sirap, Sirap Limau, Sirap Laici, Sirap Bandung, Sirap Bandung Cincau, Sirap Bandung Soda', '', 2.00, 'Drinks', NULL),
(113, 'Indo Cafe O’ Hot', '', 2.70, 'Drinks', NULL),
(114, 'Indo Cafe Susu Hot', '', 2.70, 'Drinks', NULL),
(115, 'Indo Cafe O’ Cold', '', 3.00, 'Drinks', NULL),
(116, 'Indo Cafe Susu Cold', '', 3.00, 'Drinks', NULL),
(117, 'Kopi Tenggek Hot', '', 2.70, 'Drinks', NULL),
(118, 'Kopi Tenggek Cold', '', 3.00, 'Drinks', NULL),
(119, 'Kopi Special Hot', '', 3.00, 'Drinks', NULL),
(120, 'Kopi Special Cold', '', 3.50, 'Drinks', NULL),
(121, 'Orange Juice Cold', '', 4.70, 'Drinks', NULL),
(122, 'Apple Juice Cold', '', 5.00, 'Drinks', NULL),
(123, 'Watermelon Juice Cold', '', 5.00, 'Drinks', NULL),
(124, 'Lychee Juice Cold', '', 5.00, 'Drinks', NULL),
(125, 'Lemon Juice Cold', '', 5.00, 'Drinks', NULL),
(126, 'Cold Desserts (Cikong)', '', 6.00, 'Drinks', NULL),
(127, 'Cold Desserts (Ais Jelly Limau)', '', 6.00, 'Drinks', NULL),
(128, 'Cold Desserts (Cendol)', '', 6.00, 'Drinks', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `order_time` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `order_method` enum('walk-in','online') NOT NULL DEFAULT 'walk-in',
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `order_type` enum('delivery','pickup') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `table_number`, `order_time`, `status`, `order_method`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `order_type`) VALUES
(1, 12, '2025-06-26 19:11:06', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(2, 0, '2025-06-26 19:40:26', 'Pending', 'online', 'IZZRAFIQ BIN ZULFIKAR HARDI', 'izzrafiq12@gmail.com', '01169566961', 'pt 363,kedai melor', 'delivery'),
(3, 0, '2025-06-26 19:42:35', 'Pending', 'online', 'IZZRAFIQ BIN ZULFIKAR HARDI', 'izzrafiq12@gmail.com', '01169566961', 'pt 363,kedai melor', 'delivery'),
(4, 0, '2025-06-26 19:43:47', 'Pending', 'online', 'ROY STUDIO', 'roysongram2002@gmail.com', '01169566961', 'Taman Tasik Utama, 75450  Ayer Keroh, Melaka', 'delivery'),
(5, 0, '2025-06-26 19:49:32', 'Pending', 'online', 'IZZRAFIQ BIN ZULFIKAR HARDI', 'izzrafiq12@gmail.com', '01169566961', '', 'pickup'),
(6, 0, '2025-06-26 19:52:09', 'Ready', 'online', 'IZZRAFIQ BIN ZULFIKAR HARDI', 'izzrafiq12@gmail.com', '01169566961', '', 'pickup'),
(7, 1, '2025-06-26 19:52:59', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(8, 1, '2025-06-26 20:00:21', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(9, 1, '2025-06-26 20:02:23', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(10, 1, '2025-06-30 00:11:01', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(11, 0, '2025-06-30 00:14:13', 'Pending', 'online', 'ROY STUDIO', 'roysongram2002@gmail.com', '01169566961', 'pt 363,kedai melor', 'delivery'),
(12, 4, '2025-07-02 12:30:44', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(13, 10, '2025-07-02 12:33:39', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(14, 8, '2025-07-02 12:38:02', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(15, 11, '2025-07-02 12:46:07', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL),
(16, 1, '2025-08-07 15:10:33', 'Pending', 'walk-in', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_name`, `quantity`, `price`) VALUES
(1, 1, 'Sup Gearbox Kambing', 1, 19.00),
(2, 2, 'Sup Gearbox Kambing', 1, 19.00),
(3, 3, 'Sup Gearbox Kambing', 1, 19.00),
(4, 3, 'Delivery Fee', 1, 5.00),
(5, 4, 'Smash Beef (Single)', 1, 8.00),
(6, 4, 'Delivery Fee', 1, 5.00),
(7, 5, 'Mee Rebus Ayam', 1, 9.00),
(8, 5, 'Delivery Fee', 1, 5.00),
(9, 6, 'Roti Bakar', 1, 2.50),
(10, 6, 'Delivery Fee', 1, 5.00),
(11, 7, 'Nasi Bawal Goreng Berlado', 1, 9.00),
(12, 8, 'Nasi Bawal Goreng Berlado', 1, 9.00),
(13, 9, 'Kailan (Biasa / Ikan Masin)', 1, 7.00),
(14, 10, 'Sup Gearbox Kambing', 1, 19.00),
(15, 11, 'Sup Gearbox Kambing', 1, 19.00),
(16, 11, 'Delivery Fee', 1, 5.00),
(17, 12, 'Sup Gearbox Kambing (with Roti Francis / Gardenia)', 1, 21.50),
(18, 13, 'Sup Gearbox Kambing', 1, 19.00),
(19, 14, 'Sup Gearbox Kambing', 1, 19.00),
(20, 15, 'Sup Gearbox Kambing', 1, 19.00),
(21, 16, 'Sup Gearbox Kambing', 1, 19.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
