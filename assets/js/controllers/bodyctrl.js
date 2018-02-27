app.controller('bodyCtrl', function($rootScope, $scope, $http, $state, $interval) {
    $scope.logout = function(){
    	$state.go('logout');
    }
});