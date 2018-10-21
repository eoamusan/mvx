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
                
            }, function errorCallback(data) {
                
                callback(data);

            });

        };

        service.SetCredentials = function(email, password, userdata) {
            $rootScope.globals = {
                currentUser: {
                    email: email,
                    password: password,
                    userdata: userdata
                }
            };

            $cookies.putObject('globals', $rootScope.globals);
        };

        service.ClearCredentials = function() {
            $rootScope.globals = {};
            $cookies.remove('globals');
            // $http.defaults.headers.common.Authorization = 'Basic ';
        };

        return service;
    }
]);