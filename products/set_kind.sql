-- 2026-11-19 Тағамдар: Сет / Позиция белгісі (product.is_set)
-- 1 = сет, 0 = позиция, NULL = құрамына қарап (product_set_item бар болса сет)
SET @c := (select count(*) from information_schema.columns where table_schema = database() and table_name = 'product' and column_name = 'is_set');
SET @s := IF(@c = 0, 'ALTER TABLE `product` ADD `is_set` TINYINT(1) NULL DEFAULT NULL COMMENT ''1 сет, 0 позиция, NULL құрамына қарап''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- бастапқы белгі: «Сет / Комбо» деген каталогтағы тағамдар сет (тек белгісі бос болса)
UPDATE product p JOIN product_catalog c ON c.id = p.catalog_id
SET p.is_set = 1
WHERE p.is_set IS NULL AND (LOWER(c.name_ru) LIKE '%сет%' OR LOWER(c.name_kz) LIKE '%сет%' OR LOWER(c.name_ru) LIKE '%set%' OR LOWER(c.name_ru) LIKE '%комбо%' OR LOWER(c.name_ru) LIKE '%combo%');
