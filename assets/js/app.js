var app = angular.module("doctorDialApp", ["ui.router", "slick", "ngSanitize", "ngCookies", "ui.carousel", "firebase"]);
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
            cache: true,
            views: {
                'home@container': {
                    templateUrl : "views/home.html",
                    controller : "homeCtrl"
                },
                'userArea@container': {
                    templateUrl : "views/userarea.html"
                },
                'form@home': {
                    templateUrl : "views/login/form.html"
                },
                'charter@home': {
                    templateUrl : "views/charter_item.html",
                    controller : "homeCtrl"
                },
                'vessel@home': {
                    templateUrl : "views/vessel_item.html",
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
                },
                'userArea@container': {
                    templateUrl : "views/userarea.html"
                },
                'form@vessel': {
                    templateUrl : "views/login/form.html"
                },
                'charter@vessel': {
                    templateUrl : "views/charter_item.html"
                },
                'vessel@vessel': {
                    templateUrl : "views/vessel_item.html",
                    controller : "homeCtrl"
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
                },
                'userArea@container': {
                    templateUrl : "views/userarea.html"
                },
                'form@enquiry': {
                    templateUrl : "views/login/form.html"
                },
                'charter@enquiry': {
                    templateUrl : "views/charter_item.html",
                    controller : "homeCtrl"
                },
                'vessel@enquiry': {
                    templateUrl : "views/vessel_item.html",
                    controller : "homeCtrl"
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
                'choosecategory@signup': {
                    templateUrl : "views/signup/choosecategory.html"
                },
                'charterer@signup': {
                    templateUrl : "views/signup/categories/charterer.html"
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
            // cache: false,
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

        .state('profile', {
            url: '/profile',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/profile.html",
                    controller : "profileCtrl"
                },
                'account@profile': {
                    templateUrl : "views/profile/account.html"
                },
                'changepassword@profile': {
                    templateUrl : "views/profile/changepassword.html"
                }
            }
        })

        .state('chat', {
            url: '/chat/:chat_id',
            parent: 'inner',
            cache: false,
            views: {
                'content@inner': {
                    templateUrl : "views/chat.html",
                    controller : "chatCtrl"
                }
            },
            params: {
                peer: {},
                vessel: {}
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

        .state('editcharter', {
            url: '/edit-charter',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/charter.html",
                    controller : "charterCtrl"
                }
            },
            params: {
                charter: {}
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

        .state('editvessel', {
            url: '/editvessel',
            parent: 'inner',
            views: {
                'content@inner': {
                    templateUrl : "views/addvessel.html",
                    controller : "addvesselCtrl"
                }
            },
            params: {
                vessel: {}
            }
        })
});

app.run(function($rootScope, $state, AuthenticationService, $cookies, $location, $http, $timeout) {
    // var config = {
    //     apiKey: "AIzaSyDX6ru5vWVCcY7ve_mp-dxZlyspUhjGwtw",
    //     authDomain: "mvxchange-realtime-test.firebaseapp.com",
    //     databaseURL: "https://mvxchange-realtime-test.firebaseio.com/",
    //     storageBucket: "mvxchange-realtime-test.appspot.com",
    // };

    var config = {
        apiKey: "AIzaSyAyc-0XL3hbpofaK6LOMfWh5ygqrVsWVIc",
        authDomain: "mvxchange-realtime.firebaseapp.com",
        databaseURL: "https://mvxchange-realtime.firebaseio.com/",
        storageBucket: "mvxchange-realtime.appspot.com",
    };

    firebase.initializeApp(config);

    var connectedRef = firebase.database().ref(".info/connected");

    connectedRef.on("value", function(snap) {
        
    });

    firebase.auth().onAuthStateChanged(function(user) {
        if (user) {
            var isAnonymous = user.isAnonymous;
            var uid = user.uid;

            if($rootScope.mvx_globals.currentUser){
                var usersRef = firebase.database().ref('presence/' + $rootScope.mvx_globals.currentUser.userdata.data.id);

                firebase.database().ref('presence/' + $rootScope.mvx_globals.currentUser.userdata.data.id).once('value').then(function(snapshot) {
                    var update = snapshot.val();
                    update.uid = uid;

                    firebase.database().ref('presence/' + $rootScope.mvx_globals.currentUser.userdata.data.id).update(update);
                });
            }
        } else {
            firebase.auth().signInAnonymously().catch(function(error) {
                console.log(error);
            });
        }
    });

    $rootScope.mvx_globals = $cookies.getObject('mvx_globals') || {};

    var routes_nloggedin = ['/login', '/signin', '/signup', '/signup/doctor'];
    var routes_loggedin = ['/dashboard', '/profile', '/charter', '/addvessel', '/chat'];

    if ($rootScope.mvx_globals.currentUser) {
        var credentials = {
            email: $rootScope.mvx_globals.currentUser.email,
            password: $rootScope.mvx_globals.currentUser.password
        };

        AuthenticationService.Login(credentials, function(data){
            if(data.status === 200){

                if(data.data.msg == "Account Not Verified"){
                    $rootScope.mvx_globals = {};
                    $cookies.remove('mvx_globals');
                    
                    if($rootScope.inner){
                        $state.go('verification', {email: credentials.email});
                    }else{
                        $state.go('verify', {email: credentials.email});
                    }                    
                }else if(data.data.msg != "Logged In"){
                    $rootScope.mvx_globals = {};
                    $cookies.remove("mvx_globals");

                    $state.go('login');
                }else{
                    AuthenticationService.SetCredentials(credentials.email, credentials.password, data.data);
                }

            }else{

                $rootScope.mvx_globals = {};
                $cookies.remove('mvx_globals');
                $state.go('login');

            }

        });
    }

    $rootScope.$on('$locationChangeStart', function(event, toState, toParams, fromState, fromParams) {
        angular.forEach(routes_nloggedin, function(route){
            if (($location.path().indexOf(route) !== -1) && $rootScope.mvx_globals.currentUser) {
                // User is authenticated
                $state.go("dashboard");
                event.preventDefault();
            }
        });

        angular.forEach(routes_loggedin, function(route){
            if (($location.path().indexOf(route) !== -1) && !$rootScope.mvx_globals.currentUser) {
                // User isn’t authenticated
                $state.go("login");
                event.preventDefault();
            }
        });

        $rootScope.stateIsLoading = true;
    });

    $rootScope.$on('$locationChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
        $timeout(function(){
            $rootScope.stateIsLoading = false;
        }, 2000);
    });
});