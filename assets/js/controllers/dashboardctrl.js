app.controller('dashboardCtrl', function($rootScope, $scope, $http, $state, utils, AuthenticationService) {
	$scope.show = false;
	$scope.offerShow = false;
	$scope.charterShow = false;

	$scope.addcharter = function(){
		$state.go('charter');
	}

	$scope.revealShow = function(obj, src){
		if(src == 'vessel'){
			if($scope.show == obj.id){
				$scope.show = false;
			}else{
				$scope.show = obj.id;
			}
		}else if(src == 'offer'){
			if($scope.offerShow == obj.offers_id){
				$scope.offerShow = false;
			}else{
				$scope.offerShow = obj.offers_id;
			}
		}else if(src == 'charter'){
			if($scope.charterShow == obj.id){
				$scope.charterShow = false;
			}else{
				$scope.charterShow = obj.id;
			}
		}
	}
	
	if ($rootScope.globals.currentUser) {
		if($rootScope.globals.currentUser.userdata.data.category == 'Charterer'){
			$scope.search.left_light = true;
			$scope.getOffers();
			$scope.getCharterRequests();
		}else if($rootScope.globals.currentUser.userdata.data.category == 'Ship Owner'){
			$scope.search.left_light = false;
			$scope.getVessels();
		}
	}

});