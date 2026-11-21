-- Шикізат ТҮРІ (Ун ▸ әр бренд / накладной нұсқалары) және қолдану аймағы. АЛДЫМЕН БЭКАП. Қайта іске қосуға болады (MySQL + MariaDB).
-- company_raw_type: түр (атауы, өлшемі, қолдану аймағы, баға режимі: last — соңғы накладной бағасы, avg — нұсқалардың орташасы).
-- product_raw.type_id: нұсқаны түрге байлайды (NULL — түрсіз). Рецепттегі нұсқа түр бағасымен есептеледі (products/cost2/rawtype.php).
CREATE TABLE IF NOT EXISTS `company_raw_type` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`company_id` int(11) NOT NULL,
	`name` varchar(120) NOT NULL,
	`unit` varchar(20) DEFAULT NULL,
	`area` varchar(40) DEFAULT NULL COMMENT 'қолдану аймағы: Тесто, Соус, Гарнир, Сусын …',
	`price_mode` varchar(5) NOT NULL DEFAULT 'last' COMMENT 'last | avg',
	`arh` tinyint(1) NOT NULL DEFAULT 0,
	`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`id`),
	KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Себес: шикізат түрі (нұсқалар тобы)';

SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_raw' AND column_name = 'type_id');
SET @s := IF(@c = 0, 'ALTER TABLE `product_raw` ADD COLUMN `type_id` int(11) NULL DEFAULT NULL, ADD KEY `type_id` (`type_id`)', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
