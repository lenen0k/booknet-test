DROP TABLE IF EXISTS method_settings;
DROP TABLE IF EXISTS method_country_settings;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS payment_systems;

-- Payment systems Table
CREATE TABLE payment_systems (
                                   id INTEGER PRIMARY KEY AUTOINCREMENT,
                                   name TEXT,
                                   enabled INTEGER DEFAULT 1
);

-- Payment methods Table
CREATE TABLE payment_methods (
                                 id INTEGER PRIMARY KEY AUTOINCREMENT,
                                 payment_system_id INTEGER,
                                 name TEXT,
                                 commission REAL,
                                 image_url TEXT,
                                 pay_url TEXT,
                                 priority INTEGER DEFAULT 100,
                                 enabled INTEGER DEFAULT 1,
                                 FOREIGN KEY (payment_system_id) REFERENCES payment_systems(id)
);

-- Availability settings by country Table
CREATE TABLE method_country_settings (
                                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                                      method_id INTEGER,
                                      mode TEXT CHECK (mode IN ('allow', 'deny')),
                                      country_code TEXT, -- country code in ISO-3166 format
                                      FOREIGN KEY (method_id) REFERENCES payment_methods(id)
);

-- Method settings Table
CREATE TABLE method_settings (
                                   id INTEGER PRIMARY KEY AUTOINCREMENT,
                                   method_id INTEGER,
                                   condition TEXT,
                                   FOREIGN KEY (method_id) REFERENCES payment_methods(id)
);

-- ======= Insert Data =======

INSERT INTO payment_systems (id, name) VALUES
                                             (1, 'Interkassa'),
                                             (2, 'PayU'),
                                             (3, 'CardPay'),
                                             (4, 'Booknet');

INSERT INTO payment_methods (id, payment_system_id, name, commission, image_url, pay_url, priority) VALUES
                                                                                                  (1, 1, 'Банковские карты', 2.5, 'interkassa_cards.jpg', '/pay_url/11', 1),
                                                                                                  (2, 1, 'LiqPay', 2.0, 'liqpay.jpg', '/pay_url/12', 2),
                                                                                                  (3, 1, 'Терминалы IBOX', 4.0, 'ibox.jpg', '/pay_url/13', 10),
                                                                                                  (4, 2, 'VISA / MasterCard (PayU)', 3.0, 'payu_cards.jpg', '/pay_url/21', 3),
                                                                                                  (5, 2, 'QIWI-кошелек', 3.5, 'qiwi.jpg', '/pay_url/22', 5),
                                                                                                  (6, 3, 'Cards Visa / MasterCard (CardPay)', 1.0, 'cardpay.jpg', '/pay_url/31', 2),
                                                                                                  (7, 4, 'Кошелек Booknet', 0.0, 'booknet_wallet.jpg', '/pay_url/wallet', 0),
                                                                                                  (8, 1, 'GooglePay', 2.5, 'gpay.jpg', '/pay_url/14', 4),
                                                                                                  (9, 1, 'ApplePay', 2.5, 'applepay.jpg', '/pay_url/15', 4),
                                                                                                  (10, 1, 'PayPal', 3.2, 'paypal.jpg', '/pay_url/16', 6);

INSERT INTO method_country_settings (method_id, mode, country_code) VALUES
(8, 'deny', 'IN'),
(9, 'allow', 'US'),
(9, 'allow', 'UA');

INSERT INTO method_settings (method_id, condition) VALUES
                                                         (8, 'only_android'),
                                                         (9, 'only_ios'),
                                                         (7, 'no_wallet_topup');

