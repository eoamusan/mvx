app.directive('clickAnywhereButHere', function($document, clickAnywhereButHereService) {
    return {
        restrict: 'A',
        link: function(scope, elem, attr, ctrl) {
            var handler = function(e) {
                e.stopPropagation();
            };
            elem.on('click', handler);

            scope.$on('$destroy', function() {
                elem.off('click', handler);
            });

            clickAnywhereButHereService(scope, attr.clickAnywhereButHere);
        }
    };
});

app.directive('customOnChange', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            var onChangeHandler = scope.$eval(attrs.customOnChange);
            element.bind('change', onChangeHandler);
        }
    }
});

app.directive('verificationBoxes', function($animate) {
    return {
        restrict: 'A',
        scope: true,
        link: function(scope, element, attrs) {
            scope.isActive = false;

            element.bind('keyup', function() {
                if(element[0].value.length != 0){
                    if (element.parent().next().children()[0]) {
                        element.parent().next().children()[0].focus();
                    }
                }

                //Trigger digest in this case, because this listener function is out of the angular world
                scope.$apply();

            });
        }
    }
})

app.directive('focusInput', function($animate) {
    return {
        restrict: 'A',
        scope: true,
        link: function(scope, element, attrs) {
            element.on('click', function() {
                if (element.find('input')[0]) {
                    element.find('input')[0].focus();
                }

                //Trigger digest in this case, because this listener function is out of the angular world
                scope.$apply();

            });
        }
    }
})