app.factory('AuthenticationService', ['Base64', '$http', '$cookies', '$rootScope', '$timeout',
    function(Base64, $http, $cookies, $rootScope, $timeout) {
        var service = {};

        service.Login = function(credentials, callback) {

            var login_request = $http({
                method: "post",
                url: "scripts/login.php",
                data: credentials
            });

            login_request.then(function successCallback(data) {

                callback(data);

                if((data.status == 200) && data.data.data.verified && data.data.data.enabled){
                    service.setOnline(data.data.data.id);
                }
                
            }, function errorCallback(data) {
                
                callback(data);

            });

        };

        service.setOnline = function(uid){
            var connectedRef = firebase.database().ref('.info/connected');
            var usersRef = firebase.database().ref('presence/' + uid);

            connectedRef.on('value', function(snapshot) {
                if (snapshot.val()) {
                    usersRef.child('online').onDisconnect().set(firebase.database.ServerValue.TIMESTAMP);
                    usersRef.child('online').set(true);
                }
            });
        }

        service.checkStatus = function(){
            var connectedRef = firebase.database().ref(".info/connected");

            connectedRef.on("value", function(snap) {
                
            });
        }

        service.SetCredentials = function(email, password, userdata) {
            $rootScope.mvx_globals = {
                currentUser: {
                    email: email,
                    password: password,
                    userdata: userdata
                }
            };

            $cookies.putObject('mvx_globals', $rootScope.mvx_globals);
        };

        service.ClearCredentials = function() {
            $rootScope.mvx_globals = {};
            $cookies.remove('mvx_globals');
            // $http.defaults.headers.common.Authorization = 'Basic ';
        };

        return service;
    }
]);