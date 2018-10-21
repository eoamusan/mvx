app.controller('logoutCtrl', function($rootScope, $scope, $http, $state, utils, AuthenticationService, $timeout) {
	countdown(5);
	var timer;

	$scope.$on('$destroy', function() {
        $timeout.cancel(timer);
    });

	timer = $timeout(function(){
		$state.go('signin');
	}, 6400);
});