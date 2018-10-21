app.controller('verifyCtrl', function($rootScope, $scope, $http, $state, utils, $timeout, $stateParams, $location, $anchorScroll) {
    $scope.user = {};
    $scope.processing = false;
    $scope.verificationError = false;
    $scope.emailSent = false;

    $scope.user.codes = [{},{},{},{},{},{}];
   	$timeout(function(){
		document.querySelector(".verify div:first-of-type input").focus();
	});   

    if($stateParams.email){
	    $scope.emailSent = true;
	    $scope.user.email = $stateParams.email;
	}

    $scope.verify = function(){
		$scope.closeError();
    	var validation = utils.validate();
    	$scope.verificationError = false;
    	$scope.verificationSuccess = false;

    	if(validation == 0){

    		$scope.user.code = $scope.user.codes.map((code) => {return code.value;});
    		$scope.user.code = parseInt($scope.user.code.join(''));

			$scope.closeError();
			$scope.processing = true;

			$http({
	                method: 'POST',
	                url: 'scripts/verify.php',
	                data: $scope.user
	            }).then(function(data){
	            	console.log(data);
    				$scope.processing = false;
    				
    				if(data.data.msg == "Account Verified"){
    					showError();

    					$scope.verificationSuccess = data.data.msg;
					    $timeout(function(){
					    	if($rootScope.inner){
				    			$state.go('signin', {email: $scope.user.email});
				    		}else{
					    		$state.go('login', {email: $scope.user.email});
					    	}
					    	
					    }, 2000);

    				}else{
    					showError();
    					$scope.verificationError = data.data.msg;
    					var el = document.getElementById('signupContainer');
    					el.children[2].scrollTop = 0;
    				}

	            }).catch(angular.noop);
		}
	}

	$scope.resendVerification = function(){
		$location.hash('');
		$anchorScroll();

		var validation = 0;
    	var valid = document.querySelector('[data-validate="Email address"]');
    	var validelem = angular.element(valid);
    	console.log(valid.querySelector('div.status'));
    	angular.element(valid.querySelector('div.status')).removeClass('showStatus');

    	if($scope.user.email == "" || $scope.user.email == undefined){
    		angular.element(validelem[0].querySelector('div.status')).html("Email address is required");
    		angular.element(validelem[0].querySelector('div.status')).addClass('showStatus');

        	validation++;
        }

		$scope.sendingVerification = false;		

        var data = {
            email: $scope.user.email
        }

        if(validation == 0){
            $scope.sendingVerification = true;

            $http({
                method: 'POST',
                url: 'scripts/resendverification.php',
                data: data
            }).then(function(data){
            	console.log(data);
                if(data.data.msg == "Verification Sent"){
                    $scope.sendingVerification = false;
                    $scope.verificationSent = true;
                    $scope.onceSent = true;

                    countdown(29);

                    showMoreOptionsAfterAWhile();
                }else{
                    $scope.verificationError = data.data.msg;
                    showMoreOptionsAfterAWhile();
                }

            }).catch(angular.noop);
    	}else{
    		$timeout(function(){    			
    			$scope.processing = false;
    		}, 500);
    	}
	}

	function showMoreOptionsAfterAWhile(){
        setTimeout(function(){
            $scope.verificationSent = false;
            $scope.verificationError = false;

            $scope.$apply();

            var el = document.getElementById('signupContainer');

			$location.hash('moreVerificationOptions');
			$anchorScroll();
        }, 37300);
    }

	function showError(){
		$scope.showingError = true;
	}
	$scope.closeError = function(){
		$scope.showingError = false;
	}
});