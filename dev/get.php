<?php include "config/core.php";
	// Кіру: dev_staff тізіміндегілер (5 рөл). Пароль → керек болса WhatsApp коды (config/auth.php)
	auth_routes([1, 2, 3, 4, 5], 'dev');
	exit('none');
