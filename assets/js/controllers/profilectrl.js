app.controller('profileCtrl', function($rootScope, $scope, $timeout, $http, $state, utils, $stateParams) {
    $scope.editing = false;
    $rootScope.usersignup = $rootScope.mvx_globals.currentUser.userdata.data;
    console.log($rootScope.usersignup);

    if($rootScope.mvx_globals.currentUser.userdata.data.category != 'Charterer'){
        if(typeof $rootScope.usersignup.s_services == "string") {
    	    $rootScope.usersignup.s_services = JSON.parse($rootScope.usersignup.s_services);
    	}
        if(typeof $rootScope.usersignup.s_permits == "string") {
    	    $rootScope.usersignup.s_permits = JSON.parse($rootScope.usersignup.s_permits);
    	}
    }

    $scope.edit = function() {
    	$scope.editing = !$scope.editing;
    }

    $scope.updateProfile = function() {
        $scope.processing = true;
        var data = angular.copy($rootScope.usersignup);
        console.log(data);

        $http({
                method: 'POST',
                url: 'scripts/update.php',
                data: $rootScope.usersignup
            }).then(function(data){
                console.log(data);
                $scope.processing = false;

                if (data.data.msg == "Account Updated") {
                    $scope.profileUpdated = true;
                    $scope.editing = false;
                }
            }).catch(angular.noop);
    }

    $scope.closeProfileuUpdateSuccess = function() {
        $scope.profileUpdated = false;
    }
});