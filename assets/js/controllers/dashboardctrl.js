app.controller('dashboardCtrl', function($rootScope, $scope, $http, $state, $firebaseArray, utils, AuthenticationService) {
	$scope.show = false;
	$scope.offerShow = false;
	$scope.charterShow = false;

	$scope.addcharter = function(){
		$state.go('charter');
	}
	
	$scope.editCharterEnquiry = function(charter) {
		$state.go('editcharter', {charter: charter});
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

	function addChatAddons(chats){
		angular.forEach(chats, function(chat){
			$http({
		        method: 'GET',
		        url: 'scripts/getchatdetails.php?id='+chat.$id
			}).then(function(data){
				var peer;

				if(data.data.data.origin == $rootScope.mvx_globals.currentUser.userdata.data.id){
					peer = data.data.data.peer;
				}else{
					peer = data.data.data.origin;
				}

				$scope.getUser(peer, function(data){
					chat.peer_object = data;
					chat.peer_object.s_services = JSON.parse($scope.peer.s_services);
				});

				$scope.getVessel(data.data.data.vessel, function(data){
					chat.vessel = data.data;
					chat.vessel.vessel_photos = JSON.parse(data.data.vessel_photos);
				});
			}).catch(angular.noop);
        });
	}
	
	if ($rootScope.mvx_globals.currentUser) {
		var ref = firebase.database().ref("chats/records");
		var chats = $firebaseArray(ref.child($rootScope.mvx_globals.currentUser.userdata.data.id));

		chats.$loaded()
	        .then(function(response){
	            $scope.chats = response;
	            $scope.chats.peer_object = {};
	            $scope.chats.vessel = {};

	            addChatAddons($scope.chats);

			    chats.$watch(function(event) {
				  	addChatAddons(chats);
				});
	        });


		if($rootScope.mvx_globals.currentUser.userdata.data.category == 'Charterer'){
			$scope.search.left_light = true;
			$scope.getOffers();
			$scope.getCharterRequests();
		}else if($rootScope.mvx_globals.currentUser.userdata.data.category == 'Ship Owner'){
			$scope.search.left_light = false;
			// $scope.getVessels();
			$scope.getUserVessels();
		}
	}

});