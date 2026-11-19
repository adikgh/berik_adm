-- Себес-2: ЖИ толтырған позициялардың белгісі («ЖИ» белгісі, тексеру үшін). products/cost2/ai.php кесте жоқ болса өзі де жасайды.
CREATE TABLE IF NOT EXISTS `cost2_ai` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`company_id` int(11) NOT NULL,
	`kind` char(1) NOT NULL COMMENT 'p = тағам (product), r = шикізат (product_raw), c = құрам (product_comp)',
	`ref_id` int(11) NOT NULL,
	`set_id` int(11) DEFAULT NULL,
	`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`id`),
	UNIQUE KEY `ref` (`kind`, `ref_id`),
	KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Себес-2: ЖИ толтырған позициялар (тексеру үшін белгі)';
