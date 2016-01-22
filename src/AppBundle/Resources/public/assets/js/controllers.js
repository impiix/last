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

lastApp.controller("FollowCtrl", function ($scope, $http, $interval, $window, $timeout) {

    var enabled = false;

    $scope.show = false;
    $scope.showLoader = false;
    $scope.showError = false;
    $scope.errorContent = "";
    $scope.followLabel = "Follow";
    $scope.followTrack = "";

    $scope.formData = {

    };

    $scope.load = function ($event, username) {
        console.log('zion');
        console.log($scope.formData.name);
        if (enabled) {
            $scope.stop();
            enabled = false;
            $scope.followLabel = "Follow";
        } else {
            $scope.start(username);
            enabled = true;
            $scope.followLabel = "Unfollow";
        }
    }

    $scope.setFollow = function () {
        $scope.formData.follow = "follow";
        delete $scope.formData.submit;
    }

    $scope.setSubmit = function () {
        $scope.formData.submit = "submit";
        delete $scope.formData.follow;
    }

    $scope.submit = function ($event, name) {

        $http({
            method: "POST",
            url:    "/",
            data:   $.param({"last_form": $scope.formData}),
            headers:{"Content-Type": "application/x-www-form-urlencoded"}
        }).
        success(function(data) {
            var resp = angular.fromJson(data);

            if (resp.result) {

                if (resp.result == "redirect") {
                    $window.location.href = resp.url;
                } else if (resp.result == 'ok' && $scope.formData.follow) {
                    console.log($scope.formData.name);
                    $scope.load($event, $scope.formData.name);
                }else if (resp.result == 'ok') {
                    $scope.show = true;
                    $timeout(function() {
                        $scope.show = false;
                        }, 1000);
                } else if (resp.result == 'error') {
                    console.log(resp);
                    $scope.errorContent = resp.message;
                    $scope.showError = true;
                    $timeout(function() {
                        $scope.showError = false;
                    }, 1000);

                }
            }
        });
    }

    var promise;

    $scope.start = function(username) {
        $scope.stop();

        $scope.showLoader = true;
        promise = $interval(function() {
            $http.get('/follow/' + username)
                .then(function(response) {
                    if (response.data.updated == 'yes') {
                        $scope.followTrack = response.data.description + "\n" + $scope.followTrack;
                    }
                });

        }, 10000);
    }

    $scope.stop = function() {
        $scope.showLoader = false;
        $interval.cancel(promise);
    }
});