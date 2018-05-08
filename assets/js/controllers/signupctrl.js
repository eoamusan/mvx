app.controller('signupCtrl', function($rootScope, $scope, $http, $state) {
    $rootScope.user = {};

    $scope.signUpStep = 1;

    $scope.proceed = function(){
    	if($scope.signUpStep < 3){
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