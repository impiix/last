var lastApp = angular.module("lastApp", []);

lastApp.controller("OrderCtrl", function ($scope, $http, $interval) {

    $interval(function() {
       $scope.load();
    }, 50000);

    $scope.load = function () {
        $http.get('orders.json').success(function(data) {
            $scope.orders = data;
        });
    };

    $scope.load();

    $scope.orderProp = "created_at";
});

lastApp.controller("FollowCtrl", function ($scope, $http, $interval) {

    var enabled = false;

    $scope.formData = {};
    $scope.submitValue = false;

    $scope.load = function ($event, username) {
        if (enabled) {
            $scope.stop();
            enabled = false;
            angular.element($event.currentTarget).html("Follow");
        } else {
            $scope.start(username);
            enabled = true;
            angular.element($event.currentTarget).html("Unfollow");
        }
    }

    $scope.setFollow = function () {
        $scope.submitValue = "follow";
    }

    $scope.submit = function ($event, name) {
        console.log($event);
        console.log(name);
        $scope.formData.submit = $scope.submitValue;
        $http({
            method: "POST",
            url:    "/",
            data:   $.param({"last_form": $scope.formData}),
            headers:{"Content-Type": "application/x-www-form-urlencoded"}
        }).
        success(function(data) {
            console.log(data);
        });
    }

    var promise;

    $scope.start = function(username) {
        $scope.stop();

        promise = $interval(function() {
            $http.get('/follow/' + username);
        }, 5000);
    }

    $scope.stop = function() {
        $interval.cancel(promise);
    }
});