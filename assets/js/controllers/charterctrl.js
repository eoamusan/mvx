app.controller('charterCtrl', function($rootScope, $scope, $http, $state, $timeout, utils, AuthenticationService) {
	$scope.user = {};
	$scope.charter = {};
	$scope.charterSuccess = false;

	$timeout(function(){
		loadCalender();
	}, 500);

	$scope.chooseDuration = function(){
		$scope.charter.duration = "";
	}

	$scope.charterVessel = function(){
		var validate = utils.validate();
		var data = angular.copy($scope.charter);
		$scope.processing = true;

		if (validate == 0) {
			data.userid = $rootScope.globals.currentUser.userdata.data.id;
			data.expected_mob_date = document.getElementById('inputdate').value;

			data.username = $rootScope.globals.currentUser.userdata.data.c_name;
			data.usermobile = $rootScope.globals.currentUser.userdata.data.c_mobile;
			data.useremail = $rootScope.globals.currentUser.userdata.data.c_email;

			$http({
	                method: 'POST',
	                url: 'scripts/charter.php',
	                data: data
	            }).then(function(data){
	            	console.log(data);
	            	$scope.processing = false;
	            	$scope.charterStatus = data.data.msg;

	            	if(data.data.msg == "Charter Added"){
	            		$scope.getCharterRequests();
	            		$scope.charter = {};
	            		$timeout(function(){
	            			$scope.charterSuccess = true;
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
        	$scope.charterError = "Incomplete fields. Please check all fields.";
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

	$scope.clearCharterError = function(){
		$scope.charterError = false;
	}

	$scope.fileChanged = function(file, src){
		var f = file.files[0],
		r = new FileReader();

		console.log(file.files[0].type);

		r.onloadend = function(e) {
			$scope.charter[src] = e.target.result;
			$scope.charter[(src+'name')] = file.files[0].name;

			console.log($scope.charter);

			$scope.charter[(src+'preview')] = {
				'background-image': 'url('+parseBg($scope.charter[src], file.files[0].type)+')'
			}

			$scope.$apply();
		}

		r.readAsDataURL(f, "UTF-8");
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