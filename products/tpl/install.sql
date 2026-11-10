-- Граммовка шаблондары (барлық компанияға ортақ). berik_004. Жасалған: 2026-11-09
-- АЛДЫМЕН БЭКАП. Қайта іске қосуға болады: бар шаблонға тиіспейді (әзірлеуші өзгерткені сақталады).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS grammovka_tpl (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name_ru VARCHAR(255) NOT NULL,
  cat VARCHAR(100) NULL,            -- Роллы / Пицца / Супы ...
  keys_ru VARCHAR(500) NULL,        -- тағам атымен сәйкестендіру сөздері, | арқылы
  number INT NOT NULL DEFAULT 0,
  arh TINYINT NOT NULL DEFAULT 0,
  ins_dt DATETIME DEFAULT CURRENT_TIMESTAMP,
  upd_dt DATETIME NULL,
  KEY cat (cat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grammovka_tpl_item (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tpl_id INT NOT NULL,
  variant TINYINT NOT NULL DEFAULT 2,  -- 1 Эконом, 2 Стандарт, 3 Премиум
  name_ru VARCHAR(255) NOT NULL,       -- құрам аты (компанияның product_comp.name_ru-мен сәйкестенеді)
  unit VARCHAR(20) NOT NULL DEFAULT 'кг',  -- кг (мөлшері граммен) / л (мл) / шт
  quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  number INT NOT NULL DEFAULT 0,
  KEY tpl_var (tpl_id, variant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

START TRANSACTION;

-- Филадельфия
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Филадельфия' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Филадельфия','Роллы','филадельфия|фила',1 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,3
   UNION ALL SELECT @t,1,'Семга','кг',25,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',73,5
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,3
   UNION ALL SELECT @t,2,'Семга','кг',50,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',73,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',15,6) z WHERE @new;

-- Филадельфия с огурцом
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Филадельфия с огурцом' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Филадельфия с огурцом','Роллы','филадельфия с огурцом',2 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',45,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',60,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',30,5
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,6) z WHERE @new;

-- Филадельфия гриль
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Филадельфия гриль' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Филадельфия гриль','Роллы','филадельфия гриль',3 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',50,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',65,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Соус унаги','кг',15,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Филадельфия с угрем
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Филадельфия с угрем' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Филадельфия с угрем','Роллы','филадельфия с угр',4 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',40,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',50,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Соус унаги','кг',15,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Калифорния с крабом
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Калифорния с крабом' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Калифорния с крабом','Роллы','калифорния|калифорния с крабом',5 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Тобико','кг',10,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',15,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,2,'Тобико','кг',10,6) z WHERE @new;

-- Калифорния с лососем
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Калифорния с лососем' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Калифорния с лососем','Роллы','калифорния с лосос|калифорния с семг',6 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',35,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,4
   UNION ALL SELECT @t,1,'Тобико','кг',8,5
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',45,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Тобико','кг',10,5
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Калифорния с креветкой
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Калифорния с креветкой' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Калифорния с креветкой','Роллы','калифорния с кревет',7 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Креветка','кг',35,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,4
   UNION ALL SELECT @t,1,'Тобико','кг',8,5
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Креветка','кг',45,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Тобико','кг',10,5
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Калифорния темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Калифорния темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Калифорния темпура','Роллы','калифорния темпура',8 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',40,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Кляр','кг',30,6
   UNION ALL SELECT @t,1,'Панко','кг',15,7
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,9
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',50,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Кляр','кг',40,6
   UNION ALL SELECT @t,2,'Панко','кг',20,7
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,9) z WHERE @new;

-- Запеченная Калифорния
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Запеченная Калифорния' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Запеченная Калифорния','Роллы','запеченная калифорния|калифорния запеч',9 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',40,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Соус запеченный','кг',40,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',50,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Соус запеченный','кг',50,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Аляска
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Аляска' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Аляска','Роллы','аляска',10 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Кунжут','кг',2,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',15,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,2,'Кунжут','кг',2,6) z WHERE @new;

-- Цезарь ролл
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Цезарь ролл' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Цезарь ролл','Роллы','цезарь|цезарь холодный',11 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,226 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',46,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',130,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4) z WHERE @new;

-- Цезарь запеченный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Цезарь запеченный' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Цезарь запеченный','Роллы','цезарь запеч',12 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,226 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',46,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Кляр','кг',20,5
   UNION ALL SELECT @t,1,'Панко','кг',15,6
   UNION ALL SELECT @t,1,'Соус запеченный','кг',70,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',130,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Кляр','кг',80,5
   UNION ALL SELECT @t,2,'Панко','кг',45,6
   UNION ALL SELECT @t,2,'Соус запеченный','кг',70,7) z WHERE @new;

-- Цезарь темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Цезарь темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Цезарь темпура','Роллы','цезарь темпура|цезарь жарен',13 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',60,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Салат айсберг','кг',10,5
   UNION ALL SELECT @t,1,'Кляр','кг',30,6
   UNION ALL SELECT @t,1,'Панко','кг',15,7
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,9
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',130,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Кляр','кг',80,5
   UNION ALL SELECT @t,2,'Панко','кг',45,6
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,7) z WHERE @new;

-- Америка темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Америка темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Америка темпура','Роллы','америка темпура|америка',14 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',30,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Кляр','кг',30,6
   UNION ALL SELECT @t,1,'Панко','кг',15,7
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,9
   UNION ALL SELECT @t,2,'Рис для суши','кг',195,1
   UNION ALL SELECT @t,2,'Нори','шт',1,2
   UNION ALL SELECT @t,2,'Семга','кг',35,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Кляр','кг',32,5
   UNION ALL SELECT @t,2,'Панко','кг',15,6
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,7) z WHERE @new;

-- Краб темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Краб темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Краб темпура','Роллы','краб темпура',15 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,195 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',1,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',35,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Кляр','кг',32,5
   UNION ALL SELECT @t,1,'Панко','кг',15,6
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',20,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',1,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',15,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Кляр','кг',80,5
   UNION ALL SELECT @t,2,'Панко','кг',45,6
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,7) z WHERE @new;

