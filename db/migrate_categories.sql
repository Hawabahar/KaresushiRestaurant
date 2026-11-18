CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `categories` (`name`, `image`) VALUES
('Appetizer', 'images/appetizer.jpg'),
('Sashimi', 'images/sashimi.jpeg'),
('Rolled Sushi', 'images/rolled-sushi.jpeg'),
('Yakimono Grilled', 'images/yakinimono.jpeg'),
('Teppanyaki', 'images/teppanyaki.jpg'),
('Menrui Noodle', 'images/noodle.jpg'),
('Dessert', 'images/dessert.jpg'),
('Beverages', 'images/beverages.jpg');
