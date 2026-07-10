-- Kalyani Catering database schema
-- Import via phpMyAdmin (cPanel/hPanel -> MySQL Databases -> phpMyAdmin) or:
--   mysql -u USERNAME -p DATABASENAME < db.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner','staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: email admin@kalyanicatering.com / password Kalyani@123
-- CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN (Admin > Settings > Change Password)
INSERT INTO admin_users (name, email, password_hash, role) VALUES
('Kalyani Admin', 'admin@kalyanicatering.com', '$2y$12$20swaOM65m14ZzWv7jtMtuoQAyCM6CyE7DKfn6xdmhPMkqLFvo./2', 'owner')
ON DUPLICATE KEY UPDATE email = email;

-- ---------------------------------------------------------------------
-- Menu items
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description TEXT NULL,
    cuisine VARCHAR(60) NOT NULL DEFAULT 'Indian',
    diet_type ENUM('veg','non-veg') NOT NULL DEFAULT 'veg',
    course_type VARCHAR(40) NOT NULL DEFAULT 'starter',
    serving_info VARCHAR(120) NULL,
    unit_price DECIMAL(10,2) NULL,
    quantity_available INT NULL,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_new TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_search (name, description, cuisine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional tray-size pricing tiers (Small / Medium / Full) per menu item
CREATE TABLE IF NOT EXISTS menu_item_tiers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT UNSIGNED NOT NULL,
    tier_name VARCHAR(40) NOT NULL,
    serves_text VARCHAR(60) NULL,
    price DECIMAL(10,2) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Discount codes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS discount_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses INT NULL,
    used_count INT NOT NULL DEFAULT 0,
    expires_at DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(40) NOT NULL,
    fulfillment_type ENUM('delivery','pickup') NOT NULL DEFAULT 'pickup',
    event_date DATE NOT NULL,
    event_time TIME NULL,
    address TEXT NULL,
    notes TEXT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_code VARCHAR(40) NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('pending_approval','approved','rejected','payment_pending','paid','completed','cancelled')
        NOT NULL DEFAULT 'pending_approval',
    admin_notes TEXT NULL,
    payment_method VARCHAR(20) NULL,
    payment_reference VARCHAR(190) NULL,
    payment_token VARCHAR(64) NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NULL,
    item_name VARCHAR(150) NOT NULL,
    tier_name VARCHAR(40) NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Contact messages
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    department ENUM('general','billing','customer_service') NOT NULL DEFAULT 'general',
    message TEXT NOT NULL,
    status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Newsletter subscribers
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(150) NULL,
    unsubscribe_token VARCHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    menu_item_id INT UNSIGNED NULL,
    recipient_count INT NOT NULL DEFAULT 0,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Settings (key/value store for admin-editable operational settings)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES
('business_name', 'Kalyani Catering'),
('business_tagline', 'Good Food, Great Memories'),
('business_phone', '843-819-4418'),
('business_whatsapp', '843-819-4418'),
('business_address', '6279 W Butte County Drive, Herriman, UT 84096'),
('email_general', 'admin@kalyanicatering.com'),
('email_billing', 'admin@kalyanicatering.com'),
('email_customer_service', 'admin@kalyanicatering.com'),
('ubereats_url', ''),
('doordash_url', ''),
('order_lead_days', '3'),
('facebook_url', ''),
('instagram_url', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ---------------------------------------------------------------------
-- Sample menu data (from Kalyani Catering flyers)
-- ---------------------------------------------------------------------
INSERT INTO menu_items (name, slug, description, cuisine, diet_type, course_type, serving_info, unit_price, discount_percent, is_active, is_new, sort_order) VALUES
('Vegetable Pakora', 'vegetable-pakora', 'Crispy mixed vegetable fritters, lightly spiced and fried golden.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 1),
('Vada Pav', 'vada-pav', 'Mumbai street-style spiced potato fritter in a soft bun with chutneys.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 2),
('Batata Vada', 'batata-vada', 'Deep fried spiced potato dumplings, a Maharashtrian favorite.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 3),
('Pav Bhaji', 'pav-bhaji', 'Buttery mashed mixed-vegetable curry served with soft pav buns.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 4),
('Medu Vada', 'medu-vada', 'Crispy South Indian lentil doughnuts served with sambar and chutney.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 5),
('Samosa', 'samosa', 'Golden pastry filled with spiced potatoes and peas.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 1, 6),
('Dahi Vada', 'dahi-vada', 'Lentil dumplings soaked in seasoned yogurt with tangy chutneys.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 7),
('Paneer Tikka', 'paneer-tikka', 'Char-grilled marinated cottage cheese skewers.', 'Indian', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 8),
('Gobi Manchurian', 'gobi-manchurian', 'Crispy cauliflower tossed in a tangy Indo-Chinese sauce.', 'Indo-Chinese', 'veg', 'starter', 'Tray', NULL, 0, 1, 0, 9),
('Chicken Pakoda', 'chicken-pakoda', 'Crispy fried chicken bites, spiced and golden fried.', 'Indian', 'non-veg', 'starter', 'Tray', NULL, 0, 1, 0, 10),
('Chicken Malai Kabab', 'chicken-malai-kabab', 'Creamy, mildly spiced grilled chicken skewers.', 'Indian', 'non-veg', 'starter', 'Tray', NULL, 0, 1, 0, 11),
('Chicken Tikka', 'chicken-tikka', 'Classic tandoor-grilled marinated chicken pieces.', 'Indian', 'non-veg', 'starter', 'Tray', NULL, 0, 1, 0, 12),
('Chilli Chicken (Dry)', 'chilli-chicken-dry', 'Indo-Chinese style spicy fried chicken tossed with peppers and onions.', 'Indo-Chinese', 'non-veg', 'starter', 'Tray', NULL, 0, 1, 0, 13),
('Chicken Manchurian', 'chicken-manchurian', 'Fried chicken tossed in a savory Indo-Chinese Manchurian sauce.', 'Indo-Chinese', 'non-veg', 'starter', 'Tray', NULL, 0, 1, 0, 14),
('Kadai Paneer', 'kadai-paneer', 'Cottage cheese simmered in a spiced kadai masala gravy.', 'Indian', 'veg', 'main', 'Tray', NULL, 0, 1, 0, 15),
('Butter Paneer / Palak Paneer', 'butter-paneer-palak-paneer', 'Creamy butter paneer or spinach paneer curry.', 'Indian', 'veg', 'main', 'Tray', NULL, 0, 1, 0, 16),
('Mix Veg', 'mix-veg', 'Seasonal mixed vegetables cooked in a light Indian curry.', 'Indian', 'veg', 'main', 'Tray', NULL, 0, 1, 0, 17),
('Chicken Angara', 'chicken-angara', 'Smoky, fiery chicken curry cooked kadai-style.', 'Indian', 'non-veg', 'main', 'Tray', NULL, 0, 1, 0, 18),
('Kadai Chicken', 'kadai-chicken', 'Chicken simmered with peppers and onions in a bold kadai masala.', 'Indian', 'non-veg', 'main', 'Tray', NULL, 0, 1, 0, 19),
('Chicken Korma', 'chicken-korma', 'Mild, creamy chicken curry with aromatic spices.', 'Indian', 'non-veg', 'main', 'Tray', NULL, 0, 1, 0, 20),
('Butter Chicken', 'butter-chicken', 'Tandoori chicken in a rich, creamy tomato-butter sauce.', 'Indian', 'non-veg', 'main', 'Tray', NULL, 0, 1, 1, 21),
('Egg Curry', 'egg-curry', 'Boiled eggs simmered in a spiced onion-tomato gravy.', 'Indian', 'non-veg', 'main', 'Tray', NULL, 0, 1, 0, 22),
('Dal Makhani / Chole', 'dal-makhani-chole', 'Creamy black lentils or spiced chickpea curry.', 'Indian', 'veg', 'dal', 'Tray', NULL, 0, 1, 0, 23),
('Dal Tadka', 'dal-tadka', 'Yellow lentils tempered with cumin, garlic and spices.', 'Indian', 'veg', 'dal', 'Tray', NULL, 0, 1, 0, 24),
('Sambar', 'sambar', 'South Indian tangy lentil and vegetable stew.', 'Indian', 'veg', 'dal', 'Tray', NULL, 0, 1, 0, 25),
('Tomato Rasam', 'tomato-rasam', 'Peppery, tangy South Indian tomato soup.', 'Indian', 'veg', 'dal', 'Tray', NULL, 0, 1, 0, 26),
('Chicken Biryani', 'chicken-biryani', 'Fragrant basmati rice layered with spiced chicken.', 'Indian', 'non-veg', 'rice', 'Tray', NULL, 0, 1, 1, 27),
('Vijayawada Chicken Biryani', 'vijayawada-chicken-biryani', 'Andhra-style fiery chicken biryani.', 'Indian', 'non-veg', 'rice', 'Tray', NULL, 0, 1, 0, 28),
('Veg Biryani', 'veg-biryani', 'Aromatic basmati rice layered with spiced mixed vegetables.', 'Indian', 'veg', 'rice', 'Tray', NULL, 0, 1, 0, 29),
('Jeera Rice', 'jeera-rice', 'Basmati rice tempered with cumin seeds.', 'Indian', 'veg', 'rice', 'Tray', NULL, 0, 1, 0, 30),
('Fried Rice (Chinese)', 'fried-rice-chinese', 'Indo-Chinese style vegetable fried rice.', 'Indo-Chinese', 'veg', 'rice', 'Tray', NULL, 0, 1, 0, 31),
('Rasmalai', 'rasmalai', 'Soft cottage cheese dumplings in sweetened saffron milk.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 32),
('Rice Pudding', 'rice-pudding', 'Creamy cardamom-scented rice kheer.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 33),
('Jalebi', 'jalebi', 'Crispy, syrup-soaked spiral sweets.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 34),
('Rasgulla', 'rasgulla', 'Soft, spongy cottage cheese balls in light sugar syrup.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 35),
('Balushahi', 'balushahi', 'Flaky, syrup-glazed Indian sweet pastry.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 36),
('Chum Chum', 'chum-chum', 'Sweet, syrup-soaked cottage cheese dumplings.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 37),
('Sooji Halwa', 'sooji-halwa', 'Warm semolina pudding with ghee, nuts and cardamom.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 38),
('Double Ka Meetha', 'double-ka-meetha', 'Hyderabadi bread pudding soaked in sweet milk.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 39),
('Gulab Jamun', 'gulab-jamun', 'Soft milk-solid dumplings soaked in rose-cardamom sugar syrup.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 1, 40),
('Laddu', 'laddu', 'Classic besan or motichoor sweet round treats.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 41),
('Chhena Poda', 'chhena-poda', 'Baked cottage cheese dessert, an authentic Odisha sweet.', 'Indian', 'veg', 'dessert', 'Tray', NULL, 0, 1, 0, 42),
('Mango Lassi', 'mango-lassi', 'Chilled sweet mango yogurt smoothie.', 'Indian', 'veg', 'drink', 'Glass', 4.50, 0, 1, 0, 43),
('Rose Lassi', 'rose-lassi', 'Chilled rose-flavored yogurt smoothie.', 'Indian', 'veg', 'drink', 'Glass', 4.50, 0, 1, 0, 44),
('Cucumber Raita', 'cucumber-raita', 'Cool cucumber and yogurt side.', 'Indian', 'veg', 'side', 'Bowl', 3.00, 0, 1, 0, 45),
('Boondi Raita', 'boondi-raita', 'Crispy gram-flour pearls in seasoned yogurt.', 'Indian', 'veg', 'side', 'Bowl', 3.00, 0, 1, 0, 46),
('Mint Chutney', 'mint-chutney', 'Fresh mint and cilantro chutney.', 'Indian', 'veg', 'side', 'Bowl', 2.00, 0, 1, 0, 47),
('Salad', 'salad', 'Fresh mixed seasonal salad.', 'Indian', 'veg', 'side', 'Bowl', 2.50, 0, 1, 0, 48)
ON DUPLICATE KEY UPDATE slug = slug;

-- Tray pricing tiers for tray items (Small 8-10 / Medium 18-20 / Full 35-40)
INSERT INTO menu_item_tiers (menu_item_id, tier_name, serves_text, price, sort_order)
SELECT id, 'Small', 'Serves 8-10', 45.00, 1 FROM menu_items WHERE serving_info = 'Tray'
UNION ALL
SELECT id, 'Medium', 'Serves 18-20', 85.00, 2 FROM menu_items WHERE serving_info = 'Tray'
UNION ALL
SELECT id, 'Full', 'Serves 35-40', 150.00, 3 FROM menu_items WHERE serving_info = 'Tray';

-- Sample discount code
INSERT INTO discount_codes (code, description, type, value, min_order_amount, is_active) VALUES
('WELCOME10', '10% off orders over $200', 'percent', 10.00, 200.00, 1),
('SAVE15', '15% off orders over $500', 'percent', 15.00, 500.00, 1)
ON DUPLICATE KEY UPDATE code = code;