-- Лосось темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Лосось темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Лосось темпура','Роллы','лосось темпура|сяке темпура',16 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',40,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Кляр','кг',30,5
   UNION ALL SELECT @t,1,'Панко','кг',15,6
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',50,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Кляр','кг',40,5
   UNION ALL SELECT @t,2,'Панко','кг',20,6
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Унаги темпура
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Унаги темпура' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Унаги темпура','Роллы','унаги темпура|угорь темпура',17 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',35,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,5
   UNION ALL SELECT @t,1,'Кляр','кг',30,6
   UNION ALL SELECT @t,1,'Панко','кг',15,7
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,9
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',45,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Соус унаги','кг',15,5
   UNION ALL SELECT @t,2,'Кляр','кг',40,6
   UNION ALL SELECT @t,2,'Панко','кг',20,7
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,8
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,9) z WHERE @new;

-- Краб запеченный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Краб запеченный' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Краб запеченный','Роллы','краб запеч|ролл запеченн',18 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',45,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Соус запеченный','кг',40,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',60,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Соус запеченный','кг',50,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Дракон
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Дракон' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Дракон','Роллы','дракон',19 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',40,3
   UNION ALL SELECT @t,1,'Авокадо','кг',20,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,1,'Кунжут','кг',2,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',55,3
   UNION ALL SELECT @t,2,'Авокадо','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Соус унаги','кг',15,6
   UNION ALL SELECT @t,2,'Кунжут','кг',2,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Канада
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Канада' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Канада','Роллы','канада',20 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',30,3
   UNION ALL SELECT @t,1,'Семга','кг',20,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,6
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',40,3
   UNION ALL SELECT @t,2,'Семга','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,6
   UNION ALL SELECT @t,2,'Соус унаги','кг',10,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Сяке
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сяке' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сяке','Роллы','сяке|лосось ролл',21 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',50,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',65,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Сяке кунсей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сяке кунсей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сяке кунсей','Роллы','кунсей|копчен',22 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга копченая','кг',40,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга копченая','кг',50,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,6) z WHERE @new;

-- Унаги кунсей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Унаги кунсей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Унаги кунсей','Роллы','унаги кунсей',23 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',40,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',50,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Сяке макки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сяке макки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сяке макки','Роллы','сяке макки|саке маки|сяке маки',24 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,185 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',30,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',25,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4) z WHERE @new;

-- Каппа макки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Каппа макки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Каппа макки','Роллы','каппа|капа маки|огурец макки',25 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,185 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',35,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4) z WHERE @new;

-- Краб макки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Краб макки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Краб макки','Роллы','краб макки|краб маки',26 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,90 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',20,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Рис для суши','кг',100,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',25,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4) z WHERE @new;

-- Унаги макки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Унаги макки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Унаги макки','Роллы','унаги макки|угорь маки',27 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,90 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Угорь','кг',20,3
   UNION ALL SELECT @t,1,'Соус унаги','кг',5,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',100,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Угорь','кг',25,3
   UNION ALL SELECT @t,2,'Соус унаги','кг',5,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Кунсей макки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Кунсей макки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Кунсей макки','Роллы','кунсей макки',28 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,90 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга копченая','кг',20,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Рис для суши','кг',100,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга копченая','кг',25,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4) z WHERE @new;

-- Сырный ролл
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырный ролл' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сырный ролл','Роллы','сырный ролл|чиз ролл',29 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Стружка','кг',10,6
   UNION ALL SELECT @t,1,'Сыр (дана)','шт',2,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',200,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,2,'Стружка','кг',10,6
   UNION ALL SELECT @t,2,'Сыр (дана)','шт',2,7) z WHERE @new;

-- Гункан с лососем
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гункан с лососем' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гункан с лососем','Роллы','гункан.*лосос|гункан запеченная с лосос',30 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.25,2
   UNION ALL SELECT @t,1,'Семга','кг',15,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',10,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',30,1
   UNION ALL SELECT @t,2,'Нори','шт',0.25,2
   UNION ALL SELECT @t,2,'Семга','кг',20,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',10,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Гункан с крабом
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гункан с крабом' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гункан с крабом','Роллы','гункан.*краб',31 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.25,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',15,3
   UNION ALL SELECT @t,1,'Соус запеченный','кг',10,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',30,1
   UNION ALL SELECT @t,2,'Нори','шт',0.25,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',20,3
   UNION ALL SELECT @t,2,'Соус запеченный','кг',10,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Гункан с креветкой
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гункан с креветкой' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гункан с креветкой','Роллы','гункан.*кревет',32 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.25,2
   UNION ALL SELECT @t,1,'Креветка','кг',15,3
   UNION ALL SELECT @t,1,'Соус запеченный','кг',10,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',30,1
   UNION ALL SELECT @t,2,'Нори','шт',0.25,2
   UNION ALL SELECT @t,2,'Креветка','кг',20,3
   UNION ALL SELECT @t,2,'Соус запеченный','кг',10,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Гункан с угрем
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гункан с угрем' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гункан с угрем','Роллы','гункан.*угр|гункан.*угор',33 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.25,2
   UNION ALL SELECT @t,1,'Угорь','кг',15,3
   UNION ALL SELECT @t,1,'Соус унаги','кг',5,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',30,1
   UNION ALL SELECT @t,2,'Нори','шт',0.25,2
   UNION ALL SELECT @t,2,'Угорь','кг',20,3
   UNION ALL SELECT @t,2,'Соус унаги','кг',5,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Гункан чукка
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гункан чукка' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гункан чукка','Роллы','чукка|чука',34 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.25,2
   UNION ALL SELECT @t,1,'Салат чука','кг',15,3
   UNION ALL SELECT @t,1,'Ореховый соус','кг',5,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',30,1
   UNION ALL SELECT @t,2,'Нори','шт',0.25,2
   UNION ALL SELECT @t,2,'Салат чука','кг',20,3
   UNION ALL SELECT @t,2,'Ореховый соус','кг',5,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Инь-Янь
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Инь-Янь' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Инь-Янь','Роллы','инь|ян',35 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',25,3
   UNION ALL SELECT @t,1,'Угорь','кг',20,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Соус унаги','кг',5,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',30,3
   UNION ALL SELECT @t,2,'Угорь','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Соус унаги','кг',10,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Бонито
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Бонито' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Бонито','Роллы','бонито',36 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Стружка','кг',10,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',50,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Стружка','кг',8,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Греческий
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Греческий' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Греческий','Роллы','греческ',37 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Помидор','кг',20,3
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,1,'Сыр фета','кг',20,5
   UNION ALL SELECT @t,1,'Маслины','кг',5,6
   UNION ALL SELECT @t,1,'Салат айсберг','кг',10,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Помидор','кг',25,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',25,4
   UNION ALL SELECT @t,2,'Сыр фета','кг',25,5
   UNION ALL SELECT @t,2,'Маслины','кг',10,6
   UNION ALL SELECT @t,2,'Салат айсберг','кг',10,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Ролл с курицей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Ролл с курицей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Ролл с курицей','Роллы','ролл с курицей|чикен ролл|саше с курицей',38 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',65,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,6) z WHERE @new;

