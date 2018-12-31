app.factory('clickAnywhereButHereService', function($document) {
    var tracker = [];

    return function($scope, expr) {
        var i, t, len;
        for (i = 0, len = tracker.length; i < len; i++) {
            t = tracker[i];
            if (t.expr === expr && t.scope === $scope) {
                return t;
            }
        }
        
        var handler = function() {
            $scope.$apply(expr);
        };

        $document.on('click', handler);

        // IMPORTANT! Tear down this event handler when the scope is destroyed.
        $scope.$on('$destroy', function() {
            $document.off('click', handler);
        });

        t = { scope: $scope, expr: expr };
        tracker.push(t);
        return t;
    };
});

app.factory('utils', function () {
    return {

        groupOffers: function(offers){
            grouped_offers = [];

            // for (var i = 0; i < offers.length; i++) {
            //     grouped_offers[offers[i].offers_charter_id] = [];
            // }

            // console.log(grouped_offers);
            // grouped_offers = grouped_offers.filter(function (el) {
            //     console.log(el);
            //     return el !== null;
            // });

            // console.log(grouped_offers);

            angular.forEach(offers, function(offer){
                if(!grouped_offers[offer.offers_charter_id]){
                    grouped_offers[offer.offers_charter_id] = {};
                }

                console.log(grouped_offers);

                grouped_offers[offer.offers_charter_id].charter_id = offer;
                if(formatted_responses[responses[i].user._id].responses == undefined){
                    grouped_offers[offer.offers_charter_id].offers = [];
                    grouped_offers[offer.offers_charter_id].offers.push(offer);
                }else{
                    grouped_offers[offer.offers_charter_id].offers.push(offer);
                }
                
            });

            console.log(grouped_offers);

            return grouped_offers;
        },

        inArray: function(needle, haystack) {
            if(haystack != undefined){
                var length = haystack.length;

                for(var i = 0; i < length; i++) {
                    if(haystack[i]._id == needle) return true;
                }
            }

            return false;
        },

        inArrayFull: function(needle, haystack) {
            if(haystack != undefined){
                var length = haystack.length;

                for(var i = 0; i < length; i++) {
                    if(haystack[i]._id == needle) return haystack[i];
                }
            }

            return false;
        },

        objectIsEmpty: function(obj) {
            for(var prop in obj) {
                if(obj.hasOwnProperty(prop))
                    return false;
            }

            return JSON.stringify(obj) === JSON.stringify({});
        },

        objectToArray: function(obj) {
            var arr = Object.keys(obj).map(i => obj[i])
            // var arr = Object.keys(obj).map(function(key) {
            //     return [Number(key), obj[key]];
            console.log(Array.isArray(arr));
                return arr;
            // });

            // return arr;
        },

        formatDate: function(date, wordy){
            var months_of_the_year = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            date = new Date(date);

            var day = date.getDate();
            var monthIndex = date.getMonth();
            var monthWord = months_of_the_year[date.getMonth()];
            var year = date.getFullYear();

            if(wordy){
                return day + ' ' + monthWord + ', ' + year;
            }else{
                return year + '-' + (monthIndex + 1) + '-' + day;
            }
        },

        formatTime: function(time){
            var hours = time.split(':')[0].split('T')[1];
            var minutes = time.split(':')[1];

            var amPM = (hours > 11) ? "pm" : "am";

            return ((hours > 12) ? (hours % 12) : hours) + ':' + minutes + ' ' + amPM;
        },

        validate: function(){
            var valids = angular.element(document.querySelectorAll('[data-validate]'));

            var validation = 0;

            angular.forEach(valids, function(valid){

                angular.element(valid.querySelector('div.status')).removeClass('showStatus');

                if(((angular.element(valid).find('input').val() == "") || (angular.element(valid).find('select').val() == "") || (angular.element(valid).find('textarea').val() == "")) && (valid.attributes['data-validate'].value != "Confirm Password") && (valid.attributes['data-option'] == undefined)){
                    angular.element(valid.querySelector('div.status')).html(valid.attributes['data-validate'].value+" is required");
                    angular.element(valid.querySelector('div.status')).addClass('showStatus');

                    validation++;
                }else if(valid.attributes['data-option']){
                    if(valid.attributes['data-option'].value == "file-input"){
                        if(angular.element(valid.querySelector('input.hidden')).val() == ""){
                            angular.element(valid.querySelector('div.status')).html(valid.attributes['data-validate'].value+" is required");
                            angular.element(valid.querySelector('div.status')).addClass('showStatus');

                            validation++;
                        }
                    }

                    if(valid.attributes['data-option'].value == "multiple"){
                        var allinputs = valid.querySelectorAll('.verify input');
                        for (var i = 0; i < allinputs.length; i++) {
                            if(allinputs[i].value == ""){
                                angular.element(valid.querySelector('div.status')).html(valid.attributes['data-validate'].value+" is required");
                                angular.element(valid.querySelector('div.status')).addClass('showStatus');
                                validation++;
                            }
                        }
                    }

                    if(valid.attributes['data-option'].value == "radio"){
                        var button = valid.querySelectorAll('input[type="radio"]:checked');

                        if(button.length == 0){
                            angular.element(valid.querySelector('div.status')).html(valid.attributes['data-validate'].value+" is required");
                            angular.element(valid.querySelector('div.status')).addClass('showStatus');
                            validation++;
                        }
                    }
                }else if(valid.attributes['data-validate'].value == "Email address"){
                    if(!validateEmail(angular.element(valid).find('input').val())){
                        angular.element(valid.querySelector('div.status')).html("Please use a valid email address");
                        angular.element(valid.querySelector('div.status')).addClass('showStatus');

                        validation++;
                    }
                }else if(valid.attributes['data-validate'].value == "Confirm Password"){
                    if(document.querySelector('.datapassword').value != document.querySelector('.dataconfirmpassword').value){
                        angular.element(valid.querySelector('div.status')).html("Please confirm password correctly");
                        angular.element(valid.querySelector('div.status')).addClass('showStatus');

                        validation++;
                    }
                }

            });

            return validation;
        },

        getName: function (fullname) {

            if (fullname) {
                return fullname.split(' ')[0];
            }else{
                return 'NA';
            }
        },

        getInitials: function (fullname) {

            if (fullname) {
                return fullname.split(' ')[0].charAt(0)+fullname.split(' ')[1].charAt(0);
            }else{
                return 'NA';
            }
        },

        getColor: function (fullname) {

            if (fullname) {
                var alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                var colorpalette = ['#96ceb4','#ff6f69','#ffcc5c','#88d8b0','#0F5959', '#571845', '#900C3E', '#C70039', '#FF5733', '#FFC300'];

                return colorpalette[alphabet.indexOf(fullname.charAt(0).toUpperCase()) % 10];
            }else{
                return '#000';
            }
        }

    };
});

function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}