<?
	// Кезең: күн (әдепкі), ай немесе жыл. core.php-дан кейін қосылады ($time_sort, $start_cdate, $end_cdate).
	// Жұмыс күні 06:00-де басталады: ай 1-күні 06:00 → келесі айдың 1-күні 06:00.
	$period = in_array(@$_GET['period'], ['month', 'year']) ? $_GET['period'] : 'day';
	$time_pick = $time_sort; // кезеңді ауыстырғанда таңдалған күн сақталады
	if ($period == 'month') $time_sort = date('Y-m-01', strtotime($time_sort));
	if ($period == 'year') $time_sort = date('Y-01-01', strtotime($time_sort));
	if ($period != 'day') {
		$start_cdate = "$time_sort 06:00:00";
		$end_cdate = date('Y-m-d 06:00:00', strtotime("$time_sort +1 $period"));
	}