-- Ролл с креветкой
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Ролл с креветкой' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Ролл с креветкой','Роллы','ролл с креветк|эби',39 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Креветка','кг',35,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Креветка','кг',45,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,6) z WHERE @new;

-- Вулкан
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Вулкан' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Вулкан','Роллы','вулкан',40 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,100 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',30,3
   UNION ALL SELECT @t,1,'Соус спайси','кг',15,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,5
   UNION ALL SELECT @t,1,'Тобико','кг',5,6
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',110,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',40,3
   UNION ALL SELECT @t,2,'Соус спайси','кг',20,4
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,5
   UNION ALL SELECT @t,2,'Тобико','кг',8,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Футомаки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Футомаки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Футомаки','Роллы','футомаки',41 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',1,2
   UNION ALL SELECT @t,1,'Семга','кг',25,3
   UNION ALL SELECT @t,1,'Краб готовый','кг',20,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,6
   UNION ALL SELECT @t,1,'Тобико','кг',5,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',1,2
   UNION ALL SELECT @t,2,'Семга','кг',30,3
   UNION ALL SELECT @t,2,'Краб готовый','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,6
   UNION ALL SELECT @t,2,'Тобико','кг',8,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Темпура ролл (жалпы)
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Темпура ролл (жалпы)' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Темпура ролл (жалпы)','Роллы','темпура',42 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,195 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',1,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',35,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Кляр','кг',32,5
   UNION ALL SELECT @t,1,'Панко','кг',15,6
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',20,7
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',35,3
   UNION ALL SELECT @t,2,'Краб готовый','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,6
   UNION ALL SELECT @t,2,'Кляр','кг',40,7
   UNION ALL SELECT @t,2,'Панко','кг',20,8
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,9
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,10) z WHERE @new;

-- Запеченный ролл (жалпы)
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Запеченный ролл (жалпы)' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Запеченный ролл (жалпы)','Роллы','запеч',43 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,150 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',25,3
   UNION ALL SELECT @t,1,'Краб готовый','кг',20,4
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',15,6
   UNION ALL SELECT @t,1,'Соус запеченный','кг',40,7
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,8
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',35,3
   UNION ALL SELECT @t,2,'Краб готовый','кг',25,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,5
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,6
   UNION ALL SELECT @t,2,'Соус запеченный','кг',50,7
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,8) z WHERE @new;

-- Макки (жалпы)
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Макки (жалпы)' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Макки (жалпы)','Роллы','макки|маки',44 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,90 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Семга','кг',20,3
   UNION ALL SELECT @t,1,'Сыр творожный','кг',40,4
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,5
   UNION ALL SELECT @t,2,'Рис для суши','кг',100,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Семга','кг',25,3
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,4
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,5) z WHERE @new;

-- Маргарита
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Маргарита' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Маргарита','Пицца','маргарита',45 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Помидор','кг',80,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',30,3
   UNION ALL SELECT @t,2,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,2,'Помидор','кг',100,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Пепперони
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Пепперони' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Пепперони','Пицца','пепперони|пеперони',46 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',55,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',310,1
   UNION ALL SELECT @t,2,'Сыр','кг',160,2
   UNION ALL SELECT @t,2,'Красный соус','кг',30,3
   UNION ALL SELECT @t,2,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',90,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Четыре сезона
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Четыре сезона' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Четыре сезона','Пицца','4 сезон|четыре сезон',47 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Помидор','кг',80,5
   UNION ALL SELECT @t,1,'Грибы','кг',30,6
   UNION ALL SELECT @t,1,'Курица готовая','кг',30,7
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',25,8
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,9
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',30,3
   UNION ALL SELECT @t,2,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,2,'Помидор','кг',30,5
   UNION ALL SELECT @t,2,'Грибы','кг',30,6
   UNION ALL SELECT @t,2,'Курица готовая','кг',30,7
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',30,8
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,9) z WHERE @new;

-- Четыре сыра
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Четыре сыра' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Четыре сыра','Пицца','4 сыр|четыре сыр',48 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',100,2
   UNION ALL SELECT @t,1,'Сливочный соус','кг',30,3
   UNION ALL SELECT @t,1,'Сыр чеддер','кг',25,4
   UNION ALL SELECT @t,1,'Сыр дор блю','кг',15,5
   UNION ALL SELECT @t,1,'Пармезан','кг',10,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',120,2
   UNION ALL SELECT @t,2,'Сливочный соус','кг',35,3
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',30,4
   UNION ALL SELECT @t,2,'Сыр дор блю','кг',20,5
   UNION ALL SELECT @t,2,'Пармезан','кг',15,6
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,7) z WHERE @new;

-- Сырная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырная' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сырная','Пицца','сырн',49 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',260,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,5
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',180,2
   UNION ALL SELECT @t,2,'Сливочный соус','кг',35,3
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',40,4
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,5) z WHERE @new;

