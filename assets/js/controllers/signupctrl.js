app.controller('signupCtrl', function($rootScope, $scope, $http, $state) {
    $rootScope.user = {};

    $scope.signUpStep = 1;

    $scope.proceed = function(){
    	var valids = angular.element(document.querySelectorAll('[data-validate]'));

		var validation = 0;

        angular.forEach(valids, function(valid){

        	angular.element(valid.querySelector('div.status')).removeClass('showStatus');

            if(((angular.element(valid).find('input').val() == "") || (angular.element(valid).find('select').val() == "")) && (valid.attributes['data-validate'].value != "Confirm Password")){
            	angular.element(valid.querySelector('div.status')).html(valid.attributes['data-validate'].value+" is required");
            	angular.element(valid.querySelector('div.status')).addClass('showStatus');

            	validation++;
            }else if(valid.attributes['data-validate'].value == "Confirm Password"){
            	if($scope.user.password != $scope.user.confirmpassword){
            		angular.element(valid.querySelector('div.status')).html("Please confirm password correctly");
            		angular.element(valid.querySelector('div.status')).addClass('showStatus');

	            	validation++;
	            }
            }

        });

        console.log(validation);

    	if($scope.signUpStep < 3 && validation == 0){
		    $scope.signUpStep++;
		}
	}

	$rootScope.user.services = [{id: 'service1'}];
	$rootScope.user.permits = [{id: 'permit1'}];
	$rootScope.user.records = [{id: 'record1'}];

	$scope.addNewservice = function() {
		var newItemNo = $rootScope.user.services.length + 1;
		$rootScope.user.services.push({'id':'service' + newItemNo});
	};

	$scope.addNewPermit = function() {
		var newItemNo = $rootScope.user.permits.length + 1;
		$rootScope.user.permits.push({'id':'permit' + newItemNo});
	};

	$scope.addNewRecord = function() {
		var newItemNo = $rootScope.user.records.length + 1;
		$rootScope.user.records.push({'id':'record' + newItemNo});
	};

	$scope.removeService = function(item) {
		$rootScope.user.services.splice(item, 1);
	}

	$scope.removePermit = function(item) {
		$rootScope.user.permits.splice(item, 1);
	}

	$scope.removeRecord = function(item) {
		$rootScope.user.records.splice(item, 1);
	}
});