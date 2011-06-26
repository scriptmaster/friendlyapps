<?php

require '../php-sdk/src/facebook.php';

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
	'appId'  => '114087885347718', // YOUR_APP_ID
	'secret' => '5bea038dc9e108eac2d94e21c31b48d3', // YOUR_APP_SECRET
));

// Get User ID
$user = $facebook->getUser();
if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    // $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl( array( scope=>'read_stream,user_location,friends_location' ) );
}


$accessedFromAppsURL = isset($_SERVER['HTTP_REFERER']) && preg_match( '/^https?\:\/\/apps.facebook.com\/thefriendlyapp/', $_SERVER['HTTP_REFERER'] );
$accessedFromAppsURL = TRUE;

if ( $accessedFromAppsURL ) {
	if (!$user) {
		header("Location: $loginUrl");
	} else {

		function get_location($l) {
			if (isset($l['name']))
				return $l['name'];
			if (isset($l['city']))
				return $l['city'].( isset($l['state']) ? ', '.$l['state'] : '' ).( isset($l['country']) ? ', '.$l['country'] : '' );
			return '';
		}
		
		
		$my = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, pic_square, current_location, hometown_location FROM user WHERE uid=me()') );
		$my = $my[0];
		
		$my_location = get_location( $my['current_location'] );
		if (empty($my_location)) $my_location = get_location( $my['hometown_location'] );

		if (!empty($my_location)) {
			F3::set('my_location', $my_location);
		}
		F3::set('my', $my);
		
		$result = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, profile_url, current_location, hometown_location FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())') );
		
		
		$locations = array();
		
		for( $i = 0, $ii = count($result); $i < $ii; ++$i ) {
			$location = '';
			if (isset($result[$i]['current_location'])) {
				$location = get_location($result[$i]['current_location']);
			} else if (isset($result[$i]['hometown_location'])) {
				$location = get_location($result[$i]['hometown_location']);
			}

			if (!empty($location)) {
				if (!isset($locations[$location])) $locations[$location] = array();
				$locations[$location][] = array('name'=>$result[$i]['name'], 'profile_url'=>$result[$i]['profile_url']);
			}

		}

		ksort($locations);

		// header('Content-Type: text/x-json');
		echo json_encode($locations);

	}

} else {
	//
}

?>