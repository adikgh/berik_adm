-- Дайындама (semi-finished) шаблондары: барлық компанияға ортақ. Жасалған: 2026-11-19
-- АЛДЫМЕН БЭКАП. Қайта іске қосуға болады: бағандар тек жоқ болса қосылады, бар шаблонға тиіспейді (әзірлеуші өзгерткені сақталады).
-- grammovka_tpl.kind: dish = тағам, semi = дайындама (unit, yield_qty: шығымы). grammovka_tpl_item.semi_id: құрам — дайындама шаблоны.
-- grammovka_price: жаңа шикізаттың орташа бағасы (басқа компаниялар не ЖИ бағалауы), ортақ кэш. product_comp.price_src: ai / db (баға қайдан алынды).
SET NAMES utf8mb4;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grammovka_tpl' AND COLUMN_NAME = 'kind');
SET @s := IF(@c = 0, 'ALTER TABLE `grammovka_tpl` ADD COLUMN `kind` varchar(10) NOT NULL DEFAULT ''dish''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grammovka_tpl' AND COLUMN_NAME = 'unit');
SET @s := IF(@c = 0, 'ALTER TABLE `grammovka_tpl` ADD COLUMN `unit` varchar(20) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grammovka_tpl' AND COLUMN_NAME = 'yield_qty');
SET @s := IF(@c = 0, 'ALTER TABLE `grammovka_tpl` ADD COLUMN `yield_qty` decimal(12,3) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grammovka_tpl' AND COLUMN_NAME = 'ai_note');
SET @s := IF(@c = 0, 'ALTER TABLE `grammovka_tpl` ADD COLUMN `ai_note` varchar(500) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grammovka_tpl_item' AND COLUMN_NAME = 'semi_id');
SET @s := IF(@c = 0, 'ALTER TABLE `grammovka_tpl_item` ADD COLUMN `semi_id` int(11) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_comp' AND COLUMN_NAME = 'price_src');
SET @s := IF(@c = 0, 'ALTER TABLE `product_comp` ADD COLUMN `price_src` varchar(10) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS `grammovka_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_ru` varchar(255) NOT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'кг',
  `price` int(11) NOT NULL DEFAULT 0,       -- 1 кг / 1 л / 1 шт бағасы, тг
  `src` varchar(10) NOT NULL DEFAULT 'ai',  -- ai / db
  `note` varchar(255) NULL,
  `upd_dt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unit` (`name_ru`, `unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

START TRANSACTION;

-- Заправка для риса
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Заправка для риса' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Заправка для риса','Дайындама','заправка для риса|су|суши-су|рисовая заправка|миксуком|мицкан',1001,'semi','л',1,'Уксус + сахар + соль; на 1 кг сухого риса ~150 мл' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Уксус рисовый' c,'л' d,750 e,1 f
   UNION ALL SELECT @t,2,'Уксус рисовый','л',750,1
   UNION ALL SELECT @t,3,'Уксус рисовый','л',750,1
   UNION ALL SELECT @t,1,'Сахар','кг',300,2
   UNION ALL SELECT @t,2,'Сахар','кг',280,2
   UNION ALL SELECT @t,3,'Сахар','кг',260,2
   UNION ALL SELECT @t,1,'Соль','кг',80,3
   UNION ALL SELECT @t,2,'Соль','кг',70,3
   UNION ALL SELECT @t,3,'Соль','кг',60,3
   UNION ALL SELECT @t,3,'Комбу','кг',10,4) z WHERE @new;

-- Рис для суши
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Рис для суши' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Рис для суши','Дайындама','рис для суши|суши рис|рис суши',1002,'semi','кг',2.3,'1 кг сухого риса + ~1,2 л воды → ~2,3 кг готового риса' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис круглозерный' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Рис японский','кг',1000,2
   UNION ALL SELECT @t,3,'Рис японский','кг',1000,2
   UNION ALL SELECT @t,1,'Заправка для риса','л',130,3
   UNION ALL SELECT @t,2,'Заправка для риса','л',150,3
   UNION ALL SELECT @t,3,'Заправка для риса','л',160,3) z WHERE @new;

-- Огурцы готовые
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Огурцы готовые' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Огурцы готовые','Дайындама','огурцы готовые|огурцы нарезанные|огурец нарезка',1003,'semi','кг',1,'Очищенные и нарезанные соломкой; отход ~15%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Огурцы' c,'кг' d,1150 e,1 f
   UNION ALL SELECT @t,2,'Огурцы','кг',1180,1
   UNION ALL SELECT @t,3,'Огурцы','кг',1200,1) z WHERE @new;

-- Краб готовый
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Краб готовый' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Краб готовый','Дайындама','краб готовый|крабовый микс|краб микс|крабовый салат',1004,'semi','кг',1,'Крабовые палочки (снежный краб) с майонезом/сыром' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крабовые палочки' c,'кг' d,800 e,1 f
   UNION ALL SELECT @t,2,'Крабовые палочки','кг',750,1
   UNION ALL SELECT @t,3,'Снежный краб','кг',750,2
   UNION ALL SELECT @t,1,'Майонез','кг',200,3
   UNION ALL SELECT @t,2,'Майонез','кг',250,3
   UNION ALL SELECT @t,3,'Майонез','кг',150,3
   UNION ALL SELECT @t,3,'Сыр творожный','кг',100,4) z WHERE @new;

-- Курица готовая
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Курица готовая' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Курица готовая','Дайындама','курица готовая|курица жареная|курица отварная|куриное филе готовое',1005,'semi','кг',1,'Филе обжаривается/отваривается, ужарка ~25%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Филе куриное' c,'кг' d,1350 e,1 f
   UNION ALL SELECT @t,2,'Филе куриное','кг',1330,1
   UNION ALL SELECT @t,3,'Филе куриное','кг',1300,1
   UNION ALL SELECT @t,1,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,2,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,3,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,1,'Соль','кг',15,3
   UNION ALL SELECT @t,2,'Соль','кг',15,3
   UNION ALL SELECT @t,3,'Соль','кг',15,3
   UNION ALL SELECT @t,1,'Специи для курицы','кг',8,4
   UNION ALL SELECT @t,2,'Специи для курицы','кг',10,4
   UNION ALL SELECT @t,3,'Специи для курицы','кг',12,4) z WHERE @new;

-- Говядина готовая
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Говядина готовая' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Говядина готовая','Дайындама','говядина готовая|мясо готовое|говядина жареная',1006,'semi','кг',1,'Говядина обжаривается/тушится, ужарка ~35%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Говядина' c,'кг' d,1550 e,1 f
   UNION ALL SELECT @t,2,'Говядина','кг',1520,1
   UNION ALL SELECT @t,3,'Говядина','кг',1500,1
   UNION ALL SELECT @t,1,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,2,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,3,'Масло растительное','кг',30,2
   UNION ALL SELECT @t,1,'Соль','кг',15,3
   UNION ALL SELECT @t,2,'Соль','кг',15,3
   UNION ALL SELECT @t,3,'Соль','кг',15,3
   UNION ALL SELECT @t,1,'Перец черный молотый','кг',3,4
   UNION ALL SELECT @t,2,'Перец черный молотый','кг',3,4
   UNION ALL SELECT @t,3,'Перец черный молотый','кг',4,4) z WHERE @new;

-- Колбаса готовая
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Колбаса готовая' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Колбаса готовая','Дайындама','колбаса готовая|колбаса нарезка|пепперони нарезка|салями нарезка',1007,'semi','кг',1,'Нарезанная колбаса для пиццы; отход ~3%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Колбаса салями' c,'кг' d,1030 e,1 f
   UNION ALL SELECT @t,2,'Пепперони','кг',1030,2
   UNION ALL SELECT @t,3,'Пепперони','кг',1030,2) z WHERE @new;

-- Тесто пицца
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Тесто пицца' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Тесто пицца','Дайындама','тесто пицца|тесто для пиццы|пицца тесто',1008,'semi','кг',1.65,'На 1 кг муки ~600 мл воды → ~1,65 кг теста' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Мука' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Мука','кг',1000,1
   UNION ALL SELECT @t,3,'Мука','кг',1000,1
   UNION ALL SELECT @t,1,'Дрожжи','кг',12,2
   UNION ALL SELECT @t,2,'Дрожжи','кг',10,2
   UNION ALL SELECT @t,3,'Дрожжи','кг',8,2
   UNION ALL SELECT @t,1,'Сахар','кг',30,3
   UNION ALL SELECT @t,2,'Сахар','кг',25,3
   UNION ALL SELECT @t,3,'Сахар','кг',20,3
   UNION ALL SELECT @t,1,'Соль','кг',18,4
   UNION ALL SELECT @t,2,'Соль','кг',18,4
   UNION ALL SELECT @t,3,'Соль','кг',20,4
   UNION ALL SELECT @t,1,'Масло растительное','кг',60,5
   UNION ALL SELECT @t,2,'Масло растительное','кг',40,5
   UNION ALL SELECT @t,3,'Масло оливковое','л',50,6) z WHERE @new;

-- Красный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Красный соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Красный соус','Дайындама','красный соус|соус для пиццы|томатный соус|пицца соус',1009,'semi','кг',1,'Томатная основа для пиццы и шаурмы' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Томатная паста' c,'кг' d,380 e,1 f
   UNION ALL SELECT @t,2,'Томатная паста','кг',120,1
   UNION ALL SELECT @t,2,'Томаты в собственном соку','кг',800,2
   UNION ALL SELECT @t,3,'Томаты в собственном соку','кг',950,2
   UNION ALL SELECT @t,1,'Сахар','кг',25,3
   UNION ALL SELECT @t,2,'Сахар','кг',20,3
   UNION ALL SELECT @t,3,'Сахар','кг',15,3
   UNION ALL SELECT @t,1,'Соль','кг',10,4
   UNION ALL SELECT @t,2,'Соль','кг',10,4
   UNION ALL SELECT @t,3,'Соль','кг',10,4
   UNION ALL SELECT @t,1,'Чеснок','кг',10,5
   UNION ALL SELECT @t,2,'Чеснок','кг',15,5
   UNION ALL SELECT @t,3,'Чеснок','кг',20,5
   UNION ALL SELECT @t,1,'Орегано','кг',4,6
   UNION ALL SELECT @t,2,'Орегано','кг',5,6
   UNION ALL SELECT @t,3,'Орегано','кг',6,6
   UNION ALL SELECT @t,2,'Масло оливковое','л',20,7
   UNION ALL SELECT @t,3,'Масло оливковое','л',30,7) z WHERE @new;

-- Чесночный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Чесночный соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Чесночный соус','Дайындама','чесночный соус|соус чесночный',1010,'semi','кг',1,'Майонезная основа с чесноком и зеленью' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,900 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',820,1
   UNION ALL SELECT @t,3,'Майонез','кг',700,1
   UNION ALL SELECT @t,2,'Сметана','кг',100,2
   UNION ALL SELECT @t,3,'Сметана','кг',220,2
   UNION ALL SELECT @t,1,'Чеснок','кг',40,3
   UNION ALL SELECT @t,2,'Чеснок','кг',50,3
   UNION ALL SELECT @t,3,'Чеснок','кг',60,3
   UNION ALL SELECT @t,1,'Укроп','кг',20,4
   UNION ALL SELECT @t,2,'Укроп','кг',25,4
   UNION ALL SELECT @t,3,'Укроп','кг',30,4) z WHERE @new;

-- Белый соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Белый соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Белый соус','Дайындама','белый соус|соус для шаурмы|соус шаурма',1011,'semi','кг',1,'Соус для шаурмы и лаваша' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,650 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',550,1
   UNION ALL SELECT @t,3,'Майонез','кг',450,1
   UNION ALL SELECT @t,1,'Сметана','кг',300,2
   UNION ALL SELECT @t,2,'Сметана','кг',400,2
   UNION ALL SELECT @t,3,'Сметана','кг',500,2
   UNION ALL SELECT @t,1,'Чеснок','кг',25,3
   UNION ALL SELECT @t,2,'Чеснок','кг',25,3
   UNION ALL SELECT @t,3,'Чеснок','кг',25,3
   UNION ALL SELECT @t,1,'Укроп','кг',20,4
   UNION ALL SELECT @t,2,'Укроп','кг',20,4
   UNION ALL SELECT @t,3,'Укроп','кг',20,4
   UNION ALL SELECT @t,1,'Соль','кг',5,5
   UNION ALL SELECT @t,2,'Соль','кг',5,5
   UNION ALL SELECT @t,3,'Соль','кг',5,5) z WHERE @new;

-- Соус унаги
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус унаги' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус унаги','Дайындама','соус унаги|унаги соус|соус угорь|unagi',1012,'semi','кг',1,'Уваренный соевый соус с сахаром и мирином' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Соевый соус' c,'л' d,500 e,1 f
   UNION ALL SELECT @t,2,'Соевый соус','л',420,1
   UNION ALL SELECT @t,3,'Соевый соус','л',400,1
   UNION ALL SELECT @t,1,'Сахар','кг',350,2
   UNION ALL SELECT @t,2,'Сахар','кг',300,2
   UNION ALL SELECT @t,3,'Сахар','кг',280,2
   UNION ALL SELECT @t,2,'Мирин','л',200,3
   UNION ALL SELECT @t,3,'Мирин','л',250,3
   UNION ALL SELECT @t,1,'Крахмал','кг',30,4
   UNION ALL SELECT @t,2,'Крахмал','кг',25,4
   UNION ALL SELECT @t,3,'Крахмал','кг',20,4) z WHERE @new;

-- Соус спайси
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус спайси' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус спайси','Дайындама','соус спайси|спайси соус|spicy соус|спайси',1013,'semi','кг',1,'Майонез + острый соус чили' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,870 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',820,1
   UNION ALL SELECT @t,3,'Майонез','кг',760,1
   UNION ALL SELECT @t,1,'Соус чили','кг',130,2
   UNION ALL SELECT @t,2,'Соус чили','кг',160,2
   UNION ALL SELECT @t,3,'Соус чили','кг',180,2
   UNION ALL SELECT @t,2,'Масло кунжутное','л',10,3
   UNION ALL SELECT @t,3,'Масло кунжутное','л',15,3
   UNION ALL SELECT @t,3,'Тобико','кг',40,4) z WHERE @new;

-- Соус запеченный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус запеченный' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус запеченный','Дайындама','соус запеченный|запеченный соус|соус для запечки|запечка',1014,'semi','кг',1,'Шапка для запечённых роллов' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,700 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',550,1
   UNION ALL SELECT @t,3,'Майонез','кг',450,1
   UNION ALL SELECT @t,1,'Соус чили','кг',100,2
   UNION ALL SELECT @t,2,'Соус чили','кг',100,2
   UNION ALL SELECT @t,3,'Соус чили','кг',80,2
   UNION ALL SELECT @t,1,'Сыр','кг',200,3
   UNION ALL SELECT @t,2,'Сыр','кг',150,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',200,4
   UNION ALL SELECT @t,3,'Сыр творожный','кг',350,4
   UNION ALL SELECT @t,3,'Тобико','кг',40,5) z WHERE @new;

-- Кляр
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Кляр' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Кляр','Дайындама','кляр|темпура кляр|кляр темпура',1015,'semi','кг',1,'Кляр для темпуры; ~600 мл холодной воды на 1 кг готового' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Мука' c,'кг' d,330 e,1 f
   UNION ALL SELECT @t,2,'Смесь темпура','кг',380,2
   UNION ALL SELECT @t,3,'Смесь темпура','кг',400,2
   UNION ALL SELECT @t,1,'Крахмал','кг',50,3
   UNION ALL SELECT @t,1,'Яйцо','шт',2,4
   UNION ALL SELECT @t,2,'Яйцо','шт',2,4
   UNION ALL SELECT @t,3,'Яйцо','шт',3,4
   UNION ALL SELECT @t,1,'Соль','кг',5,5
   UNION ALL SELECT @t,2,'Соль','кг',3,5
   UNION ALL SELECT @t,3,'Соль','кг',3,5) z WHERE @new;

-- Сливочный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сливочный соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Сливочный соус','Дайындама','сливочный соус|соус сливочный|соус альфредо',1016,'semi','кг',1,'Сливки с сыром, для пиццы и пасты' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сливки' c,'л' d,700 e,1 f
   UNION ALL SELECT @t,2,'Сливки','л',780,1
   UNION ALL SELECT @t,3,'Сливки','л',820,1
   UNION ALL SELECT @t,1,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Сыр','кг',120,2
   UNION ALL SELECT @t,2,'Пармезан','кг',30,3
   UNION ALL SELECT @t,3,'Пармезан','кг',120,3
   UNION ALL SELECT @t,1,'Масло сливочное','кг',50,4
   UNION ALL SELECT @t,2,'Масло сливочное','кг',50,4
   UNION ALL SELECT @t,3,'Масло сливочное','кг',60,4
   UNION ALL SELECT @t,1,'Мука','кг',30,5
   UNION ALL SELECT @t,2,'Мука','кг',20,5
   UNION ALL SELECT @t,3,'Мука','кг',15,5
   UNION ALL SELECT @t,1,'Чеснок','кг',10,6
   UNION ALL SELECT @t,2,'Чеснок','кг',10,6
   UNION ALL SELECT @t,3,'Чеснок','кг',10,6) z WHERE @new;

-- Сырный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырный соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Сырный соус','Дайындама','сырный соус|соус сырный|чиз соус',1017,'semi','кг',1,'Плавленый сыр или чеддер со сливками' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сыр плавленый' c,'кг' d,550 e,1 f
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',420,2
   UNION ALL SELECT @t,3,'Сыр чеддер','кг',480,2
   UNION ALL SELECT @t,1,'Сливки','л',400,3
   UNION ALL SELECT @t,2,'Сливки','л',470,3
   UNION ALL SELECT @t,3,'Сливки','л',450,3
   UNION ALL SELECT @t,1,'Масло сливочное','кг',40,4
   UNION ALL SELECT @t,2,'Масло сливочное','кг',50,4
   UNION ALL SELECT @t,3,'Масло сливочное','кг',50,4
   UNION ALL SELECT @t,1,'Крахмал','кг',15,5
   UNION ALL SELECT @t,2,'Крахмал','кг',15,5
   UNION ALL SELECT @t,3,'Крахмал','кг',10,5) z WHERE @new;

-- Соус бургер
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус бургер' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус бургер','Дайындама','соус бургер|бургер соус|соус для бургера',1018,'semi','кг',1,'Майонез, кетчуп, маринованные огурцы, горчица' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,620 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',580,1
   UNION ALL SELECT @t,3,'Майонез','кг',540,1
   UNION ALL SELECT @t,1,'Кетчуп','кг',200,2
   UNION ALL SELECT @t,2,'Кетчуп','кг',180,2
   UNION ALL SELECT @t,3,'Кетчуп','кг',160,2
   UNION ALL SELECT @t,1,'Огурцы маринованные','кг',120,3
   UNION ALL SELECT @t,2,'Огурцы маринованные','кг',150,3
   UNION ALL SELECT @t,3,'Огурцы маринованные','кг',170,3
   UNION ALL SELECT @t,1,'Горчица','кг',50,4
   UNION ALL SELECT @t,2,'Горчица','кг',60,4
   UNION ALL SELECT @t,3,'Горчица','кг',60,4
   UNION ALL SELECT @t,2,'Лук репчатый','кг',30,5
   UNION ALL SELECT @t,3,'Лук репчатый','кг',50,5) z WHERE @new;

-- Соус терияки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус терияки' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус терияки','Дайындама','соус терияки|терияки',1019,'semi','кг',1,'Соевый соус, сахар, имбирь, чеснок, загуститель' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Соевый соус' c,'л' d,550 e,1 f
   UNION ALL SELECT @t,2,'Соевый соус','л',480,1
   UNION ALL SELECT @t,3,'Соевый соус','л',450,1
   UNION ALL SELECT @t,1,'Сахар','кг',280,2
   UNION ALL SELECT @t,2,'Сахар','кг',250,2
   UNION ALL SELECT @t,3,'Сахар','кг',200,2
   UNION ALL SELECT @t,2,'Мирин','л',150,3
   UNION ALL SELECT @t,3,'Мирин','л',200,3
   UNION ALL SELECT @t,3,'Мед','кг',60,4
   UNION ALL SELECT @t,1,'Имбирь','кг',20,5
   UNION ALL SELECT @t,2,'Имбирь','кг',20,5
   UNION ALL SELECT @t,3,'Имбирь','кг',25,5
   UNION ALL SELECT @t,1,'Чеснок','кг',15,6
   UNION ALL SELECT @t,2,'Чеснок','кг',15,6
   UNION ALL SELECT @t,3,'Чеснок','кг',15,6
   UNION ALL SELECT @t,1,'Крахмал','кг',25,7
   UNION ALL SELECT @t,2,'Крахмал','кг',25,7
   UNION ALL SELECT @t,3,'Крахмал','кг',20,7) z WHERE @new;

-- Соус цезарь
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус цезарь' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус цезарь','Дайындама','соус цезарь|цезарь соус',1020,'semi','кг',1,'Майонез, пармезан, чеснок, горчица, лимон' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,820 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',760,1
   UNION ALL SELECT @t,3,'Майонез','кг',700,1
   UNION ALL SELECT @t,1,'Пармезан','кг',40,2
   UNION ALL SELECT @t,2,'Пармезан','кг',80,2
   UNION ALL SELECT @t,3,'Пармезан','кг',100,2
   UNION ALL SELECT @t,1,'Чеснок','кг',15,3
   UNION ALL SELECT @t,2,'Чеснок','кг',15,3
   UNION ALL SELECT @t,3,'Чеснок','кг',15,3
   UNION ALL SELECT @t,1,'Горчица','кг',30,4
   UNION ALL SELECT @t,2,'Горчица','кг',30,4
   UNION ALL SELECT @t,3,'Горчица','кг',30,4
   UNION ALL SELECT @t,1,'Сок лимона','л',30,5
   UNION ALL SELECT @t,2,'Сок лимона','л',40,5
   UNION ALL SELECT @t,3,'Сок лимона','л',40,5
   UNION ALL SELECT @t,3,'Анчоусы','кг',25,6) z WHERE @new;

-- Соус ранч
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус ранч' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус ранч','Дайындама','соус ранч|ранч',1021,'semi','кг',1,'Майонез, сметана, кефир, чеснок, зелень' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,550 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',500,1
   UNION ALL SELECT @t,3,'Майонез','кг',450,1
   UNION ALL SELECT @t,1,'Сметана','кг',300,2
   UNION ALL SELECT @t,2,'Сметана','кг',320,2
   UNION ALL SELECT @t,3,'Сметана','кг',350,2
   UNION ALL SELECT @t,1,'Кефир','л',130,3
   UNION ALL SELECT @t,2,'Кефир','л',150,3
   UNION ALL SELECT @t,3,'Кефир','л',170,3
   UNION ALL SELECT @t,1,'Чеснок','кг',10,4
   UNION ALL SELECT @t,2,'Чеснок','кг',10,4
   UNION ALL SELECT @t,3,'Чеснок','кг',10,4
   UNION ALL SELECT @t,1,'Укроп','кг',15,5
   UNION ALL SELECT @t,2,'Укроп','кг',15,5
   UNION ALL SELECT @t,3,'Укроп','кг',15,5
   UNION ALL SELECT @t,2,'Петрушка','кг',10,6
   UNION ALL SELECT @t,3,'Петрушка','кг',10,6) z WHERE @new;

-- Ореховый соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Ореховый соус' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Ореховый соус','Дайындама','ореховый соус|соус ореховый|кунжутный соус|гомадари',1022,'semi','кг',1,'Арахисовая/кунжутная паста с соевым соусом' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Арахисовая паста' c,'кг' d,500 e,1 f
   UNION ALL SELECT @t,2,'Арахисовая паста','кг',450,1
   UNION ALL SELECT @t,3,'Паста кунжутная','кг',450,2
   UNION ALL SELECT @t,1,'Соевый соус','л',200,3
   UNION ALL SELECT @t,2,'Соевый соус','л',200,3
   UNION ALL SELECT @t,3,'Соевый соус','л',200,3
   UNION ALL SELECT @t,1,'Сахар','кг',80,4
   UNION ALL SELECT @t,2,'Сахар','кг',70,4
   UNION ALL SELECT @t,3,'Сахар','кг',60,4
   UNION ALL SELECT @t,2,'Масло кунжутное','л',25,5
   UNION ALL SELECT @t,3,'Масло кунжутное','л',30,5
   UNION ALL SELECT @t,1,'Уксус рисовый','л',40,6
   UNION ALL SELECT @t,2,'Уксус рисовый','л',40,6
   UNION ALL SELECT @t,3,'Уксус рисовый','л',40,6) z WHERE @new;

-- Соус кимчи
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус кимчи' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус кимчи','Дайындама','соус кимчи|кимчи соус',1023,'semi','кг',1,'Майонез + паста кимчи' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,750 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',700,1
   UNION ALL SELECT @t,3,'Майонез','кг',650,1
   UNION ALL SELECT @t,1,'Паста кимчи','кг',250,2
   UNION ALL SELECT @t,2,'Паста кимчи','кг',300,2
   UNION ALL SELECT @t,3,'Паста кимчи','кг',330,2
   UNION ALL SELECT @t,3,'Масло кунжутное','л',20,3) z WHERE @new;

-- Соус тартар
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соус тартар' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соус тартар','Дайындама','соус тартар|тартар',1024,'semi','кг',1,'Майонез, маринованные огурцы, лук, укроп' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Майонез' c,'кг' d,750 e,1 f
   UNION ALL SELECT @t,2,'Майонез','кг',700,1
   UNION ALL SELECT @t,3,'Майонез','кг',650,1
   UNION ALL SELECT @t,1,'Огурцы маринованные','кг',180,2
   UNION ALL SELECT @t,2,'Огурцы маринованные','кг',200,2
   UNION ALL SELECT @t,3,'Огурцы маринованные','кг',220,2
   UNION ALL SELECT @t,1,'Лук репчатый','кг',40,3
   UNION ALL SELECT @t,2,'Лук репчатый','кг',50,3
   UNION ALL SELECT @t,3,'Лук репчатый','кг',50,3
   UNION ALL SELECT @t,1,'Укроп','кг',15,4
   UNION ALL SELECT @t,2,'Укроп','кг',20,4
   UNION ALL SELECT @t,3,'Укроп','кг',20,4
   UNION ALL SELECT @t,1,'Сок лимона','л',15,5
   UNION ALL SELECT @t,2,'Сок лимона','л',20,5
   UNION ALL SELECT @t,3,'Сок лимона','л',25,5
   UNION ALL SELECT @t,3,'Каперсы','кг',30,6) z WHERE @new;

-- Соя готовая
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соя готовая' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Соя готовая','Дайындама','соя готовая|соевый соус порционный|соя разбавленная',1025,'semi','кг',2,'Соевый соус, разбавленный водой 1:1 для порций' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Соевый соус' c,'л' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Соевый соус','л',1100,1
   UNION ALL SELECT @t,3,'Соевый соус','л',1300,1) z WHERE @new;

-- Бульон куриный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Бульон куриный' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Бульон куриный','Дайындама','бульон куриный|куриный бульон',1026,'semi','л',10,'~3 кг курицы на 12 л воды → 10 л бульона' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Курица суповой набор' c,'кг' d,3000 e,1 f
   UNION ALL SELECT @t,2,'Курица тушка','кг',3000,2
   UNION ALL SELECT @t,3,'Курица тушка','кг',3500,2
   UNION ALL SELECT @t,1,'Лук репчатый','кг',300,3
   UNION ALL SELECT @t,2,'Лук репчатый','кг',300,3
   UNION ALL SELECT @t,3,'Лук репчатый','кг',300,3
   UNION ALL SELECT @t,1,'Морковь','кг',300,4
   UNION ALL SELECT @t,2,'Морковь','кг',300,4
   UNION ALL SELECT @t,3,'Морковь','кг',300,4
   UNION ALL SELECT @t,1,'Соль','кг',60,5
   UNION ALL SELECT @t,2,'Соль','кг',60,5
   UNION ALL SELECT @t,3,'Соль','кг',60,5
   UNION ALL SELECT @t,1,'Лавровый лист','кг',2,6
   UNION ALL SELECT @t,2,'Лавровый лист','кг',2,6
   UNION ALL SELECT @t,3,'Лавровый лист','кг',2,6
   UNION ALL SELECT @t,1,'Перец горошком','кг',5,7
   UNION ALL SELECT @t,2,'Перец горошком','кг',5,7
   UNION ALL SELECT @t,3,'Перец горошком','кг',5,7) z WHERE @new;

-- Бульон том-ям
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Бульон том-ям' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Бульон том-ям','Дайындама','бульон том-ям|бульон том ям|том ям основа|база том ям',1027,'semi','л',5,'Куриный бульон + паста том-ям, кокосовое молоко, травы' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бульон куриный' c,'л' d,4800 e,1 f
   UNION ALL SELECT @t,2,'Бульон куриный','л',4300,1
   UNION ALL SELECT @t,3,'Бульон куриный','л',4000,1
   UNION ALL SELECT @t,1,'Паста том-ям','кг',150,2
   UNION ALL SELECT @t,2,'Паста том-ям','кг',200,2
   UNION ALL SELECT @t,3,'Паста том-ям','кг',250,2
   UNION ALL SELECT @t,2,'Кокосовое молоко','л',500,3
   UNION ALL SELECT @t,3,'Кокосовое молоко','л',800,3
   UNION ALL SELECT @t,1,'Сахар','кг',30,4
   UNION ALL SELECT @t,2,'Сахар','кг',30,4
   UNION ALL SELECT @t,3,'Сахар','кг',30,4
   UNION ALL SELECT @t,1,'Соус рыбный','л',30,5
   UNION ALL SELECT @t,2,'Соус рыбный','л',40,5
   UNION ALL SELECT @t,3,'Соус рыбный','л',50,5
   UNION ALL SELECT @t,2,'Лемонграсс','кг',30,6
   UNION ALL SELECT @t,3,'Лемонграсс','кг',50,6
   UNION ALL SELECT @t,2,'Листья лайма','кг',5,7
   UNION ALL SELECT @t,3,'Листья лайма','кг',10,7
   UNION ALL SELECT @t,3,'Галангал','кг',30,8) z WHERE @new;

-- Рис отварной
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Рис отварной' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Рис отварной','Дайындама','рис отварной|рис гарнир|рис вареный',1028,'semi','кг',2.5,'1 кг риса → ~2,5 кг готового' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис длиннозерный' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Рис длиннозерный','кг',1000,1
   UNION ALL SELECT @t,3,'Рис жасмин','кг',1000,2
   UNION ALL SELECT @t,1,'Соль','кг',15,3
   UNION ALL SELECT @t,2,'Соль','кг',15,3
   UNION ALL SELECT @t,3,'Соль','кг',15,3
   UNION ALL SELECT @t,1,'Масло растительное','кг',20,4
   UNION ALL SELECT @t,2,'Масло растительное','кг',20,4
   UNION ALL SELECT @t,3,'Масло растительное','кг',20,4) z WHERE @new;

-- Лапша отварная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Лапша отварная' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Лапша отварная','Дайындама','лапша отварная|лапша вареная|лапша готовая',1029,'semi','кг',2.2,'1 кг сухой лапши → ~2,2 кг' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лапша яичная' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Лапша яичная','кг',1000,1
   UNION ALL SELECT @t,3,'Лапша удон','кг',1000,2
   UNION ALL SELECT @t,1,'Масло растительное','кг',30,3
   UNION ALL SELECT @t,2,'Масло растительное','кг',30,3
   UNION ALL SELECT @t,3,'Масло растительное','кг',30,3
   UNION ALL SELECT @t,1,'Соль','кг',10,4
   UNION ALL SELECT @t,2,'Соль','кг',10,4
   UNION ALL SELECT @t,3,'Соль','кг',10,4) z WHERE @new;

-- Овощи WOK
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Овощи WOK' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Овощи WOK','Дайындама','овощи wok|овощи вок|овощная смесь вок',1030,'semi','кг',1,'Нарезанные овощи для вока; отход ~15%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Капуста белокочанная' c,'кг' d,350 e,1 f
   UNION ALL SELECT @t,2,'Капуста белокочанная','кг',200,1
   UNION ALL SELECT @t,1,'Морковь','кг',300,2
   UNION ALL SELECT @t,2,'Морковь','кг',250,2
   UNION ALL SELECT @t,3,'Морковь','кг',200,2
   UNION ALL SELECT @t,1,'Лук репчатый','кг',250,3
   UNION ALL SELECT @t,2,'Лук репчатый','кг',200,3
   UNION ALL SELECT @t,3,'Лук репчатый','кг',150,3
   UNION ALL SELECT @t,1,'Перец болгарский','кг',250,4
   UNION ALL SELECT @t,2,'Перец болгарский','кг',300,4
   UNION ALL SELECT @t,3,'Перец болгарский','кг',300,4
   UNION ALL SELECT @t,2,'Кабачок','кг',200,5
   UNION ALL SELECT @t,3,'Кабачок','кг',200,5
   UNION ALL SELECT @t,3,'Брокколи','кг',300,6) z WHERE @new;

-- Крылышки разбор
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Крылышки разбор' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Крылышки разбор','Дайындама','крылышки разбор|крылья разделка|крылышки зачищенные',1031,'semi','кг',0.93,'Разделка крыльев, отход ~7%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылья куриные' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Крылья куриные','кг',1000,1
   UNION ALL SELECT @t,3,'Крылья куриные','кг',1000,1) z WHERE @new;

-- Крылышки маринованные
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Крылышки маринованные' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Крылышки маринованные','Дайындама','крылышки маринованные|крылья маринованные|маринованные крылья',1032,'semi','кг',1,'Разделанные крылья в маринаде' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылышки разбор' c,'кг' d,930 e,1 f
   UNION ALL SELECT @t,2,'Крылышки разбор','кг',930,1
   UNION ALL SELECT @t,3,'Крылышки разбор','кг',930,1
   UNION ALL SELECT @t,2,'Соевый соус','л',40,2
   UNION ALL SELECT @t,3,'Соевый соус','л',40,2
   UNION ALL SELECT @t,1,'Чеснок','кг',10,3
   UNION ALL SELECT @t,2,'Чеснок','кг',15,3
   UNION ALL SELECT @t,3,'Чеснок','кг',20,3
   UNION ALL SELECT @t,1,'Специи для курицы','кг',20,4
   UNION ALL SELECT @t,2,'Специи для курицы','кг',20,4
   UNION ALL SELECT @t,3,'Специи для курицы','кг',20,4
   UNION ALL SELECT @t,1,'Соль','кг',15,5
   UNION ALL SELECT @t,2,'Соль','кг',10,5
   UNION ALL SELECT @t,3,'Соль','кг',10,5
   UNION ALL SELECT @t,3,'Мед','кг',40,6
   UNION ALL SELECT @t,2,'Паприка','кг',8,7
   UNION ALL SELECT @t,3,'Паприка','кг',10,7) z WHERE @new;

-- Котлета куриная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Котлета куриная' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Котлета куриная','Дайындама','котлета куриная|куриная котлета|котлета для бургера курица',1033,'semi','кг',1,'Фарш из курицы с луком, формовка' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Фарш куриный' c,'кг' d,900 e,1 f
   UNION ALL SELECT @t,2,'Фарш куриный','кг',950,1
   UNION ALL SELECT @t,3,'Филе куриное','кг',950,2
   UNION ALL SELECT @t,1,'Лук репчатый','кг',100,3
   UNION ALL SELECT @t,2,'Лук репчатый','кг',70,3
   UNION ALL SELECT @t,3,'Лук репчатый','кг',60,3
   UNION ALL SELECT @t,1,'Яйцо','шт',2,4
   UNION ALL SELECT @t,2,'Яйцо','шт',2,4
   UNION ALL SELECT @t,3,'Яйцо','шт',2,4
   UNION ALL SELECT @t,1,'Сухари панировочные','кг',50,5
   UNION ALL SELECT @t,2,'Сухари панировочные','кг',30,5
   UNION ALL SELECT @t,1,'Соль','кг',12,6
   UNION ALL SELECT @t,2,'Соль','кг',12,6
   UNION ALL SELECT @t,3,'Соль','кг',12,6
   UNION ALL SELECT @t,1,'Специи для курицы','кг',5,7
   UNION ALL SELECT @t,2,'Специи для курицы','кг',5,7
   UNION ALL SELECT @t,3,'Специи для курицы','кг',6,7) z WHERE @new;

-- Котлета говяжья
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Котлета говяжья' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Котлета говяжья','Дайындама','котлета говяжья|говяжья котлета|котлета для бургера',1034,'semi','кг',1,'Говяжий фарш, формовка; ужарка при жарке учтена в граммовке блюда' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Фарш говяжий' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Фарш говяжий','кг',1000,1
   UNION ALL SELECT @t,3,'Говядина мраморная','кг',1000,2
   UNION ALL SELECT @t,1,'Соль','кг',12,3
   UNION ALL SELECT @t,2,'Соль','кг',12,3
   UNION ALL SELECT @t,3,'Соль','кг',12,3
   UNION ALL SELECT @t,1,'Перец черный молотый','кг',3,4
   UNION ALL SELECT @t,2,'Перец черный молотый','кг',3,4
   UNION ALL SELECT @t,3,'Перец черный молотый','кг',3,4) z WHERE @new;

-- Лук жареный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Лук жареный' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Лук жареный','Дайындама','лук жареный|жареный лук|лук фри|лук хрустящий',1035,'semi','кг',1,'Лук, обжаренный до золотистого; ужарка ~60%' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лук репчатый' c,'кг' d,2500 e,1 f
   UNION ALL SELECT @t,2,'Лук репчатый','кг',2500,1
   UNION ALL SELECT @t,3,'Лук репчатый','кг',2500,1
   UNION ALL SELECT @t,1,'Масло растительное','кг',150,2
   UNION ALL SELECT @t,2,'Масло растительное','кг',150,2
   UNION ALL SELECT @t,3,'Масло растительное','кг',150,2
   UNION ALL SELECT @t,1,'Мука','кг',50,3
   UNION ALL SELECT @t,2,'Мука','кг',50,3
   UNION ALL SELECT @t,3,'Мука','кг',50,3) z WHERE @new;

-- Сырная смесь
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырная смесь' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Сырная смесь','Дайындама','сырная смесь|сыр для пиццы|сыр тертый|сыр микс',1036,'semi','кг',1,'Тёртый сыр для пиццы' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сыр пиццерийный' c,'кг' d,1000 e,1 f
   UNION ALL SELECT @t,2,'Моцарелла','кг',700,2
   UNION ALL SELECT @t,3,'Моцарелла','кг',800,2
   UNION ALL SELECT @t,2,'Сыр гауда','кг',300,3
   UNION ALL SELECT @t,3,'Сыр гауда','кг',200,3) z WHERE @new;

-- Светофор
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Светофор' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Светофор','Дайындама','светофор|тобико микс|икра светофор',1037,'semi','кг',1,'Смесь тобико трёх цветов' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тобико красное' c,'кг' d,340 e,1 f
   UNION ALL SELECT @t,2,'Тобико красное','кг',340,1
   UNION ALL SELECT @t,3,'Тобико красное','кг',340,1
   UNION ALL SELECT @t,1,'Тобико зеленое','кг',330,2
   UNION ALL SELECT @t,2,'Тобико зеленое','кг',330,2
   UNION ALL SELECT @t,3,'Тобико зеленое','кг',330,2
   UNION ALL SELECT @t,1,'Тобико черное','кг',330,3
   UNION ALL SELECT @t,2,'Тобико черное','кг',330,3
   UNION ALL SELECT @t,3,'Тобико черное','кг',330,3) z WHERE @new;

-- Упаковка суши
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Упаковка суши' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Упаковка суши','Дайындама','упаковка суши|суши набор|набор к роллам',1038,'semi','шт',1,'Коробка + соусник + палочки + васаби + имбирь на 1 порцию' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Коробка суши' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,2,'Коробка суши','шт',1,1
   UNION ALL SELECT @t,3,'Коробка суши','шт',1,1
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Соусник','шт',1,2
   UNION ALL SELECT @t,3,'Соусник','шт',1,2
   UNION ALL SELECT @t,1,'Палочки','шт',1,3
   UNION ALL SELECT @t,2,'Палочки','шт',1,3
   UNION ALL SELECT @t,3,'Палочки','шт',2,3
   UNION ALL SELECT @t,1,'Васаби','кг',5,4
   UNION ALL SELECT @t,2,'Васаби','кг',7,4
   UNION ALL SELECT @t,3,'Васаби','кг',10,4
   UNION ALL SELECT @t,1,'Имбирь','кг',10,5
   UNION ALL SELECT @t,2,'Имбирь','кг',15,5
   UNION ALL SELECT @t,3,'Имбирь','кг',20,5) z WHERE @new;

-- Фарш для шаурмы
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Фарш для шаурмы' AND kind='semi' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number,kind,unit,yield_qty,ai_note) SELECT 'Фарш для шаурмы','Дайындама','мясо для шаурмы|шаурма мясо|курица для шаурмы',1039,'semi','кг',1,'Курица в маринаде, обжаренная на гриле' FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бедро куриное' c,'кг' d,1400 e,1 f
   UNION ALL SELECT @t,2,'Филе куриное','кг',1350,2
   UNION ALL SELECT @t,3,'Филе куриное','кг',1300,2
   UNION ALL SELECT @t,1,'Специи для шаурмы','кг',20,3
   UNION ALL SELECT @t,2,'Специи для шаурмы','кг',20,3
   UNION ALL SELECT @t,3,'Специи для шаурмы','кг',25,3
   UNION ALL SELECT @t,2,'Кефир','л',60,4
   UNION ALL SELECT @t,3,'Кефир','л',60,4
   UNION ALL SELECT @t,1,'Масло растительное','кг',30,5
   UNION ALL SELECT @t,2,'Масло растительное','кг',30,5
   UNION ALL SELECT @t,3,'Масло растительное','кг',30,5
   UNION ALL SELECT @t,1,'Соль','кг',15,6
   UNION ALL SELECT @t,2,'Соль','кг',15,6
   UNION ALL SELECT @t,3,'Соль','кг',15,6) z WHERE @new;

-- Құрамы дайындама болатын шаблон жолдары (тағамда да, дайындамада да) сол дайындама шаблонына байланады
UPDATE grammovka_tpl_item i JOIN grammovka_tpl s ON s.kind = 'semi' AND s.arh = 0 AND LOWER(s.name_ru) = LOWER(i.name_ru) AND s.id <> i.tpl_id
  SET i.semi_id = s.id WHERE i.semi_id IS NULL;

COMMIT;
