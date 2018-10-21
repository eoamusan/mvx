var app = angular.module("doctorDialApp", ["ui.router", "slick", "ngSanitize", "ngCookies"]);
app.config(function($stateProvider, $urlRouterProvider, $locationProvider, $httpProvider) {

    $urlRouterProvider.otherwise('/');

    $httpProvider.interceptors.push('BasicAuthInterceptorService');

    $stateProvider

        .state('container', {
            templateUrl : "views/container.html",
            controller : "containerCtrl"
        })

        .state('home', {
            url: '/',
            parent: 'container',
            views: {
                'home@container': {
                    templateUrl : "views/home.html",
                    controller : "homeCtrl"
                }
            }
        })

        .state('vessel', {
            url: '/vessel/:vessel_id',
            parent: 'container',
            views: {
                'home@container': {
                    templateUrl : "views/vessel.html",
                    controller : "vesselCtrl"
                }
            },
            params: {
                vessel: {}
            }
        })

        .state('enquiry', {
            url: '/enquiry/:enquiry_id',
            parent: 'container',
            views: {
                'home@container': {
                    templateUrl : "views/enquiry.html",
                    controller : "enquiryCtrl"
                }
            },
            params: {
                enquiry: {}
            }
        })

        .state('login', {
            url: '/login',
            parent: 'container',
            views: {
                'overlay@container': {
                    templateUrl : "views/login.html",
                    controller : "loginCtrl"
                },
                'authlogo@container': {
                    templateUrl : "views/authlogo.html",
                    controller : "loginCtrl"
                },
                'home@container': {
                    templateUrl : "views/home.html",
                    controller : "homeCtrl"
                },
                'form@login': {
                    templateUrl : "views/login/form.html",
                    controller : "loginCtrl"
                }
            },
            params: {
                email: null
            }
        })

        .state('signup', {
            url: '/signup',
            parent: 'container',
            views: {
                'overlay@container': {
                    templateUrl : "views/signup.html",
                    controller : "signupCtrl"
                },
                'authlogo@container': {
                    templateUrl : "views/authlogo.html",
                    controller : "loginCtrl"
                },
                'home@container': {
                    templateUrl : "views/home.html",
                    controller : "homeCtrl"
                },
                'choosecategory@signup': {
                    templateUrl : "views/signup/choosecategory.html"
                },
                'charterer@signup': {
                    templateUrl : "views/signup/categories/charterer.html"
                },
                'procurement@signup': {
                    templateUrl : "views/signup/categories/procurement.html"
                },
                'shipowner@signup': {
                    templateUrl : "views/signup/categories/shipowner.html"
                },
                'successanimation@signup': {
                    templateUrl : "views/signup/successanimation.html"
                }
            }
        })

        .state('verify', {
            url: '/verify',
            parent: 'container',
            views: {
                'overlay@container': {
                    templateUrl : "views/verify.html",
                    controller : "verifyCtrl"
                },
                'authlogo@container': {
                    templateUrl : "views/authlogo.html",
                    controller : "loginCtrl"
                },
                'home@container': {
                    templateUrl : "views/home.html",
                    controller : "homeCtrl"
                },
                'form@verify': {
                    templateUrl : "views/verify/form.html",
                    controller : "verifyCtrl"
                }
            },
            params: {
                email: null
            }
        })

        .state('inner', {
            templateUrl : "views/inner.html",
            controller : "innerCtrl"
        })

        .state('dashboard', {
            url: '/dashboard',
            parent: 'inner',
            cache: false,
            views: {
                'content@inner': {
                    templateUrl : "views/dashboard.html",
                    controller : "dashboardCtrl"
                },
                'vessel@dashboard': {
                    templateUrl : "views/dashboard/vessel.html"
                },
                'offer@dashboard': {
                    templateUrl : "views/dashboard/offer.html"
                },
                'charter_enquiry@dashboard': {
                    templateUrl : "views/dashboard/charter_enquiry.html"
                },
                'chat@dashboard': {
                    templateUrl : "views/dashboard/chat.html"
                }
            }
        })

        .state('signin', {
            url: '/signin',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/login/form.html",
                    controller : "loginCtrl"
                }
            },
            params: {
                email: null
            }
        })

        .state('verification', {
            url: '/verification',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/verify/form.html",
                    controller : "verifyCtrl"
                }
            },
            params: {
                email: null
            }
        })

        .state('logout', {
            url: '/logout',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/logout.html",
                    controller : "logoutCtrl"
                }
            }
        })

        .state('charter', {
            url: '/charter',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/charter.html",
                    controller : "charterCtrl"
                }
            }
        })

        .state('addvessel', {
            url: '/addvessel',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/addvessel.html",
                    controller : "addvesselCtrl"
                }
            }
        })
});

app.run(function($rootScope, $state, AuthenticationService, $cookies, $location, $http, $timeout) {

    $rootScope.globals = $cookies.getObject('globals') || {};

    var routes_nloggedin = ['/login', '/signin', '/signup', '/signup/doctor'];
    var routes_loggedin = ['/dashboard', '/charter', '/addvessel'];

    console.log($rootScope.globals);

    if ($rootScope.globals.currentUser) {
        var credentials = {
            email: $rootScope.globals.currentUser.email,
            password: $rootScope.globals.currentUser.password
        };

        AuthenticationService.Login(credentials, function(data){
            console.log(data);

            if(data.status === 200){

                if(data.data.msg == "Account Not Verified"){
                    $rootScope.globals = {};
                    $cookies.remove('globals');
                    
                    if($rootScope.inner){
                        $state.go('verification', {email: credentials.email});
                    }else{
                        $state.go('verify', {email: credentials.email});
                    }                    
                }else if(data.data.msg != "Logged In"){
                    console.log(data);
                    console.log($cookies.getAll());
                    $rootScope.globals = {};
                    $cookies.remove("globals");
                    console.log($cookies.getAll());

                    $state.go('login');
                }else{
                    AuthenticationService.SetCredentials(credentials.email, credentials.password, data.data);
                }

            }else{

                $rootScope.globals = {};
                $cookies.remove('globals');
                $state.go('login');

            }

        });
    }

    $rootScope.$on('$locationChangeStart', function(event, toState, toParams, fromState, fromParams) {
        if ((routes_nloggedin.indexOf($location.path()) !== -1) && $rootScope.globals.currentUser) {
            // User is authenticated
            $state.go("dashboard");
            event.preventDefault();
        }

        if ((routes_loggedin.indexOf($location.path()) !== -1) && !$rootScope.globals.currentUser) {
            // User isn’t authenticated
            $state.go("login");
            event.preventDefault();
        }

        $rootScope.stateIsLoading = true;
    });

    $rootScope.$on('$locationChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
        $timeout(function(){
            $rootScope.stateIsLoading = false;
        }, 2000);
    });
});