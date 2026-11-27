-- ChickChow: database for cloud MySQL (TiDB Cloud / Aiven ...).
-- Run this in the SQL Editor of your cloud database (it uses the database you selected).
-- =====================================================
-- ChickChow - Online Food Ordering
-- Database dump (MySQL / MariaDB)
-- Import: phpMyAdmin -> Import tab
-- =====================================================

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(20) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE restaurants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  cuisine VARCHAR(60) NOT NULL,
  area VARCHAR(60) NOT NULL,
  rating DECIMAL(2,1) DEFAULT 4.0,
  image VARCHAR(255) DEFAULT 'placeholder.jpg',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE food_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT NOT NULL,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(8,2) NOT NULL,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT 'placeholder.jpg',
  status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  label VARCHAR(30) NOT NULL,
  area VARCHAR(60) NOT NULL,
  address VARCHAR(255) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  description VARCHAR(150) NOT NULL DEFAULT '',
  type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value INT NOT NULL,
  min_order INT NOT NULL DEFAULT 0,
  max_discount INT NOT NULL DEFAULT 0,
  expires_at DATE DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  delivery_area VARCHAR(60) NOT NULL,
  address VARCHAR(255) NOT NULL,
  subtotal DECIMAL(8,2) NOT NULL,
  discount DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  coupon_id INT DEFAULT NULL,
  coupon_code VARCHAR(20) DEFAULT NULL,
  delivery_charge DECIMAL(6,2) NOT NULL DEFAULT 60.00,
  total DECIMAL(8,2) NOT NULL,
  payment_method ENUM('cod','online') NOT NULL DEFAULT 'cod',
  status ENUM('Pending','Preparing','Out for Delivery','Delivered') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  food_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  user_id INT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
('Biryani & Rice'),
('Burger'),
('Pizza'),
('Chicken'),
('Pasta'),
('Sides & Snacks'),
('Sandwich & Wrap'),
('Grill & Steak'),
('Drinks'),
('Dessert');

INSERT INTO restaurants (name, cuisine, area, rating, image) VALUES
('Kacchi Bhai','Bangladeshi','Mohammadpur',4.6,'kacchi-bhai.jpg'),
('Sultan\'s Dine','Bangladeshi','Dhanmondi',4.7,'sultans-dine.jpg'),
('BFC','Fast Food','Farmgate',4.2,'bfc.jpg'),
('Domino\'s Pizza','Pizza','Banani',4.4,'dominos.jpg'),
('Pizzaburg','Fast Food','Uttara',4.3,'pizzaburg.jpg'),
('Mad Chef','Burger','Mirpur 10',4.1,'madchef.jpg'),
('Arabian Fast Food','Arabian','Jigatola',4.0,'arabian.jpg'),
('Cafe Appeliano','Cafe & Italian','Gulshan 1',4.5,'appeliano.jpg'),
('Food Palace','Fast Food','Malibagh',4.2,'food-palace.jpg'),
('Star Hotel & Kebab','Bangladeshi','Motijheel',4.4,'star-kebab.jpg'),
('Cha Joy','Drinks & Dessert','Bashundhara R/A',4.5,'cha-joy.jpg');

