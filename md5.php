<?php include "config/core.php";

	// sign in code
	if(isset($_GET['upd'])) {
      $user = db::query("SELECT * FROM user");
      while ($user_d = mysqli_fetch_assoc($user)) {
         $id = $user_d['id'];
         $pass = $user_d['password'];
         $pass2 = md5($pass);
         $upd = db::query("UPDATE `user` SET `password2` = '$pass2' WHERE id = '$id'");
      }
      exit();
	}