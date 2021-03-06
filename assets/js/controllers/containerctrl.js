app.controller('containerCtrl', function($rootScope, $scope, $http, $state, $window, $timeout, utils, AuthenticationService) {
	$rootScope.showingError = false;
	$scope.showLinks = false;
	$scope.user = {};
	$rootScope.offeringVessel = false;
	$rootScope.showingMore = false;


  	$scope.charter = function(){
		$state.go('charter');
	}
  
  	$scope.addvessel = function(){
		$state.go('addvessel');
	}

    $rootScope.removeGoHome = function(){
    	$state.go('home');
    }

    $rootScope.vesselInformation = function(vessel){
    	$state.go('vessel', {"vessel": vessel, "vessel_id": vessel.id});
    }

    $rootScope.charterInformation = function(charter){
    	$state.go('enquiry', {"enquiry": charter, "enquiry_id": charter.id});
    }

    $scope.formatDate = function(date){
    	return utils.formatDate(date);
    }

    $rootScope.gotoLogin = function(){
    	$state.go('login');
    }

	$scope.stripped = function(image){
		return image.split('../')[1];
	}

	$scope.openUrl = function(url){
		$window.open(url, '_blank');
	}

	$scope.getFileName = function(filepath){
		var sections = filepath.split('/');
		return sections[sections.length - 1];
	}

	$scope.computeAge = function(year_built){
		return (new Date()).getFullYear() - year_built;
	}

    $rootScope.logout = function(){
        AuthenticationService.ClearCredentials();
        $state.go('home');
	}

    $rootScope.dashboard = function(){
    	console.log('Going to dashboard');
        $state.go('dashboard');
	}

    $rootScope.signup = function(){
    	$state.go('signup');
    }

	$rootScope.showError = function(){
		$rootScope.showingError = true;
	}

	$scope.showLink = function(){
		$scope.showLinks = !$scope.showLinks;
	}

	$scope.toggleMore = function(){
		if($rootScope.showingMore == true){
			$rootScope.showingMore = false;
		}else{
			$rootScope.showingMore = true;
		}
	}

	$scope.showMore = function(){
		$rootScope.showingMore = true;
	}

	$scope.vesselOptions = function(vessel){
		if($rootScope.offeringVessel == true){
			$scope.offeredVessel = vessel;
		}else{
			$rootScope.vesselInformation(vessel);
		}
	}

	$scope.cancelOffer = function(vessel){
		$scope.offeredVessel = null;
	}

	$scope.sendOffer = function(){
		$scope.sendingOffer = true;

		var credentials = {
			charter_id: $scope.charterToTakeOffer.id,
			vessel_id: $scope.offeredVessel.id
		};

		$http({
            method: "post",
            url: 'scripts/offervessel.php',
            data: credentials
        }).then(function successCallback(data) {

        	console.log(data);
        	
        	$timeout(function(){
        		$scope.sendingOffer = false;
        		$scope.offerMsg = data.data.msg;
        	}, 500);            
            
        }, function errorCallback(data) {
            
            console.log(data);
            $scope.processing = false;

        });
	}

	$scope.closeMore = function(){
		$rootScope.showingMore = false;
		$rootScope.offeringVessel = false;
	}

	$rootScope.closeError = function(){
		$rootScope.showingError = false;
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

	// Get all vessels on platform
	$scope.getVessels = function(){
		$http({
	        method: 'GET',
	        url: 'scripts/getvessels.php'
		}).then(function(data){
			$rootScope.vessels = data.data.reverse();

			angular.forEach($rootScope.vessels, function(vessel){
				vessel.vessel_photos = JSON.parse(vessel.vessel_photos);
			});

		}).catch(angular.noop);
	}

	$scope.offerVessel = function(charter){
		console.log(charter.id);
		$rootScope.offeringVessel = true;
		console.log($rootScope.offeringVessel);
		$rootScope.charterToTakeOffer = charter;
	}

	$scope.login = function(){
    	var validation = utils.validate();
    	$scope.loginError = false;
        $rootScope.showingError = false;

    	if(validation == 0){
			$scope.closeError();
			$scope.processing = true;

			AuthenticationService.Login($scope.user, function(data){
				console.log(data);
	    		$scope.loginStatus = data.data.msg;

	    		if(data.data.msg == "Logged In"){
		    		AuthenticationService.SetCredentials($scope.user.email, $scope.user.password, data.data);
	    			$scope.user = {};

		    		$timeout(function(){
		    			$scope.loginStatus = "";
		    		}, 1000);

		    		if($rootScope.mvx_globals.currentUser.userdata.data.category == 'Ship Owner'){
						$scope.getOffers();
						$scope.getUserVessels();
					}else{
						getUserCharterRequests();
					}

		    	}else if(data.data.msg == "Account Not Verified"){
		    		$rootScope.showError();
		    		$scope.loginError = data.data.msg;
                    $timeout(function(){
                    	$rootScope.closeError();
		    			$scope.loginError = data.data.msg;
		    			if($rootScope.inner){
			    			$state.go('verification', {email: $scope.user.email});
			    		}else{
				    		$state.go('verify', {email: $scope.user.email});
				    	}
                        
                    }, 1500);
                }else{
                	$rootScope.showError();
                	$scope.loginError = data.data.msg;
                }
    			$scope.$broadcast("processing_done");

    			$timeout(function(){
    				$scope.processing = false;
	    		}, 500);
    		});
		}
	}

	$scope.getOffers = function(){
		$http({
	        method: 'GET',
	        url: 'scripts/getoffers.php?id='+$rootScope.mvx_globals.currentUser.userdata.data
		}).then(function(data){
			console.log(data);
			$rootScope.offers = data.data;

			angular.forEach($rootScope.offers, function(offer){
				offer.vessels_vessel_photos = JSON.parse(offer.vessels_vessel_photos);
			});

		}).catch(angular.noop);
	}

	$scope.getCharterRequests = function(){
		console.log('Getting Requests');
		$http({
	        method: 'GET',
	        url: 'scripts/getcharterrequests.php'
		}).then(function(data){
			$scope.charters = data.data.reverse();
		}).catch(angular.noop);
	}

	function getUserCharterRequests(){
		$scope.usercharters = null;
		$http({
	        method: 'GET',
	        url: 'scripts/getusercharterrequests.php?id='+$rootScope.mvx_globals.currentUser.userdata.data.id
		}).then(function(data){
			console.log('Charters:', data);
			$scope.usercharters = data.data;

			angular.forEach($scope.usercharters, function(charter){
				charter.userown = true;
			});

		}).catch(angular.noop);
	}

	$scope.getCharterRequests();
	$scope.getVessels();

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

	if ($rootScope.mvx_globals.currentUser) {
		if($rootScope.mvx_globals.currentUser.userdata.data.category == 'Ship Owner'){
			$scope.getOffers();
			$scope.getUserVessels();
		}else{
			getUserCharterRequests();
		}
	}

	$scope.chat = function(){
		$state.go('chat');
	}

	$scope.countries = {
		"AF": "Afghanistan", "AX": "Åland Islands", "AL": "Albania", "DZ": "Algeria", "AS": "American Samoa", "AD": "Andorra", "AO": "Angola", "AI": "Anguilla", "AQ": "Antarctica", "AG": "Antigua and Barbuda", "AR": "Argentina", "AM": "Armenia", "AW": "Aruba", "AU": "Australia", "AT": "Austria", "AZ": "Azerbaijan", "BS": "Bahamas", "BH": "Bahrain", "BD": "Bangladesh", "BB": "Barbados", "BY": "Belarus", "BE": "Belgium", "BZ": "Belize", "BJ": "Benin", "BM": "Bermuda", "BT": "Bhutan", "BO": "Bolivia, Plurinational State of", "BQ": "Bonaire, Sint Eustatius and Saba", "BA": "Bosnia and Herzegovina", "BW": "Botswana", "BV": "Bouvet Island", "BR": "Brazil", "IO": "British Indian Ocean Territory", "BN": "Brunei Darussalam", "BG": "Bulgaria", "BF": "Burkina Faso", "BI": "Burundi", "KH": "Cambodia", "CM": "Cameroon", "CA": "Canada", "CV": "Cape Verde", "KY": "Cayman Islands", "CF": "Central African Republic", "TD": "Chad", "CL": "Chile", "CN": "China", "CX": "Christmas Island", "CC": "Cocos (Keeling) Islands", "CO": "Colombia", "KM": "Comoros", "CG": "Congo", "CD": "Congo, the Democratic Republic of the", "CK": "Cook Islands", "CR": "Costa Rica", "CI": "Côte d'Ivoire", "HR": "Croatia", "CU": "Cuba", "CW": "Curaçao", "CY": "Cyprus", "CZ": "Czech Republic", "DK": "Denmark", "DJ": "Djibouti", "DM": "Dominica", "DO": "Dominican Republic", "EC": "Ecuador", "EG": "Egypt", "SV": "El Salvador", "GQ": "Equatorial Guinea", "ER": "Eritrea", "EE": "Estonia", "ET": "Ethiopia", "FK": "Falkland Islands (Malvinas)", "FO": "Faroe Islands", "FJ": "Fiji", "FI": "Finland", "FR": "France", "GF": "French Guiana", "PF": "French Polynesia", "TF": "French Southern Territories", "GA": "Gabon", "GM": "Gambia", "GE": "Georgia", "DE": "Germany", "GH": "Ghana", "GI": "Gibraltar", "GR": "Greece", "GL": "Greenland", "GD": "Grenada", "GP": "Guadeloupe", "GU": "Guam", "GT": "Guatemala", "GG": "Guernsey", "GN": "Guinea", "GW": "Guinea-Bissau", "GY": "Guyana", "HT": "Haiti", "HM": "Heard Island and McDonald Islands", "VA": "Holy See (Vatican City State)", "HN": "Honduras", "HK": "Hong Kong", "HU": "Hungary", "IS": "Iceland", "IN": "India", "ID": "Indonesia", "IR": "Iran, Islamic Republic of", "IQ": "Iraq", "IE": "Ireland", "IM": "Isle of Man", "IL": "Israel", "IT": "Italy", "JM": "Jamaica", "JP": "Japan", "JE": "Jersey", "JO": "Jordan", "KZ": "Kazakhstan", "KE": "Kenya", "KI": "Kiribati", "KP": "Korea, Democratic People's Republic of", "KR": "Korea, Republic of", "KW": "Kuwait", "KG": "Kyrgyzstan", "LA": "Lao People's Democratic Republic", "LV": "Latvia", "LB": "Lebanon", "LS": "Lesotho", "LR": "Liberia", "LY": "Libya", "LI": "Liechtenstein", "LT": "Lithuania", "LU": "Luxembourg", "MO": "Macao", "MK": "Macedonia, the former Yugoslav Republic of", "MG": "Madagascar", "MW": "Malawi", "MY": "Malaysia", "MV": "Maldives", "ML": "Mali", "MT": "Malta", "MH": "Marshall Islands", "MQ": "Martinique", "MR": "Mauritania", "MU": "Mauritius", "YT": "Mayotte", "MX": "Mexico", "FM": "Micronesia, Federated States of", "MD": "Moldova, Republic of", "MC": "Monaco", "MN": "Mongolia", "ME": "Montenegro", "MS": "Montserrat", "MA": "Morocco", "MZ": "Mozambique", "MM": "Myanmar", "NA": "Namibia", "NR": "Nauru", "NP": "Nepal", "NL": "Netherlands", "NC": "New Caledonia", "NZ": "New Zealand", "NI": "Nicaragua", "NE": "Niger", "NG": "Nigeria", "NU": "Niue", "NF": "Norfolk Island", "MP": "Northern Mariana Islands", "NO": "Norway", "OM": "Oman", "PK": "Pakistan", "PW": "Palau", "PS": "Palestinian Territory, Occupied", "PA": "Panama", "PG": "Papua New Guinea", "PY": "Paraguay", "PE": "Peru", "PH": "Philippines", "PN": "Pitcairn", "PL": "Poland", "PT": "Portugal", "PR": "Puerto Rico", "QA": "Qatar", "RE": "Réunion", "RO": "Romania", "RU": "Russian Federation", "RW": "Rwanda", "BL": "Saint Barthélemy", "SH": "Saint Helena, Ascension and Tristan da Cunha", "KN": "Saint Kitts and Nevis", "LC": "Saint Lucia", "MF": "Saint Martin (French part)", "PM": "Saint Pierre and Miquelon", "VC": "Saint Vincent and the Grenadines", "WS": "Samoa", "SM": "San Marino", "ST": "Sao Tome and Principe", "SA": "Saudi Arabia", "SN": "Senegal", "RS": "Serbia", "SC": "Seychelles", "SL": "Sierra Leone", "SG": "Singapore", "SX": "Sint Maarten (Dutch part)", "SK": "Slovakia", "SI": "Slovenia", "SB": "Solomon Islands", "SO": "Somalia", "ZA": "South Africa", "GS": "South Georgia and the South Sandwich Islands", "SS": "South Sudan", "ES": "Spain", "LK": "Sri Lanka", "SD": "Sudan", "SR": "Suriname", "SJ": "Svalbard and Jan Mayen", "SZ": "Swaziland", "SE": "Sweden", "CH": "Switzerland", "SY": "Syrian Arab Republic", "TW": "Taiwan, Province of China", "TJ": "Tajikistan", "TZ": "Tanzania, United Republic of", "TH": "Thailand", "TL": "Timor-Leste", "TG": "Togo", "TK": "Tokelau", "TO": "Tonga", "TT": "Trinidad and Tobago", "TN": "Tunisia", "TR": "Turkey", "TM": "Turkmenistan", "TC": "Turks and Caicos Islands", "TV": "Tuvalu", "UG": "Uganda", "UA": "Ukraine", "AE": "United Arab Emirates", "GB": "United Kingdom", "US": "United States", "UM": "United States Minor Outlying Islands", "UY": "Uruguay", "UZ": "Uzbekistan", "VU": "Vanuatu", "VE": "Venezuela, Bolivarian Republic of", "VN": "Viet Nam", "VG": "Virgin Islands, British", "VI": "Virgin Islands, U.S.", "WF": "Wallis and Futuna", "EH": "Western Sahara", "YE": "Yemen", "ZM": "Zambia", "ZW": "Zimbabwe"};
});