INSERT INTO food_items (restaurant_id, category_id, name, description, price, image) VALUES
(1,1,'Basmati Kacchi Biryani','Aromatic basmati rice layered with tender mutton',320.00,'kacchi-bhai-basmati-kacchi.jpg'),
(1,1,'Kacchi Khadak','Spicy slow-cooked kacchi with rich masala',350.00,'kacchi-bhai-kacchi-khadak.jpg'),
(1,1,'Special Mutton Tehari','Mustard-oil tehari with juicy mutton pieces',280.00,'kacchi-bhai-mutton-tehari.jpg'),
(1,8,'Beef Chui Jhal','Hot and tangy beef curry with chui jhal',300.00,'kacchi-bhai-beef-chuijhal.jpg'),
(1,1,'Plain Polao & Chicken Roast','Fragrant polao with sweet-spicy chicken roast',240.00,'kacchi-bhai-polao-roast.jpg'),
(2,1,'Kacchi Biryani','Classic Dhaka-style kacchi biryani',360.00,'sultans-dine-kacchi-biryani.jpg'),
(2,1,'Chicken Dam Biryani','Slow-cooked chicken biryani sealed on dum',290.00,'sultans-dine-dam-biryani.jpg'),
(2,1,'Mutton Tehari','Soft mutton cooked with rice and mustard oil',300.00,'sultans-dine-mutton-tehari.jpg'),
(2,8,'Beef Chap with Polao','Tender beef chap served with polao',340.00,'sultans-dine-beef-chap.jpg'),
(2,1,'Kacchi Platter','Kacchi with borhani, salad and jali kabab',420.00,'sultans-dine-kacchi-platter.jpg'),
(3,4,'Crispy Fried Chicken','Golden crispy chicken, 2 pieces',220.00,'bfc-crispy-fried-chicken.jpg'),
(3,4,'Spicy Fried Chicken','Fiery hot fried chicken, 2 pieces',230.00,'bfc-spicy-fried-chicken.jpg'),
(3,6,'Meatball','Juicy meatballs with dipping sauce',160.00,'bfc-meatball.jpg'),
(3,6,'Sausage','Grilled chicken sausages',140.00,'bfc-sausage.jpg'),
(3,6,'French Fries','Crispy salted fries',110.00,'bfc-french-fries.jpg'),
(3,7,'Sandwich','Grilled chicken sandwich with veggies',180.00,'bfc-sandwich.jpg'),
(4,3,'Spicy Chicken Pizza','Spicy chicken, onions and capsicum, 9 inch',620.00,'dominos-spicy-chicken-pizza.jpg'),
(4,3,'Margherita Pizza','Tomato sauce, mozzarella and basil, 9 inch',520.00,'dominos-margherita-pizza.jpg'),
(4,3,'Chicken Pepperoni Pizza','Chicken pepperoni with extra cheese, 9 inch',680.00,'dominos-chicken-pepperoni-pizza.jpg'),
(4,6,'Garlic Bread','Toasted bread with garlic butter and cheese',190.00,'dominos-garlic-bread.jpg'),
(4,4,'Chicken Wings','6 pcs oven-baked wings with BBQ glaze',280.00,'dominos-chicken-wings.jpg'),
(5,3,'Sausage Carnival Pizza','Loaded with sausage, olives and cheese',590.00,'pizzaburg-sausage-pizza.jpg'),
(5,2,'Beef Burger','Beef patty, cheese, lettuce and house sauce',260.00,'pizzaburg-beef-burger.jpg'),
(5,2,'Chicken Burger','Crispy chicken fillet with mayo',230.00,'pizzaburg-chicken-burger.jpg'),
(5,4,'Chicken Lollipop','6 pcs crispy chicken lollipops',270.00,'pizzaburg-lollipop.jpg'),
(5,6,'French Fries','Golden fries with ketchup',120.00,'pizzaburg-french-fries.jpg'),
(6,2,'Beef Burger','Double beef patty with cheddar',290.00,'madchef-beef-burger.jpg'),
(6,2,'Chicken Burger','Juicy chicken patty with fresh veggies',240.00,'madchef-chicken-burger.jpg'),
(6,4,'Chicken Wings','6 pcs hot and spicy wings',260.00,'madchef-wings.jpg'),
(6,7,'Chicken Wrap','Grilled chicken, veggies and garlic sauce',200.00,'madchef-chicken-wrap.jpg'),
(6,6,'French Fries','Crispy fries with seasoning',110.00,'madchef-french-fries.jpg'),
(7,4,'Fried Chicken','Crispy fried chicken, 2 pieces',210.00,'arabian-fried-chicken.jpg'),
(7,4,'Chicken Nuggets','8 pcs golden chicken nuggets',180.00,'arabian-nuggets.jpg'),
(7,2,'Chicken Burger','Chicken burger with Arabian sauce',200.00,'arabian-chicken-burger.jpg'),
(7,5,'Chicken Pasta','Creamy pasta with chicken chunks',250.00,'arabian-chicken-pasta.jpg'),
(7,3,'Chicken Pizza','Cheesy chicken pizza, 8 inch',450.00,'arabian-chicken-pizza.jpg'),
(8,5,'Chicken Alfredo Pasta','Creamy alfredo sauce with grilled chicken',420.00,'appeliano-alfredo-pasta.jpg'),
(8,3,'Chicken Pizza','Wood-style chicken pizza with mozzarella',560.00,'appeliano-chicken-pizza.jpg'),
(8,8,'Grilled Chicken','Herb-marinated grilled chicken with sides',480.00,'appeliano-grilled-chicken.jpg'),
(8,8,'Chicken Steak','Chicken steak with mushroom sauce and vegetables',520.00,'appeliano-chicken-steak.jpg'),
(8,10,'Waffle','Warm waffle with chocolate syrup',260.00,'appeliano-waffle.jpg'),
(9,6,'Onion Rings','Crunchy battered onion rings',140.00,'food-palace-onion-rings.jpg'),
(9,6,'French Fries','Salted crispy fries',110.00,'food-palace-french-fries.jpg'),
(9,4,'Chicken Popcorn','Bite-size crispy chicken popcorn',180.00,'food-palace-popcorn-chicken.jpg'),
(9,4,'Naga Drums','Spicy naga-glazed chicken drumsticks',240.00,'food-palace-naga-drums.jpg'),
(9,9,'Chocolate Milkshake','Thick chocolate milkshake',200.00,'food-palace-choco-shake.jpg'),
(10,1,'Chicken Biryani','Spiced chicken biryani with egg and salad',260.00,'star-kebab-chicken-biryani.jpg'),
(10,8,'Beef Tikka','Charcoal-grilled beef tikka pieces',320.00,'star-kebab-beef-tikka.jpg'),
(10,9,'Borhani','Traditional spiced yogurt drink',70.00,'star-kebab-borhani.jpg'),
(10,10,'Firni','Creamy rice pudding with cardamom',90.00,'star-kebab-firni.jpg'),
(11,9,'Brown Sugar Boba Milk Tea','Milk tea with brown sugar syrup and tapioca pearls',240.00,'cha-joy-brown-sugar-boba.jpg'),
(11,9,'Taro Milk Tea','Creamy taro milk tea',230.00,'cha-joy-taro-milk-tea.jpg'),
(11,9,'Iced Matcha Latte','Iced matcha with fresh milk',250.00,'cha-joy-matcha-latte.jpg');

