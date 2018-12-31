app.controller('chatCtrl', function($rootScope, $scope, $http, $state, $interval, $timeout, utils, $stateParams, $firebaseArray) {
	$scope.chatInfo = 'user';
	$scope.chat = {};
	var ref = firebase.database().ref();
	var chats = ref.child("chats");

    function getChatDetails(id){
		$http({
	        method: 'GET',
	        url: 'scripts/getchatdetails.php?id='+id
		}).then(function(data){
			if(data.data.msg !== 'Chat null'){
				if((data.data.data.origin == $rootScope.mvx_globals.currentUser.userdata.data.id) || (data.data.data.peer == $rootScope.mvx_globals.currentUser.userdata.data.id)){
					getChats($stateParams.chat_id);

					if(data.data.data.origin == $rootScope.mvx_globals.currentUser.userdata.data.id){
						$scope.getUser(data.data.data.peer, function(data){
							$scope.peer = data;
							$scope.peer.s_services = JSON.parse($scope.peer.s_services);
						});
					}else{
						$scope.getUser(data.data.data.origin, function(data){
							$scope.peer = data;
							$scope.peer.s_services = JSON.parse($scope.peer.s_services);
						});
					}

					$scope.getVessel(data.data.data.vessel, function(data){
						$scope.vessel = data.data;
						$scope.vessel.vessel_photos = JSON.parse($scope.vessel.vessel_photos);
					});
				}else{
					$scope.getVessel(data.data.data.vessel, function(data){
						var vessel = data.data;
						vessel.vessel_photos = JSON.parse(vessel.vessel_photos);
						$state.go('vessel', {"vessel": vessel, "vessel_id": data.data.id});
					});
				}
			}else{
				$scope.home();
			}
		}).catch(angular.noop);
	}

	$scope.showChatInfo = function(value){
		if($scope.chatInfo == value){
			$scope.chatInfo = false;
		}else{
			$scope.chatInfo = value;
		}
	}

	$scope.sendChat = function(){
		$scope.sendingChat = true;
		var created_at = + new Date();
		var chat = $firebaseArray(chats.child($stateParams.chat_id));
		var record_for_user = chats.child('records/'+$rootScope.mvx_globals.currentUser.userdata.data.id+'/'+$stateParams.chat_id);
		var record_for_peer = chats.child('records/'+$scope.peer.id+'/'+$stateParams.chat_id);

		var data = {
            author: $rootScope.mvx_globals.currentUser.userdata.data.id,
            msg: $scope.chat.term,
            created_at: created_at
        };

		var data_for_user = {
            peer: $scope.peer.id,
            last: $scope.chat.term,
            last_time: created_at
        };

		var data_for_peer = {
            peer: $rootScope.mvx_globals.currentUser.userdata.data.id,
            last: $scope.chat.term,
            last_time: created_at
        };

        chat.$add(data).then(function(ref){
        	record_for_user.set(data_for_user);
        	record_for_peer.set(data_for_peer);

        	$scope.sendingChat = false;
        	$scope.chat.term = "";
        });
	}

	function getChats(chat_id){
	    var chats = ref.child("chats");

		list = $firebaseArray(chats.child(chat_id));

	    list.$loaded()
	        .then(function(response){
	            $scope.chats = response;

	            $timeout(function(){
		   			var chatContainer = document.getElementById('chatContainer');
		   			chatContainer.querySelector('.simplebar-scroll-content').scrollTop = chatContainer.querySelector('.simplebar-scroll-content').querySelector('.simplebar-content').offsetHeight;
				});

			    list.$watch(function(event) {
				  	$timeout(function(){
						var chatContainer = document.getElementById('chatContainer');
		   				chatContainer.querySelector('.simplebar-scroll-content').scrollTop = chatContainer.querySelector('.simplebar-scroll-content').querySelector('.simplebar-content').offsetHeight;
					});
				});
	        });

	}

	if(utils.objectIsEmpty($stateParams.peer)){
		getChatDetails($stateParams.chat_id);
	}else{
		$scope.peer = $stateParams.peer;
		if($scope.peer.category == 'Ship Owner'){
			$scope.peer.s_services = JSON.parse($stateParams.peer.s_services);
		}
		$scope.vessel = $stateParams.vessel;
		getChats($stateParams.chat_id);
	}
});