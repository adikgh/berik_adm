<? include "../config/core.php";


	// 
	if (!$user_id) { header('location: /'); exit; } // 2026-11-21 аудит: кірусіз ашылып тұрған


	// site setting
	$menu_name = 'acc';
	$site_set = [
		'header' => 'user',
		'footer' => 'false',
      'ublock' => 'true',
		'utop_nm' => 'Жеке деректер',
		'utop_bk' => ' ',
	];
	$css = ['user', 'uacc'];
	$js = ['user'];
	
?>
<? include "../block/header.php"; ?>
<? include "acc_d.php"; ?>
<? include "../block/footer.php"; ?>