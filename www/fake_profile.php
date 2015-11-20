<?php
	header('Content-Type: application/json');

	$arr = array(
		"username"=>"poopie",
		"first_name"=>"Doof",
		"middle_initial"=>"X",
		"last_name"=>"Doofington",
		"suffix"=>"DDS.",
		"gender"=>"Male",
		"bio"=>"bio",
		"email_addr"=>"doof@doofington.poop",
		"office_phone"=>"DOO-FOF-FICE",
		"mobile_phone"=>"DOO-FMO-BILE"
	);
	echo json_encode($arr);
?>
