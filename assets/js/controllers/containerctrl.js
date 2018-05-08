app.controller('containerCtrl', function($rootScope, $scope, $http, $state) {
    $rootScope.removeGoHome = function(){
    	$state.go('home');
    }

    $rootScope.login = function(){
    	$state.go('login');
    }

    $rootScope.signup = function(){
    	$state.go('signup');
    }
});