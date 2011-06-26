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
    $user_profile = $facebook->api('/me');
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

/*
if (!empty($_GET['code']) && !empty($_GET['state'])) {
	F3::call(':auth|code');
} else if ( !empty($_GET['error']) ) {
	F3::call(':auth|error');
} else {
	//
}
*/

if ( $accessedFromAppsURL ) {
	if (!$user) {
		echo $loginUrl;
		header("Location: $loginUrl");
	} else {
		// F3::call('bestfriends');
		// $uid = '100000100367711';
		// print_r( $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name FROM user WHERE uid = me()') ) );

		// '<div id="loginLogout"><a href="'.$logoutUrl.'">Logout</a></div>';
		F3::set('logoutUrl', $logoutUrl);

		function get_location($l) {
			if (isset($l['name']))
					return $l['name'];
				if (isset($l['city']))
					return $l['city'].( isset($l['state']) ? ', '.$l['state'] : '' ).( isset($l['country']) ? ', '.$l['country'] : '' );
			return '';
		}

		// print_r( $facebook->api('/me') );
		// echo '<hr />';
		// print_r( $facebook->api('/me/statuses') );

		// echo '<hr />';
		$friends = $facebook->api('/me/friends');
		// print_r( count($friends['data']) );
		// print_r( $friends['data'] );
		// for ($friends as )

		// echo '<hr />';
		$my = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, pic_square, current_location, hometown_location FROM user WHERE uid=me()') );
		$my = $my[0];
		
		
		$my_location = get_location( $my['current_location'] );
		if (empty($my_location)) $my_location = get_location( $my['hometown_location'] );

		if (!empty($my_location)) {
			F3::set('my_location', $my_location);
		}
		F3::set('my', $my);
		
		// $result = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, current_location, hometown_location FROM user WHERE uid=me() OR uid IN (SELECT uid2 FROM friend WHERE uid1 = me())') );
		$result = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, current_location, hometown_location FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())') );
		//echo '<hr />';
		
		
		$locations = array();
		
		for( $i = 0, $ii = count($result); $i < $ii; ++$i ) {
			$name = $result[$i]['name'];
			$location = '';
			// echo $name;
			// echo ' - ';
			if (isset($result[$i]['current_location'])) {
				$location = get_location($result[$i]['current_location']);
			} else if (isset($result[$i]['hometown_location'])) {
				/*
				if (isset($result[$i]['hometown_location']['name']))
					$location = $result[$i]['hometown_location']['name'];	
				if (isset($result[$i]['hometown_location']['city']))
					$location = $result[$i]['hometown_location']['city'].( isset($result[$i]['hometown_location']['state']) ? ', '.$result[$i]['hometown_location']['state'] : '' ).( isset($result[$i]['hometown_location']['country']) ? ', '.$result[$i]['hometown_location']['country'] : '' );
				*/
			}

			if (!empty($location)) {
				if (!isset($locations[$location])) $locations[$location] = array();
				$locations[$location][] = $name;
			}

		}

		// echo 'LOCATIONS:<br />';
		// print_r($locations);
		ksort($locations);

		// print_r( array_keys( $locations ) );
		F3::set('locations', $locations);

		function test_locations_display() {
			echo '<ul id="locations">';
			foreach($locations as $location => $names) {
				echo "<li>";
				echo $location;
				echo ' &nbsp; ('.count($names).')';
				echo "<ul>";
				foreach($names as $name) {
					echo "<li>";
					echo $name;
					echo "</li>";
				}
				echo "</ul>";
				echo "</li>";
			}
			echo '</ul>';
		}
		
		
		// print_r( $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT post_id, message FROM stream WHERE source_id=me()') ) );
		// echo '<hr />';
		
		
		// $my_likes = $facebook->api( array( 'method'=>'fql.query', 'query'=>'SELECT user_id FROM like WHERE post_id IN (SELECT post_id FROM stream WHERE source_id=me() ) and user_id!=me()' ) );
		// print_r( $my_likes );
		// echo '<hr />';
		
		// $my_comments = $facebook->api( array( 'method'=>'fql.query', 'query'=>'SELECT fromid, text FROM comment WHERE post_id IN (SELECT post_id FROM stream WHERE source_id=me() ) and fromid!=me()' ) );
		// print_r( $my_comments );
		// echo '<hr />';
		
		// print_r( $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT uid, name, pic_square FROM user WHERE uid = me() OR uid IN (SELECT uid2 FROM friend WHERE uid1 = me())') ) );


		// print_r( $facebook->api_client->fql_query('SELECT user_id FROM like WHERE object_id="$uid"') );
	}
	// echo 'Hello Facebook User';
	// $signed_request = $_POST['signed_request'];
} else {
	//
}


F3::set('title', 'Friendly');
echo F3::serve('html/main.htm');



?>