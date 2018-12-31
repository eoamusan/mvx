app.controller('vesselCtrl', function($scope, $rootScope, $http, $state, utils, $stateParams, $timeout) {

    function getVessel(id){
		$http({
	        method: 'GET',
	        url: 'scripts/getvessel.php?id='+$stateParams.vessel_id
		}).then(function(data){
			$scope.vessel = data.data;
			$scope.vessel.vessel_photos = JSON.parse($scope.vessel.vessel_photos);

			getUser(data.data.user_id, function(data){
				$scope.vessel.user = data;
			});

			console.log('Vessel: ', $scope.vessel);
		}).catch(angular.noop);
	}

    $scope.getChatId = function(userid, peer, vessel){
    	if ($rootScope.mvx_globals.currentUser) {
	    	$scope.processing = true;

			var credentials = {
				userid: userid,
				peer: peer.id,
				vessel: vessel.id
			};

			$http({
	            method: "post",
	            url: 'scripts/getchatid.php',
	            data: credentials
	        }).then(function successCallback(data) {
	        	
	            $state.go('chat', {"vessel": vessel, "peer": peer, "chat_id": data.data.code});

	        	$timeout(function(){
	        		$scope.processing = false;
	        	}, 500);
	            
	        }, function errorCallback(data) {
	            
	            console.log(data);
	            $scope.processing = false;

	        });
	    }else{
	    	$rootScope.gotoLogin();
	    }
	}

	function getUser(userid, callback){
		var url = 'scripts/fetchuser.php?id='+userid+'&src=id';

		$http({
	        method: 'GET',
	        url: url
		}).then(function(data){
            callback(data.data.data);
		}).catch(angular.noop);
    }

	if(utils.objectIsEmpty($stateParams.vessel)){
		getVessel($stateParams.vessel_id);
	}else{
		$scope.vessel = $stateParams.vessel;
		getUser($stateParams.vessel.user_id, function(data){
			$scope.vessel.user = data;
		});
	}

});