-- Demo accounts (password: admin123 / user123, stored as password_hash)
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin','admin@chickchow.com','01700000000','$2y$10$mBURbr4WDbtDLRtBsnJrgeBKS7w7T9.ybl7xcpe9t3RqiZ2Zpk0lm','admin'),
('Demo User','user@chickchow.com','01711111111','$2y$10$KA1.syXdOhYAZgslcuFXM.dB/W9agX2cIVpSivQLMvWoqBrm80hCq','user');
INSERT INTO addresses (user_id, label, area, address) VALUES
(2,'Home','Dhanmondi','House 12, Road 5'),
(2,'Work','Gulshan 1','Road 41, Level 5');

-- Food discounts (percent off)
UPDATE food_items SET discount_percent = 10 WHERE id IN (1, 24, 44, 53);
UPDATE food_items SET discount_percent = 15 WHERE id IN (3, 34, 51);
UPDATE food_items SET discount_percent = 20 WHERE id IN (6, 18, 27, 49);
UPDATE food_items SET discount_percent = 25 WHERE id IN (17);
UPDATE food_items SET discount_percent = 30 WHERE id IN (22);
UPDATE food_items SET discount_percent = 12 WHERE id IN (38);

-- Coupons
INSERT INTO coupons (code, description, type, value, min_order, max_discount, expires_at, is_active) VALUES
('CHICK10','10% off on orders above 300','percent',10,300,150,'2027-12-31',1),
('WELCOME50','50 taka off on your order above 250','fixed',50,250,0,'2027-12-31',1),
('BIGFEAST','150 taka off on big orders above 800','fixed',150,800,0,'2027-12-31',1);

-- Demo orders (Delivered) with reviews, so the home page shows customer reviews
INSERT INTO orders (id, user_id, full_name, phone, delivery_area, address, subtotal, discount, coupon_id, coupon_code, delivery_charge, total, payment_method, status, created_at) VALUES
(1, 2, 'Demo User', '01711111111', 'Dhanmondi', 'House 12, Road 5', 576.00, 0.00, NULL, NULL, 60.00, 636.00, 'cod', 'Delivered', '2026-10-20 19:30:00'),
(2, 2, 'Demo User', '01711111111', 'Gulshan 1', 'Road 41, Level 5', 655.00, 50.00, 2, 'WELCOME50', 60.00, 665.00, 'cod', 'Delivered', '2026-10-22 13:10:00');
INSERT INTO order_items (order_id, food_id, quantity, price) VALUES
(1, 1, 2, 288.00),
(2, 17, 1, 465.00),
(2, 20, 1, 190.00);
INSERT INTO reviews (order_id, user_id, rating, comment, created_at) VALUES
(1, 2, 5, 'Kacchi was hot and tasty, and it arrived before time. Loved the discount too!', '2026-10-20 21:05:00'),
(2, 2, 4, 'Pizza was great. Coupon worked perfectly. Delivery was a little late.', '2026-10-22 14:40:00');
