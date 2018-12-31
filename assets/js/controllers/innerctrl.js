app.controller('innerCtrl', function($rootScope, $scope, $http, $state, $window, utils, AuthenticationService, $timeout) {
	$scope.search = {};
	$scope.search.left_light = false;
	$scope.search.right_light = true;
	$scope.search.middle_light = false;
	$scope.showingLinks = false;
	$rootScope.inner = true;
	$rootScope.showingError = false;

	$scope.index = function(id){
		$scope[index + id] = 0;
		return $scope[index + id];
	}

	$scope.setActive = function(val){
		$timeout(function(){
			$rootScope.index = val;
			$scope.$apply();
		});
	}

	$scope.stripped = function(image){
		return image.split('../')[1];
	}

	$scope.countries = {
		"AF": "Afghanistan", "AX": "Åland Islands", "AL": "Albania", "DZ": "Algeria", "AS": "American Samoa", "AD": "Andorra", "AO": "Angola", "AI": "Anguilla", "AQ": "Antarctica", "AG": "Antigua and Barbuda", "AR": "Argentina", "AM": "Armenia", "AW": "Aruba", "AU": "Australia", "AT": "Austria", "AZ": "Azerbaijan", "BS": "Bahamas", "BH": "Bahrain", "BD": "Bangladesh", "BB": "Barbados", "BY": "Belarus", "BE": "Belgium", "BZ": "Belize", "BJ": "Benin", "BM": "Bermuda", "BT": "Bhutan", "BO": "Bolivia, Plurinational State of", "BQ": "Bonaire, Sint Eustatius and Saba", "BA": "Bosnia and Herzegovina", "BW": "Botswana", "BV": "Bouvet Island", "BR": "Brazil", "IO": "British Indian Ocean Territory", "BN": "Brunei Darussalam", "BG": "Bulgaria", "BF": "Burkina Faso", "BI": "Burundi", "KH": "Cambodia", "CM": "Cameroon", "CA": "Canada", "CV": "Cape Verde", "KY": "Cayman Islands", "CF": "Central African Republic", "TD": "Chad", "CL": "Chile", "CN": "China", "CX": "Christmas Island", "CC": "Cocos (Keeling) Islands", "CO": "Colombia", "KM": "Comoros", "CG": "Congo", "CD": "Congo, the Democratic Republic of the", "CK": "Cook Islands", "CR": "Costa Rica", "CI": "Côte d'Ivoire", "HR": "Croatia", "CU": "Cuba", "CW": "Curaçao", "CY": "Cyprus", "CZ": "Czech Republic", "DK": "Denmark", "DJ": "Djibouti", "DM": "Dominica", "DO": "Dominican Republic", "EC": "Ecuador", "EG": "Egypt", "SV": "El Salvador", "GQ": "Equatorial Guinea", "ER": "Eritrea", "EE": "Estonia", "ET": "Ethiopia", "FK": "Falkland Islands (Malvinas)", "FO": "Faroe Islands", "FJ": "Fiji", "FI": "Finland", "FR": "France", "GF": "French Guiana", "PF": "French Polynesia", "TF": "French Southern Territories", "GA": "Gabon", "GM": "Gambia", "GE": "Georgia", "DE": "Germany", "GH": "Ghana", "GI": "Gibraltar", "GR": "Greece", "GL": "Greenland", "GD": "Grenada", "GP": "Guadeloupe", "GU": "Guam", "GT": "Guatemala", "GG": "Guernsey", "GN": "Guinea", "GW": "Guinea-Bissau", "GY": "Guyana", "HT": "Haiti", "HM": "Heard Island and McDonald Islands", "VA": "Holy See (Vatican City State)", "HN": "Honduras", "HK": "Hong Kong", "HU": "Hungary", "IS": "Iceland", "IN": "India", "ID": "Indonesia", "IR": "Iran, Islamic Republic of", "IQ": "Iraq", "IE": "Ireland", "IM": "Isle of Man", "IL": "Israel", "IT": "Italy", "JM": "Jamaica", "JP": "Japan", "JE": "Jersey", "JO": "Jordan", "KZ": "Kazakhstan", "KE": "Kenya", "KI": "Kiribati", "KP": "Korea, Democratic People's Republic of", "KR": "Korea, Republic of", "KW": "Kuwait", "KG": "Kyrgyzstan", "LA": "Lao People's Democratic Republic", "LV": "Latvia", "LB": "Lebanon", "LS": "Lesotho", "LR": "Liberia", "LY": "Libya", "LI": "Liechtenstein", "LT": "Lithuania", "LU": "Luxembourg", "MO": "Macao", "MK": "Macedonia, the former Yugoslav Republic of", "MG": "Madagascar", "MW": "Malawi", "MY": "Malaysia", "MV": "Maldives", "ML": "Mali", "MT": "Malta", "MH": "Marshall Islands", "MQ": "Martinique", "MR": "Mauritania", "MU": "Mauritius", "YT": "Mayotte", "MX": "Mexico", "FM": "Micronesia, Federated States of", "MD": "Moldova, Republic of", "MC": "Monaco", "MN": "Mongolia", "ME": "Montenegro", "MS": "Montserrat", "MA": "Morocco", "MZ": "Mozambique", "MM": "Myanmar", "NA": "Namibia", "NR": "Nauru", "NP": "Nepal", "NL": "Netherlands", "NC": "New Caledonia", "NZ": "New Zealand", "NI": "Nicaragua", "NE": "Niger", "NG": "Nigeria", "NU": "Niue", "NF": "Norfolk Island", "MP": "Northern Mariana Islands", "NO": "Norway", "OM": "Oman", "PK": "Pakistan", "PW": "Palau", "PS": "Palestinian Territory, Occupied", "PA": "Panama", "PG": "Papua New Guinea", "PY": "Paraguay", "PE": "Peru", "PH": "Philippines", "PN": "Pitcairn", "PL": "Poland", "PT": "Portugal", "PR": "Puerto Rico", "QA": "Qatar", "RE": "Réunion", "RO": "Romania", "RU": "Russian Federation", "RW": "Rwanda", "BL": "Saint Barthélemy", "SH": "Saint Helena, Ascension and Tristan da Cunha", "KN": "Saint Kitts and Nevis", "LC": "Saint Lucia", "MF": "Saint Martin (French part)", "PM": "Saint Pierre and Miquelon", "VC": "Saint Vincent and the Grenadines", "WS": "Samoa", "SM": "San Marino", "ST": "Sao Tome and Principe", "SA": "Saudi Arabia", "SN": "Senegal", "RS": "Serbia", "SC": "Seychelles", "SL": "Sierra Leone", "SG": "Singapore", "SX": "Sint Maarten (Dutch part)", "SK": "Slovakia", "SI": "Slovenia", "SB": "Solomon Islands", "SO": "Somalia", "ZA": "South Africa", "GS": "South Georgia and the South Sandwich Islands", "SS": "South Sudan", "ES": "Spain", "LK": "Sri Lanka", "SD": "Sudan", "SR": "Suriname", "SJ": "Svalbard and Jan Mayen", "SZ": "Swaziland", "SE": "Sweden", "CH": "Switzerland", "SY": "Syrian Arab Republic", "TW": "Taiwan, Province of China", "TJ": "Tajikistan", "TZ": "Tanzania, United Republic of", "TH": "Thailand", "TL": "Timor-Leste", "TG": "Togo", "TK": "Tokelau", "TO": "Tonga", "TT": "Trinidad and Tobago", "TN": "Tunisia", "TR": "Turkey", "TM": "Turkmenistan", "TC": "Turks and Caicos Islands", "TV": "Tuvalu", "UG": "Uganda", "UA": "Ukraine", "AE": "United Arab Emirates", "GB": "United Kingdom", "US": "United States", "UM": "United States Minor Outlying Islands", "UY": "Uruguay", "UZ": "Uzbekistan", "VU": "Vanuatu", "VE": "Venezuela, Bolivarian Republic of", "VN": "Viet Nam", "VG": "Virgin Islands, British", "VI": "Virgin Islands, U.S.", "WF": "Wallis and Futuna", "EH": "Western Sahara", "YE": "Yemen", "ZM": "Zambia", "ZW": "Zimbabwe"};

	$scope.focus = false;

	$scope.$on('$destroy', function() {
        $rootScope.inner = false;
    });

	$scope.getVessels = function(){
		$http({
	        method: 'GET',
	        url: 'scripts/getvessels.php'
		}).then(function(data){
			$scope.vessels = data.data;

			angular.forEach($scope.vessels, function(vessel){
				vessel.vessel_photos = JSON.parse(vessel.vessel_photos);
			});

		}).catch(angular.noop);
	}

	$scope.getUserVessels = function(){
		$http({
	        method: 'GET',
	        url: 'scripts/getuservessels.php?id='+$rootScope.mvx_globals.currentUser.userdata.data.id
		}).then(function(data){
			$scope.uservessels = data.data;
			
			angular.forEach($scope.uservessels, function(vessel){
				vessel.vessel_photos = JSON.parse(vessel.vessel_photos);
				vessel.userown = true;
			});
		}).catch(angular.noop);
	}

	$scope.getUser = function(userid, callback){
		var url = 'scripts/fetchuser.php?id='+userid+'&src=id';

		$http({
	        method: 'GET',
	        url: url
		}).then(function(data){
            callback(data.data.data);
		}).catch(angular.noop);
    }

    $scope.getVessel = function(id, callback){
		$http({
	        method: 'GET',
	        url: 'scripts/getvessel.php?id='+id
		}).then(function(data){
			callback(data);
		}).catch(angular.noop);
	}

	$scope.getOffers = function(){
		$scope.offers = null;
		$http({
	        method: 'GET',
	        url: 'scripts/getoffers.php?id='+$rootScope.mvx_globals.currentUser.userdata.data.id
		}).then(function(data){
			var offers = data.data;
			var grouped_offers = [];
			var cleaned_grouped_offers = [];

			angular.forEach(offers, function(offer){
				offer.vessels_vessel_photos = JSON.parse(offer.vessels_vessel_photos);
			});

			angular.forEach(offers, function(offer, key){
				if(grouped_offers[offer.offers_charter_id] == null){
					grouped_offers[offer.offers_charter_id] = [];
				}
				grouped_offers[offer.offers_charter_id].push(offer);
			});

			angular.forEach(grouped_offers, function(offer, key){
				if(offer !== null){
					cleaned_grouped_offers[key] = offer;
				}
			})

			$scope.offers = cleaned_grouped_offers;

		}).catch(angular.noop);
	}

	$scope.getCharterRequests = function(){
		$scope.charters = null;
		$http({
	        method: 'GET',
	        url: 'scripts/getusercharterrequests.php?id='+$rootScope.mvx_globals.currentUser.userdata.data.id
		}).then(function(data){
			$scope.charters = data.data;

		}).catch(angular.noop);
	}

	$scope.foc = function(val){
		$scope.focus = val;
	}

	$scope.date = function(date){
		return new Date(date);
	}

	$scope.getFileName = function(filepath){
		var sections = filepath.split('/');
		return sections[sections.length - 1];
	}

	$scope.blur = function(){
		$scope.focus = false;
	}

	$scope.getColor = function(fullname){
		return utils.getColor(fullname);
	}

	$scope.getInitials = function(fullname){
		return utils.getInitials(fullname);
	}

	$scope.getName = function(fullname){
		return utils.getName(fullname);
	}

	$scope.home = function(){
		$state.go('home');
	}

	$scope.login = function(){
		$state.go('signin');
	}

	$scope.signup = function(){
		$state.go('signup');
	}

	$scope.dashboard = function(){
		$state.go('dashboard');
	}

	$scope.addvessel = function(){
		$state.go('addvessel');
	}

	$scope.charter = function(){
		$state.go('charter');
	}

	$scope.chat = function(){
		$state.go('chat');
	}

	$scope.viewchat = function(code, vessel, peer){
		$state.go('chat', {"vessel": vessel, "peer": peer, "chat_id": code});
	}

    $rootScope.charterInformationTab = function(charter){
    	var url = $state.href('enquiry', {"enquiry": charter, "enquiry_id": charter.id});
		$window.open(url,'_blank');
    }

    $rootScope.vesselInformationTab = function(vessel, src){
    	if(src == 'vessel') {
    		vesselObj = vessel;
    		vesselId = vessel.id;
    	}else{
    		vesselObj = {};
    		vesselId = vessel.vessels_id;
    	}

    	var url = $state.href('vessel', {"vessel": vesselObj, "vessel_id": vesselId});
		$window.open(url,'_blank');
    }

	$scope.showLinks = function(){
		$scope.showingLinks = !$scope.showingLinks;
	}

    $rootScope.logout = function(){
        AuthenticationService.ClearCredentials();
        $state.go('logout');
	}

	$rootScope.closeError = function(){
		$rootScope.showingError = false;
	}

	$rootScope.showError = function(){
		$rootScope.showingError = true;
	}
});