-- Болоньезе
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Болоньезе' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Болоньезе','Пицца','болоньез',50 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Фарш','кг',70,5
   UNION ALL SELECT @t,1,'Светофор','кг',20,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',30,3
   UNION ALL SELECT @t,2,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,2,'Фарш','кг',90,5
   UNION ALL SELECT @t,2,'Светофор','кг',20,6
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,7) z WHERE @new;

-- Ветчина с грибами
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Ветчина с грибами' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Ветчина с грибами','Пицца','ветчина с гриб',51 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Ветчина','кг',50,4
   UNION ALL SELECT @t,1,'Грибы','кг',40,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Ветчина','кг',65,4
   UNION ALL SELECT @t,2,'Грибы','кг',50,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Курица с грибами
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Курица с грибами' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Курица с грибами','Пицца','курица с гриб|курица и гриб',52 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,5
   UNION ALL SELECT @t,1,'Грибы','кг',30,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Сливочный соус','кг',35,3
   UNION ALL SELECT @t,2,'Курица готовая','кг',65,4
   UNION ALL SELECT @t,2,'Грибы','кг',50,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Куриная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Куриная' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Куриная','Пицца','курин|с курицей',53 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Сырный соус','кг',12,5
   UNION ALL SELECT @t,1,'Курица готовая','кг',55,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Курица готовая','кг',90,4
   UNION ALL SELECT @t,2,'Помидор','кг',30,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Гавайская
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Гавайская' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Гавайская','Пицца','гавай',54 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,4
   UNION ALL SELECT @t,1,'Ананас','кг',40,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Курица готовая','кг',65,4
   UNION ALL SELECT @t,2,'Ананас','кг',50,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Мексика
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Мексика' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Мексика','Пицца','мексик',55 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Помидор','кг',12,5
   UNION ALL SELECT @t,1,'Грибы','кг',55,6
   UNION ALL SELECT @t,1,'Светофор','кг',30,7
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,8
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Фарш','кг',60,4
   UNION ALL SELECT @t,2,'Халапеньо','кг',15,5
   UNION ALL SELECT @t,2,'Перец болгарский','кг',25,6
   UNION ALL SELECT @t,2,'Кукуруза','кг',25,7
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,8) z WHERE @new;

-- Барбекю
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Барбекю' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Барбекю','Пицца','b.b.q|bbq|барбекю',56 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Соус барбекю','кг',30,3
   UNION ALL SELECT @t,1,'Курица готовая','кг',40,4
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',30,5
   UNION ALL SELECT @t,1,'Лук красный','кг',15,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Соус барбекю','кг',35,3
   UNION ALL SELECT @t,2,'Курица готовая','кг',50,4
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',40,5
   UNION ALL SELECT @t,2,'Лук красный','кг',20,6
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,7) z WHERE @new;

-- Ассорти
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Ассорти' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Ассорти','Пицца','ассорти',57 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Ветчина','кг',25,4
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',25,5
   UNION ALL SELECT @t,1,'Курица готовая','кг',25,6
   UNION ALL SELECT @t,1,'Грибы','кг',20,7
   UNION ALL SELECT @t,1,'Перец болгарский','кг',15,8
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,9
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Ветчина','кг',30,4
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',30,5
   UNION ALL SELECT @t,2,'Курица готовая','кг',30,6
   UNION ALL SELECT @t,2,'Грибы','кг',25,7
   UNION ALL SELECT @t,2,'Перец болгарский','кг',20,8
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,9) z WHERE @new;

-- Вегетарианская
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Вегетарианская' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Вегетарианская','Пицца','вегет',58 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Грибы','кг',30,4
   UNION ALL SELECT @t,1,'Помидор','кг',30,5
   UNION ALL SELECT @t,1,'Перец болгарский','кг',20,6
   UNION ALL SELECT @t,1,'Маслины','кг',10,7
   UNION ALL SELECT @t,1,'Кукуруза','кг',15,8
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,9
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Грибы','кг',40,4
   UNION ALL SELECT @t,2,'Помидор','кг',40,5
   UNION ALL SELECT @t,2,'Перец болгарский','кг',25,6
   UNION ALL SELECT @t,2,'Маслины','кг',15,7
   UNION ALL SELECT @t,2,'Кукуруза','кг',20,8
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,9) z WHERE @new;

-- Грибная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Грибная' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Грибная','Пицца','грибн',59 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Сливочный соус','кг',30,3
   UNION ALL SELECT @t,1,'Грибы','кг',70,4
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,5
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Сливочный соус','кг',35,3
   UNION ALL SELECT @t,2,'Грибы','кг',90,4
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,5) z WHERE @new;

-- Халапеньо
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Халапеньо' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Халапеньо','Пицца','халапеньо',60 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',50,4
   UNION ALL SELECT @t,1,'Халапеньо','кг',15,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',60,4
   UNION ALL SELECT @t,2,'Халапеньо','кг',20,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Пицца Ранч
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Пицца Ранч' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Пицца Ранч','Пицца','ранч',61 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Соус ранч','кг',30,3
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,4
   UNION ALL SELECT @t,1,'Ветчина','кг',30,5
   UNION ALL SELECT @t,1,'Помидор','кг',20,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Соус ранч','кг',35,3
   UNION ALL SELECT @t,2,'Курица готовая','кг',60,4
   UNION ALL SELECT @t,2,'Ветчина','кг',40,5
   UNION ALL SELECT @t,2,'Помидор','кг',30,6
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,7) z WHERE @new;

