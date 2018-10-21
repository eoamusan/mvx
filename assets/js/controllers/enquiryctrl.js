app.controller('enquiryCtrl', function($scope, $http, $state, utils, $stateParams) {

    $scope.getCharterRequest = function(id){
		$http({
	        method: 'GET',
	        url: 'scripts/getcharterrequest.php?id='+$stateParams.enquiry_id
		}).then(function(data){
			$scope.charter = data.data;
		}).catch(angular.noop);
	}

	if(utils.objectIsEmpty($stateParams.enquiry)){
		$scope.getCharterRequest($stateParams.enquiry_id);
	}else{
		$scope.charter = $stateParams.enquiry;
	}

});