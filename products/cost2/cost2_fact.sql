-- Себес: Факт мәндері (қолмен толтырылады). План — cost_setting, Факт — осы кесте; бос (NULL) өріс — План мәні.
-- Қайта іске қосуға болады. products/cost2/fact.php кесте жоқ болса өзі де жасайды.
CREATE TABLE IF NOT EXISTS `cost_setting_fact` (
	`company_id` int(11) NOT NULL,
	`orders_day` decimal(10,1) DEFAULT NULL,
	`salary` int(11) DEFAULT NULL,
	`rent` int(11) DEFAULT NULL,
	`utility` int(11) DEFAULT NULL,
	`delivery` int(11) DEFAULT NULL,
	`ads` int(11) DEFAULT NULL,
	`markup` decimal(5,2) DEFAULT NULL,
	`upd_dt` datetime DEFAULT NULL,
	PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Себес: Факт мәндері (қолмен), бос өріс — План';