-- Пицца Филадельфия
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Пицца Филадельфия' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Пицца Филадельфия','Пицца','пицца филадельфия',62 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',110,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Чесночный соус','кг',45,4
   UNION ALL SELECT @t,1,'Семга','кг',50,5
   UNION ALL SELECT @t,1,'Сыр творожный','кг',80,6
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,7
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Сливочный соус','кг',35,3
   UNION ALL SELECT @t,2,'Семга','кг',60,4
   UNION ALL SELECT @t,2,'Сыр творожный','кг',40,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Пицца Фри
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Пицца Фри' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Пицца Фри','Пицца','пицца фри',63 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Тесто пицца' c,'кг' d,250 e,1 f
   UNION ALL SELECT @t,1,'Сыр','кг',120,2
   UNION ALL SELECT @t,1,'Красный соус','кг',30,3
   UNION ALL SELECT @t,1,'Фри','кг',60,4
   UNION ALL SELECT @t,1,'Колбаса готовая','кг',40,5
   UNION ALL SELECT @t,1,'Пицца коробка','шт',1,6
   UNION ALL SELECT @t,2,'Тесто пицца','кг',300,1
   UNION ALL SELECT @t,2,'Сыр','кг',150,2
   UNION ALL SELECT @t,2,'Красный соус','кг',35,3
   UNION ALL SELECT @t,2,'Фри','кг',80,4
   UNION ALL SELECT @t,2,'Колбаса готовая','кг',50,5
   UNION ALL SELECT @t,2,'Пицца коробка','шт',1,6) z WHERE @new;

-- Том-ям с креветками
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Том-ям с креветками' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Том-ям с креветками','Супы','том-ям|том ям|том-ям с кревет',64 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бульон том-ям' c,'л' d,250 e,1 f
   UNION ALL SELECT @t,1,'Кокосовое молоко','л',50,2
   UNION ALL SELECT @t,1,'Креветка','кг',40,3
   UNION ALL SELECT @t,1,'Грибы','кг',25,4
   UNION ALL SELECT @t,1,'Помидор','кг',15,5
   UNION ALL SELECT @t,1,'Упаковка суп','шт',1,7
   UNION ALL SELECT @t,2,'Бульон том-ям','л',300,1
   UNION ALL SELECT @t,2,'Кокосовое молоко','л',70,2
   UNION ALL SELECT @t,2,'Креветка','кг',60,3
   UNION ALL SELECT @t,2,'Грибы','кг',30,4
   UNION ALL SELECT @t,2,'Помидор','кг',20,5
   UNION ALL SELECT @t,2,'Рис отварной','кг',80,6
   UNION ALL SELECT @t,2,'Упаковка суп','шт',1,7) z WHERE @new;

-- Том-ям с курицей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Том-ям с курицей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Том-ям с курицей','Супы','том-ям с куриц|том ям с куриц',65 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бульон том-ям' c,'л' d,250 e,1 f
   UNION ALL SELECT @t,1,'Кокосовое молоко','л',50,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,3
   UNION ALL SELECT @t,1,'Грибы','кг',25,4
   UNION ALL SELECT @t,1,'Помидор','кг',15,5
   UNION ALL SELECT @t,1,'Упаковка суп','шт',1,7
   UNION ALL SELECT @t,2,'Бульон том-ям','л',300,1
   UNION ALL SELECT @t,2,'Кокосовое молоко','л',70,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',70,3
   UNION ALL SELECT @t,2,'Грибы','кг',30,4
   UNION ALL SELECT @t,2,'Помидор','кг',20,5
   UNION ALL SELECT @t,2,'Рис отварной','кг',80,6
   UNION ALL SELECT @t,2,'Упаковка суп','шт',1,7) z WHERE @new;

-- Рамен с курицей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Рамен с курицей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Рамен с курицей','Супы','рамен|рамен с куриц',66 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бульон куриный' c,'л' d,300 e,1 f
   UNION ALL SELECT @t,1,'Лапша рамен','кг',80,2
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,3
   UNION ALL SELECT @t,1,'Яйцо','шт',0.5,4
   UNION ALL SELECT @t,1,'Лук зеленый','кг',5,5
   UNION ALL SELECT @t,1,'Упаковка суп','шт',1,7
   UNION ALL SELECT @t,2,'Бульон куриный','л',350,1
   UNION ALL SELECT @t,2,'Лапша рамен','кг',100,2
   UNION ALL SELECT @t,2,'Курица готовая','кг',70,3
   UNION ALL SELECT @t,2,'Яйцо','шт',1,4
   UNION ALL SELECT @t,2,'Лук зеленый','кг',5,5
   UNION ALL SELECT @t,2,'Нори','шт',0.25,6
   UNION ALL SELECT @t,2,'Упаковка суп','шт',1,7) z WHERE @new;

-- Рамен с креветками
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Рамен с креветками' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Рамен с креветками','Супы','рамен с кревет',67 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Бульон куриный' c,'л' d,300 e,1 f
   UNION ALL SELECT @t,1,'Лапша рамен','кг',80,2
   UNION ALL SELECT @t,1,'Креветка','кг',40,3
   UNION ALL SELECT @t,1,'Яйцо','шт',0.5,4
   UNION ALL SELECT @t,1,'Лук зеленый','кг',5,5
   UNION ALL SELECT @t,1,'Упаковка суп','шт',1,6
   UNION ALL SELECT @t,2,'Бульон куриный','л',350,1
   UNION ALL SELECT @t,2,'Лапша рамен','кг',100,2
   UNION ALL SELECT @t,2,'Креветка','кг',60,3
   UNION ALL SELECT @t,2,'Яйцо','шт',1,4
   UNION ALL SELECT @t,2,'Лук зеленый','кг',5,5
   UNION ALL SELECT @t,2,'Упаковка суп','шт',1,6) z WHERE @new;

-- WOK-лапша с курицей
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='WOK-лапша с курицей' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'WOK-лапша с курицей','Супы','wok|вок|лапша с куриц',68 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лапша удон' c,'кг' d,120 e,1 f
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,2
   UNION ALL SELECT @t,1,'Овощи WOK','кг',50,3
   UNION ALL SELECT @t,1,'Соус терияки','кг',25,4
   UNION ALL SELECT @t,1,'Масло растительное','кг',10,5
   UNION ALL SELECT @t,1,'Кунжут','кг',2,6
   UNION ALL SELECT @t,1,'Упаковка WOK','шт',1,7
   UNION ALL SELECT @t,2,'Лапша удон','кг',150,1
   UNION ALL SELECT @t,2,'Курица готовая','кг',70,2
   UNION ALL SELECT @t,2,'Овощи WOK','кг',60,3
   UNION ALL SELECT @t,2,'Соус терияки','кг',30,4
   UNION ALL SELECT @t,2,'Масло растительное','кг',10,5
   UNION ALL SELECT @t,2,'Кунжут','кг',2,6
   UNION ALL SELECT @t,2,'Упаковка WOK','шт',1,7) z WHERE @new;

