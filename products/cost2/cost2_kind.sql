-- Тағам ТҮРІ: product.kind (1 сет, 2 тағам, 3 тікелей шикізат, 4 қатысы жоқ; NULL белгісіз), product.kind_src (user | ai).
-- АЛДЫМЕН БЭКАП. Қайта іске қосуға болады. Ескі product.is_set сақталады (1 → kind 1, 0 → kind 2, NULL → белгісіз).
-- Алдымен products/set_kind.sql (product.is_set) салынған болуы керек.
SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product' AND column_name = 'kind');
SET @s := IF(@c = 0, 'ALTER TABLE `product` ADD COLUMN `kind` tinyint(1) NULL DEFAULT NULL COMMENT ''1 сет, 2 тағам, 3 тікелей шикізат, 4 қатысы жоқ''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product' AND column_name = 'kind_src');
SET @s := IF(@c = 0, 'ALTER TABLE `product` ADD COLUMN `kind_src` varchar(4) NULL DEFAULT NULL COMMENT ''user | ai''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- бар белгілер (қолмен не бұрынғы ЖИ қойған) — қолмен деп саналады
UPDATE `product` SET `kind` = 1, `kind_src` = 'user' WHERE `kind` IS NULL AND `is_set` = 1;
UPDATE `product` SET `kind` = 2, `kind_src` = 'user' WHERE `kind` IS NULL AND `is_set` = 0;
