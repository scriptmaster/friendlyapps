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
		$result = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, profile_url, current_location, hometown_location FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())') );
		//echo '<hr />';
		
		
		$locations = array();
		
		for( $i = 0, $ii = count($result); $i < $ii; ++$i ) {

			$location = '';

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
				$locations[$location][] = array('name'=>$result[$i]['name'], 'profile_url'=>$result[$i]['profile_url']);
			}

		}

		// echo 'LOCATIONS:<br />';
		// print_r($locations);
		ksort($locations);

		// print_r( array_keys( $locations ) );
		
		$addresses = array_keys( $locations ); //destination_addresses
		
		$destinations = implode( '|', $addresses );

		$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='.urlencode($my_location).'&destinations='.urlencode($destinations).'&sensor=false';
		$matrix = json_decode( file_get_contents($url) );

		$ordered_locations = array(); // in the route order
		$missed_locations = array();

		// print_r( $matrix );
		if ( !empty( $matrix ) && $matrix->status=='OK' ) {

			$merged_locations = array();
			$distances = array();

			$mda = $matrix->destination_addresses;
			$my_location = $matrix->origin_addresses[0];
			$dists = $matrix->rows[0]->elements;
			
			for($i=0, $ii=count($locations); $i<$ii; ++$i) {
				// echo '<br/>'.$mda[$i];
				// echo ' - '.$addresses[$i];
				// echo ' ( ' . ($dists[$i]->status=='OK' ? $dists[$i]->distance->value : $dists[$i]->status) . ' ) ';
				if ( $dists[$i]->status == 'OK' ) {
					// $key = htmlentities($matrix->destination_addresses[$i]);
					$key = $mda[$i];
					if (isset($merged_locations[ $key ])) {
						$merged_locations[ $key ] = array_merge( $merged_locations[ $key ], $locations[$addresses[$i]] );
					} else {
						$merged_locations[ $key ] = $locations[$addresses[$i]];
						$distances[ $dists[$i]->distance->value ] = $key;
					}
				} else {
					$key = $mda[$i];
					if (isset($missed_locations[ $key ])) {
						$missed_locations[ $key ] = array_merge( $missed_locations[ $key ], $locations[$addresses[$i]] );
					} else {
						$missed_locations[ $key ] = $locations[$addresses[$i]];
					}
				}
			}
			// echo '<hr />';
			// print_r( array_keys( $merged_locations ) );
			// echo '<hr />';

			ksort( $distances );
			// print_r( $distances );
			foreach($distances as $d => $location) {
				$ordered_locations[ $location ] = $merged_locations[ $location ];
			}

			// $ordered_locations = array_merge( $ordered_locations );
			// $destination = array_pop( $ordered_locations );

			// $orig_locations = array_keys($ordered_locations);

			$waypoints = array_keys($ordered_locations);
			$myLocationExcluded = FALSE;
			if ($my_location == $waypoints[0]) {
				$myLocationExcluded = TRUE;
				// There are friends to visit in the current city
				// however, we DONT include the current location in the waypoints.
				array_shift( $waypoints );
			}
			$destination = array_pop( $waypoints );

			$url2 = 'http://maps.googleapis.com/maps/api/directions/json?origin='.urlencode($my_location).
				'&destination='.urlencode($destination).
				'&waypoints=optimize:true|'.urlencode( join('|', $waypoints) ).'&sensor=false';
				
			// print_r( $waypoints );

			$result = json_decode( file_get_contents($url2) );

			if ($result->status == 'OK') {
				$optimized_locations = array();
				$waypoint_order = $result->routes[0]->waypoint_order;

				if ($myLocationExcluded) { // my_location was included - there are some friends to visit in current location first //
					$optimized_locations[ $my_location ] = $ordered_locations[ $my_location ];
				}

				for ($i=0,$ii=count($waypoint_order);$i<$ii;$i++) {
					$location = $waypoints[ $waypoint_order[$i] ];
					$optimized_locations[ $location ] = $ordered_locations[ $location ];
				}

				// Add the destination //
				$optimized_locations[ $destination ] = $ordered_locations[ $destination ];

				// array_multisort( $ordered_locations, $result->routes[0]->waypoint_order );
				// print_r( $result->routes[0]->waypoint_order );
				if (count($optimized_locations)>0) {
					$ordered_locations = $optimized_locations;
				}

			}

			// echo '<a href="'.$url2.'" target="_blank">waypoints</a>';

		} else {
			// echo 'Something went WRONG with Google Maps.';
			// print_r( $matrix );
			$ordered_locations = $locations;
		}
		// print_r( count($matrix['destination_addresses']) );
		// print_r( count($addresses) );
		
		// echo '<a href="'.$url.'" target="_blank">matrix</a>';

		F3::set('locations', $ordered_locations);
		F3::set('missed_locations', $missed_locations);

		// print_r( $locations );

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

	}

} else {
	//
}


F3::set('title', 'Friendly');
echo F3::serve('html/main.htm');



?>