-- WOK-лапша с креветками
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='WOK-лапша с креветками' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'WOK-лапша с креветками','Супы','wok.*кревет|вок.*кревет|лапша с кревет',69 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лапша удон' c,'кг' d,120 e,1 f
   UNION ALL SELECT @t,1,'Креветка','кг',40,2
   UNION ALL SELECT @t,1,'Овощи WOK','кг',50,3
   UNION ALL SELECT @t,1,'Соус терияки','кг',25,4
   UNION ALL SELECT @t,1,'Масло растительное','кг',10,5
   UNION ALL SELECT @t,1,'Кунжут','кг',2,6
   UNION ALL SELECT @t,1,'Упаковка WOK','шт',1,7
   UNION ALL SELECT @t,2,'Лапша удон','кг',150,1
   UNION ALL SELECT @t,2,'Креветка','кг',60,2
   UNION ALL SELECT @t,2,'Овощи WOK','кг',60,3
   UNION ALL SELECT @t,2,'Соус терияки','кг',30,4
   UNION ALL SELECT @t,2,'Масло растительное','кг',10,5
   UNION ALL SELECT @t,2,'Кунжут','кг',2,6
   UNION ALL SELECT @t,2,'Упаковка WOK','шт',1,7) z WHERE @new;

-- Фри
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Фри' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Фри','Закуски','фри|картофель фри',70 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Фри' c,'кг' d,200 e,1 f
   UNION ALL SELECT @t,1,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,3
   UNION ALL SELECT @t,2,'Фри','кг',200,1
   UNION ALL SELECT @t,2,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,3) z WHERE @new;

-- Наггетсы
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Наггетсы' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Наггетсы','Закуски','наггетс',71 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Наггетсы' c,'кг' d,95 e,1 f
   UNION ALL SELECT @t,1,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,3
   UNION ALL SELECT @t,2,'Наггетсы','кг',95,1
   UNION ALL SELECT @t,2,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,3) z WHERE @new;

-- Сырные палочки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырные палочки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сырные палочки','Закуски','сырные палочк',72 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сырные палочки' c,'кг' d,90 e,1 f
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,2
   UNION ALL SELECT @t,1,'Упаковка фри','шт',1,3
   UNION ALL SELECT @t,2,'Сырные палочки','кг',120,1
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,2
   UNION ALL SELECT @t,2,'Упаковка фри','шт',1,3) z WHERE @new;

-- Луковые кольца
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Луковые кольца' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Луковые кольца','Закуски','луковые кольц',73 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Луковые кольца' c,'кг' d,80 e,1 f
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,2
   UNION ALL SELECT @t,1,'Упаковка фри','шт',1,3
   UNION ALL SELECT @t,2,'Луковые кольца','кг',100,1
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,2
   UNION ALL SELECT @t,2,'Упаковка фри','шт',1,3) z WHERE @new;

-- Куриные крылышки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Куриные крылышки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Куриные крылышки','Закуски','крылышк',74 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылышки маринованные' c,'кг' d,300 e,1 f
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',25,2
   UNION ALL SELECT @t,1,'Чикен пакет','шт',1,3
   UNION ALL SELECT @t,2,'Крылышки маринованные','кг',400,1
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',35,2
   UNION ALL SELECT @t,2,'Чикен пакет','шт',1,3) z WHERE @new;

-- Чизбургер говяжий
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Чизбургер говяжий' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Чизбургер говяжий','Закуски','чизбургер говяж|бургер говяж|чизбургер',75 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Булочка бургер' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,1,'Котлета говяжья','кг',80,2
   UNION ALL SELECT @t,1,'Сыр чеддер','кг',15,3
   UNION ALL SELECT @t,1,'Помидор','кг',15,4
   UNION ALL SELECT @t,1,'Огурцы маринованные','кг',10,5
   UNION ALL SELECT @t,1,'Салат айсберг','кг',10,6
   UNION ALL SELECT @t,1,'Соус бургер','кг',15,7
   UNION ALL SELECT @t,1,'Упаковка бургер','шт',1,8
   UNION ALL SELECT @t,2,'Булочка бургер','шт',1,1
   UNION ALL SELECT @t,2,'Котлета говяжья','кг',100,2
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',20,3
   UNION ALL SELECT @t,2,'Помидор','кг',20,4
   UNION ALL SELECT @t,2,'Огурцы маринованные','кг',15,5
   UNION ALL SELECT @t,2,'Салат айсберг','кг',10,6
   UNION ALL SELECT @t,2,'Соус бургер','кг',20,7
   UNION ALL SELECT @t,2,'Упаковка бургер','шт',1,8) z WHERE @new;

-- Чизбургер куриный
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Чизбургер куриный' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Чизбургер куриный','Закуски','чизбургер курин|бургер курин',76 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Булочка бургер' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,1,'Котлета куриная','кг',80,2
   UNION ALL SELECT @t,1,'Сыр чеддер','кг',15,3
   UNION ALL SELECT @t,1,'Помидор','кг',15,4
   UNION ALL SELECT @t,1,'Салат айсберг','кг',10,5
   UNION ALL SELECT @t,1,'Соус бургер','кг',15,6
   UNION ALL SELECT @t,1,'Упаковка бургер','шт',1,7
   UNION ALL SELECT @t,2,'Булочка бургер','шт',1,1
   UNION ALL SELECT @t,2,'Котлета куриная','кг',100,2
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',20,3
   UNION ALL SELECT @t,2,'Помидор','кг',20,4
   UNION ALL SELECT @t,2,'Салат айсберг','кг',10,5
   UNION ALL SELECT @t,2,'Соус бургер','кг',20,6
   UNION ALL SELECT @t,2,'Упаковка бургер','шт',1,7) z WHERE @new;

