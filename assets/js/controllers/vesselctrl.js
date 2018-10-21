app.controller('vesselCtrl', function($scope, $http, $state, utils, $stateParams) {

    $scope.getCharterRequest = function(id){
		$http({
	        method: 'GET',
	        url: 'scripts/getvessel.php?id='+$stateParams.vessel_id
		}).then(function(data){
			$scope.vessel = data.data;
			$scope.vessel.vessel_photos = JSON.parse($scope.vessel.vessel_photos);

			console.log('Vessel: '+$scope.vessel);
		}).catch(angular.noop);
	}

	if(utils.objectIsEmpty($stateParams.vessel)){
		$scope.getCharterRequest($stateParams.vessel_id);
	}else{
		$scope.vessel = $stateParams.vessel;
	}

});