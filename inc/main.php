<?php

require '../php-sdk/src/facebook.php';

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
	'appId'  => 'YOUR_APP_ID'
	'secret' => 'YOUR_APP_SECRET'
));

// Get User ID
$user = $facebook->getUser();
/*
if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    // $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}
*/

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
		echo $loginUrl;
		header("Location: $loginUrl");
	} else {
		function get_location($l) {
			if (isset($l['name']))
				return $l['name'];
			if (isset($l['city']))
				return $l['city'].( isset($l['state']) ? ', '.$l['state'] : '' ).( isset($l['country']) ? ', '.$l['country'] : '' );
			return '';
		}

		$my = $facebook->api( array('method'=>'fql.query', 'query'=>'SELECT name, profile_url, pic_square, current_location, hometown_location FROM user WHERE uid=me()') );
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

		// Sort Locations
		ksort($locations);
		
		$renderMode = 'SERVER';
		F3::set('renderMode', $renderMode);

		if ($renderMode == 'CLIENT') {
			F3::set('locations', $locations);
		} else {

			$addresses = array_keys( $locations ); //destination_addresses
			$destinations = implode( '|', $addresses );

			$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='.urlencode($my_location).'&destinations='.urlencode($destinations).'&sensor=false';
			$matrix = json_decode( file_get_contents($url) );

			$ordered_locations = array(); // in the route order
			$missed_locations = array();
	
			if ( !empty( $matrix ) && $matrix->status=='OK' ) {
	
				$merged_locations = array();
				$distances = array();
	
				$mda = $matrix->destination_addresses;
				
				$my_location = $matrix->origin_addresses[0];
				if (!empty($my_location)) F3::set('my_location', $my_location);
				
				$dists = $matrix->rows[0]->elements;
				
				for($i=0, $ii=count($locations); $i<$ii; ++$i) {
					if ( $dists[$i]->status == 'OK' ) {
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

				ksort( $distances );

				foreach($distances as $d => $location) {
					$ordered_locations[ $location ] = $merged_locations[ $location ];
				}
	
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
	
					// Add the destination, finally //
					$optimized_locations[ $destination ] = $ordered_locations[ $destination ];
	
					// array_multisort( $ordered_locations, $result->routes[0]->waypoint_order );
					// print_r( $result->routes[0]->waypoint_order );
					if (count($optimized_locations)>0) {
						$ordered_locations = $optimized_locations;
						F3::set('optimizedOnServer', 1);
					}
				}
	
			} else {
				// echo 'Something went WRONG with Google Maps.';
				// print_r( $matrix );
				F3::set('optimizedOnServer', 0);
				$ordered_locations = $locations;
			}

			F3::set('locations', $ordered_locations);
			F3::set('missed_locations', $missed_locations);
		}


		
		function map_locations($locations) {
			
			$markers = array();
			$i = 65; // A;
			foreach($locations as $location => $names) {
				if ($i>90) $i=48;
				$markers[] = 'markers=label:'.chr($i).'|'.$location;
				// $paths[] = $location;
				$i++;
			}
			
			$url = 'http://maps.google.com/maps/api/staticmap?size=500x500&'.join('&',$markers).'&sensor=false';
			//$url = 'http://maps.google.com/maps/api/staticmap?size=500x500&'.join('&',$markers).'&sensor=false&path='.join('|',array_keys($locations));
			//$url = 'http://maps.google.com/maps/api/staticmap?size=500x500&sensor=false&path='.join('|',array_keys($locations));
			
			return $url;
			
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

		$url = map_locations( F3::get('locations') );
		F3::set('map_url', $url);


	}

} else {
	//
}


F3::set('title', 'The shortest way you should use to visit all your Facebook friends in various cities');
echo F3::serve('html/main.htm');

?>