-- Клаб-сэндвич
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Клаб-сэндвич' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Клаб-сэндвич','Закуски','клаб|сэндвич',77 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Хлеб тостовый' c,'шт' d,3 e,1 f
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,2
   UNION ALL SELECT @t,1,'Сыр чеддер','кг',15,4
   UNION ALL SELECT @t,1,'Помидор','кг',20,5
   UNION ALL SELECT @t,1,'Салат айсберг','кг',10,6
   UNION ALL SELECT @t,1,'Майонез','кг',15,7
   UNION ALL SELECT @t,1,'Упаковка бургер','шт',1,9
   UNION ALL SELECT @t,2,'Хлеб тостовый','шт',3,1
   UNION ALL SELECT @t,2,'Курица готовая','кг',70,2
   UNION ALL SELECT @t,2,'Ветчина','кг',20,3
   UNION ALL SELECT @t,2,'Сыр чеддер','кг',20,4
   UNION ALL SELECT @t,2,'Помидор','кг',25,5
   UNION ALL SELECT @t,2,'Салат айсберг','кг',15,6
   UNION ALL SELECT @t,2,'Майонез','кг',20,7
   UNION ALL SELECT @t,2,'Фри','кг',80,8
   UNION ALL SELECT @t,2,'Упаковка бургер','шт',1,9) z WHERE @new;

-- Шаурма куриная
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Шаурма куриная' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Шаурма куриная','Закуски','шаурма курин|шаурма|донер',78 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лаваш' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,1,'Курица готовая','кг',80,2
   UNION ALL SELECT @t,1,'Помидор','кг',20,4
   UNION ALL SELECT @t,1,'Огурцы','кг',20,5
   UNION ALL SELECT @t,1,'Капуста','кг',20,6
   UNION ALL SELECT @t,1,'Белый соус','кг',30,7
   UNION ALL SELECT @t,1,'Упаковка шаурма','шт',1,8
   UNION ALL SELECT @t,2,'Лаваш','шт',1,1
   UNION ALL SELECT @t,2,'Курица готовая','кг',110,2
   UNION ALL SELECT @t,2,'Фри','кг',40,3
   UNION ALL SELECT @t,2,'Помидор','кг',25,4
   UNION ALL SELECT @t,2,'Огурцы','кг',25,5
   UNION ALL SELECT @t,2,'Капуста','кг',25,6
   UNION ALL SELECT @t,2,'Белый соус','кг',40,7
   UNION ALL SELECT @t,2,'Упаковка шаурма','шт',1,8) z WHERE @new;

-- Шаурма говяжья
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Шаурма говяжья' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Шаурма говяжья','Закуски','шаурма говяж',79 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Лаваш' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,1,'Говядина готовая','кг',80,2
   UNION ALL SELECT @t,1,'Помидор','кг',20,4
   UNION ALL SELECT @t,1,'Огурцы','кг',20,5
   UNION ALL SELECT @t,1,'Капуста','кг',20,6
   UNION ALL SELECT @t,1,'Белый соус','кг',30,7
   UNION ALL SELECT @t,1,'Упаковка шаурма','шт',1,8
   UNION ALL SELECT @t,2,'Лаваш','шт',1,1
   UNION ALL SELECT @t,2,'Говядина готовая','кг',110,2
   UNION ALL SELECT @t,2,'Фри','кг',40,3
   UNION ALL SELECT @t,2,'Помидор','кг',25,4
   UNION ALL SELECT @t,2,'Огурцы','кг',25,5
   UNION ALL SELECT @t,2,'Капуста','кг',25,6
   UNION ALL SELECT @t,2,'Белый соус','кг',40,7
   UNION ALL SELECT @t,2,'Упаковка шаурма','шт',1,8) z WHERE @new;

-- Салат Цезарь
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Салат Цезарь' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Салат Цезарь','Салаты','салат цезарь|цезарь салат',80 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Салат айсберг' c,'кг' d,60 e,1 f
   UNION ALL SELECT @t,1,'Курица готовая','кг',50,2
   UNION ALL SELECT @t,1,'Помидор черри','кг',20,3
   UNION ALL SELECT @t,1,'Сухарики','кг',10,4
   UNION ALL SELECT @t,1,'Пармезан','кг',5,5
   UNION ALL SELECT @t,1,'Соус цезарь','кг',25,6
   UNION ALL SELECT @t,1,'Упаковка салат','шт',1,7
   UNION ALL SELECT @t,2,'Салат айсберг','кг',80,1
   UNION ALL SELECT @t,2,'Курица готовая','кг',70,2
   UNION ALL SELECT @t,2,'Помидор черри','кг',30,3
   UNION ALL SELECT @t,2,'Сухарики','кг',15,4
   UNION ALL SELECT @t,2,'Пармезан','кг',10,5
   UNION ALL SELECT @t,2,'Соус цезарь','кг',30,6
   UNION ALL SELECT @t,2,'Упаковка салат','шт',1,7) z WHERE @new;

-- Салат Греческий
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Салат Греческий' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Салат Греческий','Салаты','салат греческ',81 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Помидор' c,'кг' d,50 e,1 f
   UNION ALL SELECT @t,1,'Огурцы','кг',50,2
   UNION ALL SELECT @t,1,'Перец болгарский','кг',20,3
   UNION ALL SELECT @t,1,'Сыр фета','кг',25,4
   UNION ALL SELECT @t,1,'Маслины','кг',10,5
   UNION ALL SELECT @t,1,'Лук красный','кг',10,6
   UNION ALL SELECT @t,1,'Масло оливковое','л',10,7
   UNION ALL SELECT @t,1,'Упаковка салат','шт',1,8
   UNION ALL SELECT @t,2,'Помидор','кг',60,1
   UNION ALL SELECT @t,2,'Огурцы','кг',60,2
   UNION ALL SELECT @t,2,'Перец болгарский','кг',30,3
   UNION ALL SELECT @t,2,'Сыр фета','кг',35,4
   UNION ALL SELECT @t,2,'Маслины','кг',15,5
   UNION ALL SELECT @t,2,'Лук красный','кг',10,6
   UNION ALL SELECT @t,2,'Масло оливковое','л',15,7
   UNION ALL SELECT @t,2,'Упаковка салат','шт',1,8) z WHERE @new;

