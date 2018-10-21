app.controller('loginCtrl', function($rootScope, $scope, $timeout, $http, $state, utils, $stateParams, AuthenticationService) {
    $scope.user = {};

    if($stateParams.email){
    	$scope.user.email = $stateParams.email;
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
		    		if($rootScope.inner){
		    			$state.go('dashboard');
		    		}else{
		    			if(data.data.data.category == "Procurement Vendor / Supplier"){
				    		$state.go('home');
				    	}else{
				    		$state.go('dashboard');
				    	}
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

});