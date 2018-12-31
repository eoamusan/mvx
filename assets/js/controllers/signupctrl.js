app.controller('signupCtrl', function($rootScope, $scope, $http, $state, utils, $timeout) {
    $rootScope.usersignup = {};
	$rootScope.usersignup.services = [{id: 'service1'}];
	$rootScope.usersignup.shipservices = [{id: 'service1'}];
	$rootScope.usersignup.permits = [{id: 'permit1'}];
	$rootScope.usersignup.shippermits = [{id: 'permit1'}];

	console.log($rootScope.usersignup);

    $scope.processing = false;

    $scope.signUpStep = 1;
    $scope.signupError = false;

    $scope.proceed = function(){
    	var validation = utils.validate();
    	$scope.signupError = false;

    	if($scope.signUpStep < 3 && validation == 0){
    		if($scope.signUpStep == 1){
    			$scope.processing = true;
    			console.log($rootScope.usersignup);

    			$http({
	                method: 'POST',
	                url: 'scripts/checkEmailAvailability.php',
	                data: $rootScope.usersignup
	            }).then(function(data){
	            	console.log(data);
    				$scope.processing = false;
	                if (data.data == "false") {
	                	$scope.closeError();
	                	$scope.signUpStep++;
	                }else{
	                	$scope.showError();
	                }

	            }).catch(angular.noop);
			}else{
				$scope.signUpStep++;
			}
		}else if($scope.signUpStep == 3 && validation == 0){
			$scope.closeError();
			$scope.processing = true;

			$http({
	                method: 'POST',
	                url: 'scripts/signup.php',
	                data: $rootScope.usersignup
	            }).then(function(data){
	            	console.log(data);
    				$scope.processing = false;
    				
    				if(data.data.msg == "Registration Successful"){

    					$scope.signUpStep = 4;
					    $timeout(function(){
					    	successAnimation();
    						countdown(5);
					    }, 500)
					    .then(function(){
					    	return $timeout(function(){
						    	$state.go('verify', {email: $rootScope.usersignup.email});
						    }, 5100);
					    });
    				}else{
    					$scope.showError();
    					$scope.signupError = data.data.msg;
    					var el = document.getElementById('signupContainer');
    					el.children[2].scrollTop = 0;
    				}

	            }).catch(angular.noop);
		}
	}

	$scope.goBack = function(){
		if ($scope.signUpStep > 1){
			$scope.signUpStep--;
		}
	}

	$scope.useAnother = function(){
		$rootScope.usersignup.email = "";
		document.getElementById("useAnother").focus();
	}

	$scope.fileChanged = function(file, src){
		var f = file.files[0],
		r = new FileReader();

		console.log(file.files[0].type);

		r.onloadend = function(e) {
			$rootScope.usersignup[src] = e.target.result;
			$rootScope.usersignup[(src+'name')] = file.files[0].name;

			console.log($rootScope.usersignup);

			$rootScope.usersignup[(src+'preview')] = {
				'background-image': 'url('+parseBg($rootScope.usersignup[src], file.files[0].type)+')'
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

	$scope.addNewservice = function(obj) {
		var newItemNo = obj.length + 1;
		obj.push({'id':'service' + newItemNo});
	};

	$scope.addNewPermit = function(obj) {
		var newItemNo = obj.length + 1;
		obj.push({'id':'permit' + newItemNo});
	};

	$scope.removeService = function(obj, item) {
		obj.splice(item, 1);
	}

	$scope.removePermit = function(obj, item) {
		obj.splice(item, 1);
	}
});