app.controller('addvesselCtrl', function($rootScope, $scope, $http, $state, $timeout, utils, AuthenticationService) {
	$scope.user = {};
	$scope.addvessel = {};
	$scope.addvesselSuccess = false;

	$scope.addVessel = function(){
		var validate = utils.validate();
		var data = angular.copy($scope.addvessel);
		$scope.processing = true;

		if (validate == 0) {
			data.userid = $rootScope.globals.currentUser.userdata.data.id;
			if(document.getElementById('inputdate')){
				data.unavailable_till = document.getElementById('inputdate').value;
			}
			data.classification_expiry = document.getElementById('inputdate2').value;

			data.username = $rootScope.globals.currentUser.userdata.data.display_name;
			data.usermobile = $rootScope.globals.currentUser.userdata.data.s_contactmobile;
			data.useremail = $rootScope.globals.currentUser.userdata.data.s_contactemail;

			$http({
	                method: 'POST',
	                url: 'scripts/addvessel.php',
	                data: data
	            }).then(function(data){
	            	$scope.processing = false;
	            	$scope.addvesselStatus = data.data.msg;

	            	if(data.data.msg == "Vessel Added"){
	            		$scope.getVessels();
	            		$scope.addvessel = {};
	            		$timeout(function(){
	            			$scope.addvesselSuccess = true;
	            		}, 1000)
	            		.then(function(){
					    	return $timeout(function(){
								countdown(3);
						    }, 500)
						    .then(function(){
						    	return $timeout(function(){
						    		$scope.dashboard();
							    }, 4100);
						    });
					    });
	            	}
	            }).catch(angular.noop);
        }else{
        	$scope.addvesselError = "Incomplete fields. Please check all fields.";
        	$scope.enforceError = true;
        	$rootScope.showError();
        	$scope.processing = false;

        	$timeout(function(){
        		$rootScope.closeError();
        		$scope.enforceError = false;
        	}, 5000);

        	var el = document.getElementById('charter');
        	el.children[2].scrollTop = 0;
        }
	}

	$scope.loadCalender = function(){
		$timeout(function(){
			loadCalender();
		}, 500);
	}

	$scope.loadCalender();

	$scope.clearAddVesselError = function(){
		$scope.addvesselError = false;
	}

	$scope.fileChanged = function(file, src){
		var photo = '';

		if(file.files[0].type.indexOf('image') >= 0){
			if(!$scope.addvessel[(src+'preview')]){
				$scope.addvessel[(src+'preview')] = [];
			}
			if(file.files.length == 1){ photo = 'photo';}else{photo = 'photos';}
			$scope.addvessel[(src+'name')] = file.files.length + ' ' + photo + ' added';
		}

		angular.forEach(file.files, function(file_unit, key){
			var f = file_unit;
			var r = new FileReader();

			r.onloadend = function(e) {
				if(file.files[0].type.indexOf('image') < 0){
					$scope.addvessel[src] = e.target.result;
					$scope.addvessel[(src+'name')] = file.files[0].name;
				}
				
				if(file_unit.type.indexOf('image') < 0){
					$scope.addvessel[(src+'preview')] = {
						'background-image': 'url('+parseBg($scope.addvessel[src], file_unit.type)+')'
					}
				}else{
					$scope.addvessel[(src+'preview')].push({
								'image': parseBg(e.target.result, file_unit.type),
								'name': file_unit.name
							});
				}

				$scope.$apply();
			}

			r.readAsDataURL(f, "UTF-8");
		});
	}

	function parseBg(file, file_type){
		if (file_type.indexOf('image') >=0) {
			return file;
		}else{
			if (file_type.indexOf('pdf') >= 0) {
				return 'assets/images/icons/pdf.png';
			}else if (file_type.indexOf('word') >= 0) {
				return 'assets/images/icons/word.png';
			}else if ((file_type.indexOf('presentation') >= 0) || (file_type.indexOf('powerpoint') >= 0)) {
				return 'assets/images/icons/ppt.png';
			}else{
				return 'assets/images/icons/file.png';
			}
		}
	}
});