-- Соевый соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Соевый соус' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Соевый соус','Доп.','соевый|соус соевый',82 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Соя готовая' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Соя готовая','кг',30,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Имбирь
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Имбирь' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Имбирь','Доп.','имбир',83 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Имбирь' c,'кг' d,15 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Имбирь','кг',20,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Васаби
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Васаби' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Васаби','Доп.','васаби',84 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Васаби' c,'кг' d,5 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Васаби','кг',8,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Сырный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Сырный соус' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Сырный соус','Доп.','сырный соус',85 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сырный соус' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Сырный соус','кг',30,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Чесночный соус
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Чесночный соус' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Чесночный соус','Доп.','чесночный соус',86 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Чесночный соус' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Чесночный соус','кг',30,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Кетчуп
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Кетчуп' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Кетчуп','Доп.','кетчуп',87 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Кетчуп' c,'кг' d,25 e,1 f
   UNION ALL SELECT @t,1,'Соусник','шт',1,2
   UNION ALL SELECT @t,2,'Кетчуп','кг',30,1
   UNION ALL SELECT @t,2,'Соусник','шт',1,2) z WHERE @new;

-- Палочки
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Палочки' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Палочки','Доп.','палочки',88 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Палочки' c,'шт' d,1 e,1 f
   UNION ALL SELECT @t,2,'Палочки','шт',1,1) z WHERE @new;

-- Блэк джек
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Блэк джек' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Блэк джек','Роллы','блэк джек|блек джек|блек чек|black jack',89 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Рис для суши' c,'кг' d,257 e,1 f
   UNION ALL SELECT @t,1,'Нори','шт',0.5,2
   UNION ALL SELECT @t,1,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,1,'Упаковка суши','шт',1,4
   UNION ALL SELECT @t,1,'Огурцы готовые','кг',25,5
   UNION ALL SELECT @t,1,'Кунжут','кг',2,6
   UNION ALL SELECT @t,2,'Рис для суши','кг',180,1
   UNION ALL SELECT @t,2,'Нори','шт',0.5,2
   UNION ALL SELECT @t,2,'Краб готовый','кг',80,3
   UNION ALL SELECT @t,2,'Огурцы готовые','кг',20,4
   UNION ALL SELECT @t,2,'Кунжут','кг',2,5
   UNION ALL SELECT @t,2,'Сыр творожный','кг',55,6
   UNION ALL SELECT @t,2,'Упаковка суши','шт',1,7) z WHERE @new;

-- Шарики
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Шарики' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Шарики','Закуски','шарики|сырные шарики',90 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Шарики' c,'кг' d,80 e,1 f
   UNION ALL SELECT @t,1,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',10,3
   UNION ALL SELECT @t,2,'Шарики','кг',80,1
   UNION ALL SELECT @t,2,'Упаковка фри','шт',1,2
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',10,3) z WHERE @new;

-- Корн дог
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Корн дог' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Корн дог','Закуски','корн дог|корндог',91 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Сосиски (дана)' c,'шт' d,4 e,1 f
   UNION ALL SELECT @t,1,'Кляр','кг',5,2
   UNION ALL SELECT @t,1,'Панко','кг',5,3
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',5,4
   UNION ALL SELECT @t,2,'Сосиски (дана)','шт',1,1
   UNION ALL SELECT @t,2,'Кляр','кг',10,2
   UNION ALL SELECT @t,2,'Панко','кг',5,3
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',5,4) z WHERE @new;

-- Крылышки 8 шт
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Крылышки 8 шт' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Крылышки 8 шт','Закуски','крылышки 8|крылошки 8|крылышки - 8',92 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылышки маринованные' c,'кг' d,340 e,1 f
   UNION ALL SELECT @t,1,'Чикен пакет','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',45,3
   UNION ALL SELECT @t,2,'Крылышки маринованные','кг',340,1
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',45,2
   UNION ALL SELECT @t,2,'Чикен пакет','шт',1,3) z WHERE @new;

-- Крылышки 10 шт
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Крылышки 10 шт' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Крылышки 10 шт','Закуски','крылышки 10|крылошки 10|крылышки - 10',93 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылышки маринованные' c,'кг' d,445 e,1 f
   UNION ALL SELECT @t,1,'Чикен пакет','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',45,3
   UNION ALL SELECT @t,2,'Крылышки маринованные','кг',445,1
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',45,2
   UNION ALL SELECT @t,2,'Чикен пакет','шт',1,3) z WHERE @new;

-- Крылышки 12 шт
SET @t = (SELECT id FROM grammovka_tpl WHERE name_ru='Крылышки 12 шт' ORDER BY id LIMIT 1);
SET @new = (@t IS NULL);
INSERT INTO grammovka_tpl (name_ru,cat,keys_ru,number) SELECT 'Крылышки 12 шт','Закуски','крылышки 12|крылошки 12|крылышки - 12',94 FROM dual WHERE @new;
SET @t = IF(@new, LAST_INSERT_ID(), @t);
INSERT INTO grammovka_tpl_item (tpl_id,variant,name_ru,unit,quantity,number)
  SELECT * FROM (SELECT @t a,1 b,'Крылышки маринованные' c,'кг' d,534 e,1 f
   UNION ALL SELECT @t,1,'Чикен пакет','шт',1,2
   UNION ALL SELECT @t,1,'Фритюрное масло','кг',54,3
   UNION ALL SELECT @t,2,'Крылышки маринованные','кг',537,1
   UNION ALL SELECT @t,2,'Чикен пакет','шт',1,2
   UNION ALL SELECT @t,2,'Фритюрное масло','кг',45,3) z WHERE @new;

COMMIT;
-- Барлығы: 94 шаблон, 1109 жол (Эконом + Стандарт; Премиум кейін).
