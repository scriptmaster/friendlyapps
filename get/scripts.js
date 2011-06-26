
jQuery(function($){
	//
	/*
	$('ul#locations > li').toggle(function(){
		$(this).children('ul').slideDown('fast');
	},function(){
		$(this).children('ul').slideUp('fast');
	});
	*/
	
	if (!my.location) {
		$('#locations').hide();
		alert('Set your Current and Home Locations first!');
		setTimeout(function(){
			top.location.href = 'http://www.facebook.com/editprofile.php';
		}, 500);
		
	}
	
	function log(){
		if (window.console && console.log) console.log(arguments);
	}
	
	function array_keys(a){
		//
	};
	
	$.getJSON('/friendly/locations', function(data){
		var addresses = [];
		for (var x in data) {
			addresses.push( x );
		}
		log(addresses);
		var url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='+
					escape(my.location)+
					'&destinations='+
					escape(addresses.join('|'))+
					'&sensor=false';
		log(url);
		$.ajax({
			url: url,
			success: function(matrix){
				// log(matrix);
				if (matrix && matrix.status=='OK') {
					//
				}
			},
			error: function(){
				log(arguments);
			}
		});
	});
	
	
	
	/*
			$addresses = array_keys( $locations ); //destination_addresses
			$destinations = implode( '|', $addresses );

			$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='.urlencode($my_location).'&destinations='.urlencode($destinations).'&sensor=false';
			$matrix = json_decode( file_get_contents($url) );

			// print_r( $url );

			$ordered_locations = array(); // in the route order
			$missed_locations = array();
	
			// print_r( $matrix );
			if ( !empty( $matrix ) && $matrix->status=='OK' ) {
	
				$merged_locations = array();
				$distances = array();
	
				$mda = $matrix->destination_addresses;
				
				$my_location = $matrix->origin_addresses[0];
				if (!empty($my_location)) F3::set('my_location', $my_location);
				
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

	
	*/
	
})
