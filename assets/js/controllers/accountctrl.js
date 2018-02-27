app.controller('accountCtrl', function($rootScope, $scope, $http, $state, $interval) {
    $scope.login = function(){
        $state.go('login');
    }

    $scope.signup = function(){
        $state.go('signup');
    }
});